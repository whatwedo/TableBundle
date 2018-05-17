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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class AjaxRelationFilterType extends FilterType
{
    const CRITERIA_EQUAL = 'equal';
    const CRITERIA_NOT_EQUAL = 'not_equal';

    protected $emptyQuery;
    protected $accessorPath;
    private $propToCheckAgainstId;
    protected $targetClass;

    /**
     * @var Registry $doctrine
     */
    protected $doctrine;

    /**
     * @var PropertyAccessor
     */
    protected static $propertyAccessor;

    public function __construct($column, $targetClass, $doctrine, $joins = [], $emptyFieldsCheck = false, $accessorPath = false, $propToCheckAgainstId = 'id')
    {
        if ($emptyFieldsCheck !== false
            && !is_array($emptyFieldsCheck)) {
            $emptyFieldsCheck = [$emptyFieldsCheck];
        }
        $this->emptyQuery = $emptyFieldsCheck;
        $this->accessorPath = $accessorPath;
        $this->propToCheckAgainstId = $propToCheckAgainstId;
        $this->targetClass = $targetClass;
        $this->doctrine = $doctrine;
        parent::__construct($column, $joins);
    }

    public function getOperators()
    {
        return [
            static::CRITERIA_EQUAL => 'ist',
            static::CRITERIA_NOT_EQUAL => 'ist nicht',
        ];
    }

    public function getValueField($value = 0)
    {
        $field = sprintf(
            '<select name="{name}" class="form-control" data-ajax-select data-ajax-entity="%s">',
            $this->targetClass
        );

        $curentSelection = null;
        if ($value > 0) {
            $curentSelection = $this->doctrine->getRepository($this->targetClass)->find($value);
        }

        if (!is_null($curentSelection)) {
            $field .= sprintf('<option value="%s">%s</option>', $value, $curentSelection->__toString());
        } elseif ($this->emptyQuery) {
            $field .= "<option selected value='0'>- leer -</option>";
        }

        $field .= '</select>';

        return $field;
    }

    public function addToQueryBuilder($operator, $value, $parameterName, QueryBuilder $queryBuilder)
    {
        if ($value == 'empty'
            && is_array($this->emptyQuery)) {
            $orX = $queryBuilder->expr()->orX();
            foreach ($this->emptyQuery as $field) {
                switch ($operator) {
                    case static::CRITERIA_EQUAL:
                        $orX->add($field);
                        break;
                    case static::CRITERIA_NOT_EQUAL:
                        $orX->add($queryBuilder->expr()->not($field));
                        break;
                }
            }

            return $orX;
        }

        $property = sprintf('%s.%s', $this->getColumn(), $this->propToCheckAgainstId);

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                return $queryBuilder->expr()->eq($property, (int) $value);
            case static::CRITERIA_NOT_EQUAL:
                return $queryBuilder->expr()->neq($property, (int) $value);
        }

        return false;
    }
}
