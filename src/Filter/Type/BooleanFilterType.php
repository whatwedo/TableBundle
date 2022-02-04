<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

class BooleanFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'whatwedo_table.filter.operator.is',
            static::CRITERIA_NOT_EQUAL => 'whatwedo_table.filter.operator.is_not',
        ];
    }

    public function getValueField(?string $value = '1'): string
    {
        return sprintf(
            '<select name="{name}" class="form-control"><option value="1" %s>ausgewählt</option><option value="0" %s>nicht ausgewählt</option></select>',
            $value === '1' ? 'selected' : '',
            $value === '0' ? 'selected' : ''
        );
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $value = $value === '1' ? 'true' : 'false';

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                return $queryBuilder->expr()->eq($this->getColumn(), $value);
            case static::CRITERIA_NOT_EQUAL:
                return $queryBuilder->expr()->neq($this->getColumn(), $value);
        }

        return false;
    }
}
