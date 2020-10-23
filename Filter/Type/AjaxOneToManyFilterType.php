<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
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
use Doctrine\Persistence\ManagerRegistry;

class AjaxOneToManyFilterType extends FilterType
{
    const CRITERIA_EQUAL = 'equal';

    const CRITERIA_NOT_EQUAL = 'not_equal';

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var string
     */
    protected $targetClass;

    public function __construct($column, string $targetClass, ManagerRegistry $doctrine, array $joins = [])
    {
        parent::__construct($column, $joins);
        $this->doctrine = $doctrine;
        $this->targetClass = $targetClass;
    }

    /**
     * @return array
     */
    public function getOperators()
    {
        return [
            static::CRITERIA_EQUAL => 'whatwedo_table.filter.operator.contains',
            static::CRITERIA_NOT_EQUAL => 'whatwedo_table.filter.operator.contains_not',
        ];
    }

    /**
     * @param int $value
     *
     * @return string
     */
    public function getValueField($value = 0)
    {
        $field = sprintf(
            '<select name="{name}" class="form-control" data-ajax-select data-ajax-entity="%s">',
            $this->targetClass
        );
        $currentSelection = null;
        if ($value > 0) {
            $currentSelection = $this->doctrine->getRepository($this->targetClass)->find($value);
        }

        if (null !== $currentSelection) {
            $field .= sprintf('<option value="%s">%s</option>', $value, $currentSelection->__toString());
        }

        $field .= '</select>';

        return $field;
    }

    /**
     * @param string $operator
     * @param string $parameterName
     *
     * @return bool|\Doctrine\ORM\Query\Expr\Comparison|string
     */
    public function addToQueryBuilder($operator, $value, $parameterName, QueryBuilder $queryBuilder)
    {
        $targetParameter = 'target_'.md5(rand());
        $targetValue = $this->doctrine->getRepository($this->targetClass)->find($value);
        $queryBuilder->setParameter($targetParameter, $targetValue);
        switch ($operator) {
            case static::CRITERIA_EQUAL:
                return $queryBuilder->expr()->in($this->column, ':'.$targetParameter);
            case static::CRITERIA_NOT_EQUAL:
                return sprintf(':%s NOT IN %s', $this->column, $targetParameter);
        }

        return false;
    }
}
