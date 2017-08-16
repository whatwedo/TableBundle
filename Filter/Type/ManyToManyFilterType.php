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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ManyToManyFilterType extends FilterType
{

    const CRITERIA_EQUAL = 'equal';
    const CRITERIA_NOT_EQUAL = 'not_equal';

    /**
     * @var EntityManager $em
     */
    protected $em;

    /**
     * @var string $targetClazz
     */
    protected $targetClazz;

    /**
     * @var Callable $callable
     */
    protected $callable;

    /**
     * @var string $labelAccessorPath
     */
    protected $labelAccessorPath = '__toString';

    /**
     * ManyToManyFilterType constructor.
     * @param string $column
     * @param array $joins
     * @param EntityManager $em
     * @param string $targetClazz
     */
    public function __construct($column, array $joins = [], EntityManager $em, $targetClazz)
    {
        parent::__construct($column, $joins);
        $this->em = $em;
        $this->targetClazz = $targetClazz;
        $this->setCallable();
    }

    /**
     * @param Callable|null $callable
     * @return ManyToManyFilterType $this
     */
    public function setCallable($callable = null)
    {
        if (is_null($callable)) {
            $this->callable = function () {
                return $this->em->getRepository($this->targetClazz)->findAll();
            };
        } else {
            $this->callable = $callable;
        }
        return $this;
    }

    /**
     * @param string $labelAccessorPath
     */
    public function setLabelAccessorPath($labelAccessorPath)
    {
        $this->labelAccessorPath = $labelAccessorPath;
    }

    /**
     * @return array
     */
    public function getOperators()
    {
        return [
            static::CRITERIA_EQUAL => 'enthält',
            static::CRITERIA_NOT_EQUAL => 'enthält nicht',
        ];
    }

    /**
     * @param int $value
     * @return string
     */
    public function getValueField($value = 0)
    {
        $field = '<select name="{name}" class="form-control">';
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        foreach (call_user_func($this->callable) as $entity) {
            $value = null;
            if ($this->labelAccessorPath == '__toString') {
                $value = $entity->__toString();
            } else {
                $value = $propertyAccessor->getValue($entity, $this->labelAccessorPath);
            }

            $field .= sprintf(
                '<option value="%s" %s>%s</option>',
                $entity->getId(),
                $entity->getId() == (int) $value ? 'selected="selected"' : '',
                $value
            );
        }
        $field .= '</select>';
        return $field;
    }

    /**
     * @param $operator
     * @param $value
     * @param $parameterName
     * @param QueryBuilder $queryBuilder
     * @return bool|\Doctrine\ORM\Query\Expr\Comparison|string
     */
    public function addToQueryBuilder($operator, $value, $parameterName, QueryBuilder $queryBuilder)
    {
        $rand = md5(rand());
        $targetParameter = sprintf('target_%s', $rand);
        $targetValue = $this->em->getRepository($this->targetClazz)->find($value);
        $queryBuilder->setParameter($targetParameter, $targetValue);
        switch ($operator) {
            case (static::CRITERIA_EQUAL):
                return $queryBuilder->expr()->isMemberOf(':' . $targetParameter, $this->column);
            case (static::CRITERIA_NOT_EQUAL):
                return sprintf(
                    ':%s NOT MEMBER OF %s',
                    $targetParameter,
                    $this->column
                );
        }
        return false;
    }

}
