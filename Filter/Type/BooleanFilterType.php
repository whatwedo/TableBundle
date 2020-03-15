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
class BooleanFilterType extends FilterType
{
    const CRITERIA_EQUAL = 'equal';
    const CRITERIA_NOT_EQUAL = 'not_equal';

    public function getOperators()
    {
        return [
            static::CRITERIA_EQUAL => 'ist',
            static::CRITERIA_NOT_EQUAL => 'ist nicht',
        ];
    }

    public function getValueField($value = 1)
    {
        return sprintf(
            '<select name="{name}" class="form-control"><option value="1" %s>ausgewählt</option><option value="0" %s>nicht ausgewählt</option></select>',
            1 === $value ? 'selected' : '',
            0 === $value ? 'selected' : ''
        );
    }

    public function addToQueryBuilder($operator, $value, $parameterName, QueryBuilder $queryBuilder)
    {
        $value = 1 === $value ? 'true' : 'false';

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                return $queryBuilder->expr()->eq($this->getColumn(), $value);
            case static::CRITERIA_NOT_EQUAL:
                return $queryBuilder->expr()->neq($this->getColumn(), $value);
        }

        return false;
    }
}
