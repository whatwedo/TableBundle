<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace whatwedo\TableBundle\Filter\Type;

use Doctrine\ORM\QueryBuilder;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class TextFilterType extends FilterType
{
    const CRITERIA_EQUAL = 'equal';
    const CRITERIA_NOT_EQUAL = 'not_equal';
    const CRITERIA_STARTS_WITH = 'starts_with';
    const CRITERIA_ENDS_WITH = 'ends_with';
    const CRITERIA_CONTAINS = 'contains';
    const CRITERIA_IS_EMPTY = 'is_empty';
    const CRITERIA_IS_NOT_EMPTY = 'is_not_empty';

    public function getOperators()
    {
        return [
            static::CRITERIA_EQUAL => 'whatwedo_table.filter.operator.equal',
            static::CRITERIA_NOT_EQUAL => 'whatwedo_table.filter.operator.not_equal',
            static::CRITERIA_CONTAINS => 'whatwedo_table.filter.operator.contains',
            static::CRITERIA_STARTS_WITH => 'whatwedo_table.filter.operator.starts_with',
            static::CRITERIA_ENDS_WITH => 'whatwedo_table.filter.operator.ends_with',
            static::CRITERIA_IS_EMPTY => 'whatwedo_table.filter.operator.is_empty',
            static::CRITERIA_IS_NOT_EMPTY => 'whatwedo_table.filter.operator.is_not_empty',
        ];
    }

    public function getValueField($value = '')
    {
        return sprintf('<input type="text" name="{name}" value="%s" class="form-control">',
            addcslashes($value, '"')
        );
    }

    public function addToQueryBuilder($operator, $value, $parameterName, QueryBuilder $queryBuilder)
    {
        switch ($operator) {
            case static::CRITERIA_EQUAL:
                $queryBuilder->setParameter($parameterName, $value);

                return $queryBuilder->expr()->eq($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_NOT_EQUAL:
                $queryBuilder->setParameter($parameterName, $value);

                return $queryBuilder->expr()->neq($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_STARTS_WITH:
                $queryBuilder->setParameter($parameterName, $value.'%');

                return $queryBuilder->expr()->like($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_ENDS_WITH:
                $queryBuilder->setParameter($parameterName, '%'.$value);

                return $queryBuilder->expr()->like($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_CONTAINS:
                $queryBuilder->setParameter($parameterName, '%'.$value.'%');

                return $queryBuilder->expr()->like($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_IS_EMPTY:
                $queryBuilder->setParameter($parameterName, '');

                return $queryBuilder->expr()->orX()->addMultiple([
                    $queryBuilder->expr()->isNull($this->getColumn()),
                    $queryBuilder->expr()->eq($this->getColumn(), sprintf(':%s', $parameterName)),
                ]);
            case static::CRITERIA_IS_NOT_EMPTY:
                $queryBuilder->setParameter($parameterName, '');

                return $queryBuilder->expr()->orX()->addMultiple([
                    $queryBuilder->expr()->isNotNull($this->getColumn()),
                    $queryBuilder->expr()->eq($this->getColumn(), sprintf(':%s', $parameterName)),
                ]);
        }

        return false;
    }
}
