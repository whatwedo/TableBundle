<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

class ChoiceFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    protected array $choices = [];

    public function __construct(string $column, array $choices, array $joins = [])
    {
        parent::__construct($column, $joins);
        $this->choices = $choices;
    }

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.is',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.is_not',
        ];
    }

    public function getValueField(?string $value = null): string
    {
        $result = '<select name="{name}" class="form-control"><option value=""></option>';

        foreach ($this->choices as $key => $choice) {
            $result .= sprintf(
                '<option value="%s" %s>%s</option>',
                $key,
                $value === $key ? 'selected' : '',
                $choice
            );
        }

        $result .= '</select>';

        return $result;
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        $queryBuilder->setParameter($parameterName, $value);

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                return $queryBuilder->expr()->eq($this->getColumn(), ':' . $parameterName);
            case static::CRITERIA_NOT_EQUAL:
                return $queryBuilder->expr()->neq($this->getColumn(), ':' . $parameterName);
        }

        return false;
    }
}
