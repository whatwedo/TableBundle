<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ArrayDataLoader extends AbstractDataLoader
{
    public const OPTION_DATA = 'data';

    public function getResults(): iterable
    {
        return $this->options[self::OPTION_DATA];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(self::OPTION_DATA);
        $resolver->setAllowedTypes(self::OPTION_DATA, ['iterable']);
    }
}
