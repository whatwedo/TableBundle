<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

use Symfony\Component\OptionsResolver\OptionsResolver;

#[Autoconfigure(tags: ['whatwedo.table_bundle.data_loader'])]
interface DataLoaderInterface
{
    public function configureOptions(OptionsResolver $resolver): void;

    public function setOptions(array $options): void;

    public function getResults(): iterable;

    public function getOption(string $option);
}
