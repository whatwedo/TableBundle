<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

class DoctrineDataLoader implements DataLoaderInterface
{
    private \Doctrine\ORM\QueryBuilder $queryBuilder;

    public function setQueryBuilder(\Doctrine\ORM\QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function getResults()
    {
        return $this->queryBuilder->getQuery()->toIterable();
    }
}
