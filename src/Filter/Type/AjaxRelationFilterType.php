<?php

declare(strict_types=1);

namespace araise\TableBundle\Filter\Type;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class AjaxRelationFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    protected static PropertyAccessor $propertyAccessor;

    public function __construct(
        string $column,
        protected string $targetClass,
        protected EntityManagerInterface $entityManager,
        protected string $jsonSearchUrl,
        array $joins = []
    ) {
        parent::__construct($column, $joins);
    }

    public function getOperators(): array
    {
        return [
            static::CRITERIA_EQUAL => 'araise_table.filter.operator.is',
            static::CRITERIA_NOT_EQUAL => 'araise_table.filter.operator.is_not',
        ];
    }

    public function getValueField(?string $value = '0'): string
    {
        $field = sprintf(
            '<select name="{name}" class="form-control" data-araise--core-bundle--select-url-value="%s" data-controller="araise--core-bundle--select" data-araise--core-bundle--select-required-value="0">',
            $this->jsonSearchUrl
        );

        $currentSelection = (int) $value > 0 ? $this->entityManager->getRepository($this->targetClass)->find((int) $value) : null;
        if ($currentSelection) {
            $field .= sprintf('<option value="%s">%s</option>', $value, $currentSelection->__toString());
        }

        $field .= '</select>';

        return $field;
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder)
    {
        switch ($operator) {
            case static::CRITERIA_EQUAL:
                if ((int) $value) {
                    return $queryBuilder->expr()->eq($this->getColumn() . '.id', (int) $value);
                }

                return $queryBuilder->expr()->isNull($this->getColumn() . '.id');

            case static::CRITERIA_NOT_EQUAL:
                if ((int) $value) {
                    return $queryBuilder->expr()->neq($this->getColumn() . '.id', (int) $value);
                }

                return $queryBuilder->expr()->isNotNull($this->getColumn() . '.id');
        }

        return null;
    }
}
