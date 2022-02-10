<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Gedmo\Tree\Hydrator\ORM\TreeObjectHydrator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\TableBundle\Entity\TreeInterface;
use whatwedo\TableBundle\Extension\PaginationExtension;

class DoctrineTreeDataLoader extends AbstractDataLoader
{
    public const OPTION_QUERY_BUILDER = 'query_builder';

    public const OPTION_DEFAULT_LIMIT = 'default_limit';

    public function __construct(
        protected PaginationExtension $paginationExtension,
        protected EntityManagerInterface $entityManager
    ) {
    }

    public function getResults(): iterable
    {
        $this->entityManager->getConfiguration()->addCustomHydrationMode('tree', TreeObjectHydrator::class);
        $this->paginationExtension->setLimit($this->options[self::OPTION_DEFAULT_LIMIT]);

        $results = [];

        $queryResults = $this->options[self::OPTION_QUERY_BUILDER]
            ->getQuery()
            ->setHint(\Doctrine\ORM\Query::HINT_INCLUDE_META_COLUMNS, true)
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
        $resolver->setDefault(self::OPTION_DEFAULT_LIMIT, 25);
        $resolver->setRequired(self::OPTION_QUERY_BUILDER);
        $resolver->setAllowedTypes(self::OPTION_QUERY_BUILDER, 'Doctrine\ORM\QueryBuilder');
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
