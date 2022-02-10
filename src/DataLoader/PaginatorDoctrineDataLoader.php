<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use whatwedo\TableBundle\Extension\PaginationExtension;

class PaginatorDoctrineDataLoader implements DataLoaderInterface
{
    private QueryBuilder $queryBuilder;

    private array $options = [];

    public function __construct(
        protected PaginationExtension $paginationExtension
    ) {
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getResults()
    {
        $this->paginationExtension->setLimit($this->options['default_limit']);

        $paginator = new Paginator(
            $this->queryBuilder
                ->setMaxResults($this->options['default_limit'])
                ->setFirstResult(($this->paginationExtension->getCurrentPage() - 1) * $this->options['default_limit'])
        );

        $this->paginationExtension->setTotalResults(\count($paginator));

        return $paginator->getIterator();
    }

    public function setDefaultLimit(int $limit)
    {
        $this->options['default_limit'] = $limit;
    }
}
