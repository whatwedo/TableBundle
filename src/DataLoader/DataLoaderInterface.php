<?php

declare(strict_types=1);

namespace araise\TableBundle\DataLoader;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\OptionsResolver\OptionsResolver;

#[Autoconfigure(tags: ['araise.table_bundle.data_loader'])]
interface DataLoaderInterface
{
    public function configureOptions(OptionsResolver $resolver): void;

    public function setOptions(array $options): void;

    public function getResults(): iterable;

    public function getOption(string $option);

    public function loadNecessaryExtensions(iterable $extensions): void;
}
