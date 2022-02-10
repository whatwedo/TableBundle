<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Symfony\Component\OptionsResolver\OptionsResolver;

class DoctrineDataLoader extends AbstractDataLoader
{
    public const OPTION_QUERY_BUILDER = 'query_builder';

    public function getResults(): iterable
    {
        return $this->options[self::OPTION_QUERY_BUILDER]->getQuery()->toIterable();
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('default_limit', 5);
        $resolver->setRequired(self::OPTION_QUERY_BUILDER);
        $resolver->setAllowedTypes(self::OPTION_QUERY_BUILDER, 'Doctrine\ORM\QueryBuilder');
    }
}
