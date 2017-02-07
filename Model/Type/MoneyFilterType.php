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

namespace whatwedo\TableBundle\Model\Type;
use Doctrine\ORM\QueryBuilder;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class MoneyFilterType extends FilterType
{
    const CRITERIA_EQUAL = 'equal';
    const CRITERIA_NOT_EQUAL = 'not_equal';
    const CRITERIA_BIGGER_THAN = 'bigger_than';
    const CRITERIA_SMALLER_THAN = 'smaller_than';

    public function getOperators()
    {
        return [
            static::CRITERIA_EQUAL => 'ist gleich',
            static::CRITERIA_NOT_EQUAL => 'ist ungleich',
            static::CRITERIA_BIGGER_THAN => 'grösser als',
            static::CRITERIA_SMALLER_THAN => 'kleiner als',
        ];
    }

    public function getValueField($value = null)
    {
        if (!is_numeric($value)) {
            $value = 0;
        }

        return sprintf('<input type="number" step="any" name="{name}" value="%s" class="form-control">', $value);
    }

    public function addToQueryBuilder($operator, $value, $parameterName, QueryBuilder $queryBuilder)
    {
        if (!is_numeric($value)) {
            $value = 0;
        }

        $value = ((float) $value) * 100;

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                $queryBuilder->setParameter($parameterName, $value);
                return $queryBuilder->expr()->eq($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_NOT_EQUAL:
                $queryBuilder->setParameter($parameterName, $value);
                return $queryBuilder->expr()->neq($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_BIGGER_THAN:
                $queryBuilder->setParameter($parameterName, $value);
                return $queryBuilder->expr()->gt($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_SMALLER_THAN:
                $queryBuilder->setParameter($parameterName, $value);
                return $queryBuilder->expr()->lt($this->getColumn(), sprintf(':%s', $parameterName));
        }

        return false;
    }
}
