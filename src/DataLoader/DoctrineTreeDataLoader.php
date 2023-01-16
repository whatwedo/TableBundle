<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query;
use Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\TableBundle\Entity\TreeInterface;
use whatwedo\TableBundle\Extension\PaginationExtension;

class DoctrineTreeDataLoader extends AbstractDataLoader
{
    public const OPT_QUERY_BUILDER = 'query_builder';

    public function __construct(
        protected PaginationExtension $paginationExtension,
        protected EntityManagerInterface $entityManager
    ) {
    }

    public function getResults(): iterable
    {
        $this->entityManager->getConfiguration()->addCustomHydrationMode('tree', TreeObjectHydrator::class);

        $results = [];

        $queryResults = $this->options[self::OPT_QUERY_BUILDER]
            ->getQuery()
            ->setHint(Query::HINT_INCLUDE_META_COLUMNS, true)
            ->getResult('tree');

        $this->getHierarchicalResults($results, $queryResults);

        $this->paginationExtension->setTotalResults(\count($results));

        $results = array_slice(
            $results,
            ($this->paginationExtension->getCurrentPage() - 1) * $this->paginationExtension->getLimit(),
            $this->paginationExtension->getLimit()
        );

        return (new ArrayCollection($results))->getIterator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(self::OPT_QUERY_BUILDER);
        $resolver->setAllowedTypes(self::OPT_QUERY_BUILDER, 'Doctrine\ORM\QueryBuilder');
    }

    private function getHierarchicalResults(array &$results, array $getResult)
    {
        foreach ($getResult as $item) {
            if ($item instanceof TreeInterface) {
                $results[] = $item;
                if ($item->getChildren()->count()) {
                    $this->getHierarchicalResults($results, $item->getChildren()->toArray());
                }
            }
        }
    }
}
