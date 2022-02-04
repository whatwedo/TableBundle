<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Table;

use Symfony\Component\OptionsResolver\OptionsResolver;

interface ColumnInterface
{
    public function __construct(Table $table, string $identifier, array $options = []);

    public function getTable(): Table;

    public function getIdentifier(): string;

    public function configureOptions(OptionsResolver $resolver): void;

    public function getOption(string $key);

    public function setOption(string $key, $value): void;

    public function render($row): string;

    public function getActions($row): array;
}
