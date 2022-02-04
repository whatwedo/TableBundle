<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Table;

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractColumn implements ColumnInterface
{
    protected array $options = [];

    public function __construct(
        protected Table $table,
        protected string $identifier = '',
        array $options = [
        ]
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getOption(string $key)
    {
        return $this->options[$key] ?? null;
    }

    public function setOption(string $key, $value): void
    {
        $this->options[$key] = $value;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getActions($row): array
    {
        return [];
    }
}
