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
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class RelationFilterType extends FilterType
{
    const CRITERIA_EQUAL = 'equal';
    const CRITERIA_NOT_EQUAL = 'not_equal';

    protected $choices;
    protected $emptyQuery;
    protected $accessorPath;
    private $propToCheckAgainstId;

    /**
     * @var PropertyAccessor
     */
    protected static $propertyAccessor;

    public function __construct($column, $choices, $joins = [], $emptyFieldsCheck = false, $accessorPath = false, $propToCheckAgainstId = 'id')
    {
        $this->choices = $choices;

        if (false !== $emptyFieldsCheck
            && !\is_array($emptyFieldsCheck)) {
            $emptyFieldsCheck = [$emptyFieldsCheck];
        }
        $this->emptyQuery = $emptyFieldsCheck;
        $this->accessorPath = $accessorPath;
        $this->propToCheckAgainstId = $propToCheckAgainstId;
        parent::__construct($column, $joins);
    }

    /**
     * @return PropertyAccessor
     */
    public function getPropertyAccessor()
    {
        if (null === static::$propertyAccessor) {
            static::$propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return static::$propertyAccessor;
    }

    public function getOperators()
    {
        return [
            static::CRITERIA_EQUAL => 'whatwedo_table.filter.operator.is',
            static::CRITERIA_NOT_EQUAL => 'whatwedo_table.filter.operator.is_not',
        ];
    }

    public function getValueField($value = 0)
    {
        $field = sprintf('<select name="{name}" class="form-control" %s>', \count($this->choices) < 10 ? 'data-disable-interactive' : '');

        if ($this->emptyQuery) {
            $field .= "<option value='empty'>- leer -</option>";
        }

        foreach ($this->choices as $choice) {
            if ($this->accessorPath) {
                $strValue = '';
                try {
                    $strValue = $this->getPropertyAccessor()->getValue($choice, $this->accessorPath);
                } catch (UnexpectedTypeException $e) {
                }
            } else {
                $strValue = $choice->__toString();
            }

            /* @noinspection PhpUndefinedMethodInspection */
            $field .= sprintf(
                '<option value="%s" %s>%s</option>',
                $choice->getId(),
                $choice->getId() === (int) $value ? 'selected="selected"' : '',
                $strValue
            );
        }

        $field .= '</select>';

        return $field;
    }

    public function addToQueryBuilder($operator, $value, $parameterName, QueryBuilder $queryBuilder)
    {
        if ('empty' === $value
            && \is_array($this->emptyQuery)) {
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

            if (1 === $orX->count()) {
                return $orX->getParts()[0];
            }

            return $orX;
        }

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                return $queryBuilder->expr()->eq(sprintf('%s.%s', $this->getColumn(), $this->propToCheckAgainstId), (int) $value);
            case static::CRITERIA_NOT_EQUAL:
                return $queryBuilder->expr()->neq(sprintf('%s.%s', $this->getColumn(), $this->propToCheckAgainstId), (int) $value);
        }

        return false;
    }
}
