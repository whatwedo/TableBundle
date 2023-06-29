<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

abstract class FilterType implements FilterTypeInterface
{
    public function __construct(
        protected string $column,
        protected array $joins = [
        ]
    ) {
    }

    public function getColumn(): string
    {
        return $this->column;
    }

    public function setColumn($column): static
    {
        $this->column = $column;

        return $this;
    }

    public function getJoins(): array
    {
        return $this->joins;
    }

    public function setJoins($joins): static
    {
        $this->joins = $joins;

        return $this;
    }
}
