<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\TableBundle\Extension\PaginationExtension;

class DoctrineDataLoader extends AbstractDataLoader
{
    public const OPT_QUERY_BUILDER = 'query_builder';

    protected PaginationExtension $paginationExtension;

    public function loadNecessaryExtensions(iterable $extensions): void
    {
        foreach ($extensions as $extension) {
            if ($extension instanceof PaginationExtension) {
                $this->paginationExtension = $extension;
                break;
            }
        }
    }

    public function getResults(): iterable
    {
        /** @var QueryBuilder $qb */
        $qb = (clone $this->options[self::OPT_QUERY_BUILDER]);
        $qb->select('COUNT(' . $qb->getRootAliases()[0] . ')');
        $this->paginationExtension->setTotalResults((int) $qb->getQuery()->getSingleScalarResult());

        if ($this->paginationExtension->getLimit()) {
            $this->options[self::OPT_QUERY_BUILDER]
                ->setMaxResults($this->paginationExtension->getLimit())
                ->setFirstResult($this->paginationExtension->getOffsetResults());
        }

        $paginator = new Paginator(
            $this->options[self::OPT_QUERY_BUILDER]
        );

        return $paginator->getIterator();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setRequired(self::OPT_QUERY_BUILDER);
        $resolver->setAllowedTypes(self::OPT_QUERY_BUILDER, QueryBuilder::class);
    }
}
