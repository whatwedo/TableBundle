<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

interface FilterTypeInterface
{
    public function getColumn(): string;

    public function getJoins(): array;

    public function getOperators(): array;

    public function getValueField(?string $value): string;

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder): ?string;
}
