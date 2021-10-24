<?php

declare(strict_types=1);
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
use whatwedo\CoreBundle\Enum\AbstractSimpleEnum;

class SimpleEnumFilterType extends FilterType
{
    public const CRITERIA_EQUAL = 'equal';

    public const CRITERIA_NOT_EQUAL = 'not_equal';

    public function __construct(
        $column,
        array $joins,
        protected string $enumClass
    ) {
        if (! is_subclass_of($this->enumClass, AbstractSimpleEnum::class)) {
            throw new \InvalidArgumentException('"%s" is not an instance of %s', $this->enumClass, AbstractSimpleEnum::class);
        }

        parent::__construct($column, $joins);
    }

    public function getOperators(): array
    {
        return [
            self::CRITERIA_EQUAL => 'whatwedo_table.filter.operator.equal',
            self::CRITERIA_NOT_EQUAL => 'whatwedo_table.filter.operator.not_equal',
        ];
    }

    public function getValueField(?string $value = null): string
    {
        $keys = call_user_func([$this->enumClass, 'getValues']);

        $options = '';
        foreach ($keys as $key) {
            $options .= sprintf(
                '<option value="%s" %s>%s</option>',
                $key,
                $key === $value ? 'selected' : '',
                call_user_func([$this->enumClass, 'getRepresentation'], $key)
            );
        }

        return sprintf(
            '<select name="{name}" class="form-control">%s</select>',
            $options
        );
    }

    public function toDql(string $operator, string $value, string $parameterName, QueryBuilder $queryBuilder): ?string
    {
        $queryBuilder->setParameter($parameterName, $value);

        switch ($operator) {
            case self::CRITERIA_EQUAL:
                return $queryBuilder->expr()->eq(':' . $parameterName, $this->getColumn());
            case self::CRITERIA_NOT_EQUAL:
                return $queryBuilder->expr()->neq(':' . $parameterName, $this->getColumn());
        }

        return null;
    }
}
