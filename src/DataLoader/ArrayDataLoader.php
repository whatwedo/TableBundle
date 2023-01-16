<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ArrayDataLoader extends AbstractDataLoader
{
    public const OPT_DATA = 'data';

    public function getResults(): iterable
    {
        return $this->options[self::OPT_DATA];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(self::OPT_DATA);
        $resolver->setAllowedTypes(self::OPT_DATA, ['iterable']);
    }
}
