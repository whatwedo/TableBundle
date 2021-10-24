<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\EventListener;

use Doctrine\ORM\QueryBuilder;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Table\DoctrineTable;

class DoctrineOrderEventListener
{
    public function orderResultSet(DataLoadEvent $event): void
    {
        if (! $event->getTable() instanceof DoctrineTable
            || ! $event->getTable()->getSortExtension()) {
            return;
        }

        $queryBuilder = $event->getTable()->getOption('query_builder');

        $sortedColumns = $event->getTable()->getSortExtension()->getOrders(true);
        if (! empty($sortedColumns)) {
            $queryBuilder->resetDQLPart('orderBy');
        }

        foreach ($sortedColumns as $column => $order) {
            foreach (explode(',', $column) as $sortExp) {
                $this->addOrderBy($queryBuilder, $sortExp, $order);
            }
        }
    }

    private function addOrderBy(QueryBuilder $queryBuilder, string $sortExp, string $order): void
    {
        $alias = $queryBuilder->getRootAliases()[0];

        if (! str_contains($sortExp, '.')
            && ! str_starts_with($sortExp, '_')) { // do not add definition query alias on hidden columns
            $sortExp = sprintf('%s.%s', $alias, $sortExp);
        }

        $allAliases = $queryBuilder->getAllAliases();
        $sortAliases = array_slice(explode('.', $sortExp), 0, -1);

        foreach ($sortAliases as $sortAlias) {
            if (! in_array($sortAlias, $allAliases, true)) {
                $queryBuilder->leftJoin(sprintf('%s.%s', $alias, $sortAlias), $sortAlias);
            }

            $alias = $sortAlias;
        }

        $sortExp = implode('.', array_slice(explode('.', $sortExp), -2));

        $queryBuilder->addOrderBy(
            $sortExp,
            $order
        );
    }
}
