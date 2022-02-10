<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Doctrine\ORM\Tools\Pagination\Paginator;
use whatwedo\TableBundle\Extension\PaginationExtension;

class PaginatorDoctrineDataLoader extends DoctrineDataLoader
{
    public function __construct(
        protected PaginationExtension $paginationExtension
    ) {
    }

    public function getResults(): iterable
    {
        $this->paginationExtension->setLimit($this->options['default_limit']);

        $paginator = new Paginator(
            $this->options[self::OPTION_QUERY_BUILDER]
                ->setMaxResults($this->paginationExtension->getLimit())
                ->setFirstResult(($this->paginationExtension->getCurrentPage() - 1) * $this->paginationExtension->getLimit())
        );

        $this->paginationExtension->setTotalResults(\count($paginator));

        return $paginator->getIterator();
    }
}
