<?php

declare(strict_types=1);

namespace araise\TableBundle\EventListener;

use araise\TableBundle\DataLoader\ArrayDataLoader;
use araise\TableBundle\Event\DataLoadEvent;
use araise\TableBundle\Table\Column;
use araise\TableBundle\Table\Table;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ArrayOrderEventListener
{
    public function orderResultSet(DataLoadEvent $event): void
    {
        if (! $event->getTable()->getDataLoader() instanceof ArrayDataLoader
            || ! $event->getTable()->getSortExtension()) {
            return;
        }

        $dataLoader = $event->getTable()->getDataLoader();
        $data = $dataLoader->getOption(ArrayDataLoader::OPT_DATA)->toArray();
        $sortedColumns = $event->getTable()->getSortExtension()->getOrders(true);
        $propertyAccess = PropertyAccess::createPropertyAccessor();

        foreach ($sortedColumns as $column => $order) {
            $accessorPath = $this->getAccessorPath($event->getTable(), $column);
            $sortedColumns[$accessorPath] = $order;
            unset($sortedColumns[$column]);
        }
        $sortValues = [];
        foreach ($sortedColumns as $column => $order) {
            $array = [];
            foreach ($data as $row) {
                $array[] = $propertyAccess->getValue($row, $column);
            }
            $sortValues[] = $array;
            $sortValues[] = $order === 'asc' ? SORT_ASC : SORT_DESC;
        }

        $sortValues[] = &$data;
        array_multisort(...$sortValues);

        $dataLoader->setOptions([
            ArrayDataLoader::OPT_DATA => new ArrayCollection($data),
        ]);
    }

    private function getAccessorPath(Table $table, string $columnId): string
    {
        foreach ($table->getColumns() as $column) {
            if ($column->getIdentifier() === $columnId) {
                return $column->getOption(Column::OPT_ACCESSOR_PATH);
            }
        }
        return $columnId;
    }
}
