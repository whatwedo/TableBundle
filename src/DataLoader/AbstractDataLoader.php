<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractDataLoader implements DataLoaderInterface
{
    protected array $options;

    public function setOptions(array $options): void
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
    }
}
