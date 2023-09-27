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

class DatetimeFilterType extends FilterType
{
    const CRITERIA_EQUAL = 'equal';

    const CRITERIA_NOT_EQUAL = 'not_equal';

    const CRITERIA_BEFORE = 'before';

    const CRITERIA_AFTER = 'after';

    const CRITERIA_IN_YEAR = 'in_year';

    public function getOperators()
    {
        return [
            static::CRITERIA_EQUAL => 'whatwedo_table.filter.operator.equal',
            static::CRITERIA_NOT_EQUAL => 'whatwedo_table.filter.operator.not_equal',
            static::CRITERIA_BEFORE => 'whatwedo_table.filter.operator.before',
            static::CRITERIA_AFTER => 'whatwedo_table.filter.operator.after',
            static::CRITERIA_IN_YEAR => 'whatwedo_table.filter.operator.same_year',
        ];
    }

    public function getValueField($value = null): string
    {
        $value = $value ? \DateTime::createFromFormat('d.m.Y H:i:s', $value) : new \DateTime();

        return sprintf(
            '<input type="text" name="{name}" value="%s" class="form-control" data-provide="datetimepicker" data-date-format="dd.mm.yyyy HH:ii">',
            $value instanceof \DateTime ? $value->format('d.m.Y H:i') : ''
        );
    }

    public function addToQueryBuilder($operator, $value, $parameterName, QueryBuilder $queryBuilder)
    {
        $value = $this->prepareDateValue($value);

        $dateAsString = $value->format($this->getQueryDataFormat());

        switch ($operator) {
            case static::CRITERIA_EQUAL:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->eq(
                    sprintf(':%s', $parameterName),
                    sprintf('%s', $this->getColumn())
                );
            case static::CRITERIA_NOT_EQUAL:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->neq(
                    sprintf(':%s', $parameterName),
                    sprintf('%s', $this->getColumn())
                );
            case static::CRITERIA_BEFORE:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->lt($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_AFTER:
                $queryBuilder->setParameter($parameterName, $dateAsString);

                return $queryBuilder->expr()->gt($this->getColumn(), sprintf(':%s', $parameterName));
            case static::CRITERIA_IN_YEAR:
                $startYear = clone $value;
                $endYear = clone $value;
                $startYear->modify('first day of January '.date('Y'))->setTime(0, 0, 0);
                $endYear->modify('last day of December '.date('Y'))->setTime(23, 59, 59);
                $queryBuilder->setParameter($parameterName.'_start', $startYear->format($this->getQueryDataFormat()));
                $queryBuilder->setParameter($parameterName.'_end', $endYear->format($this->getQueryDataFormat()));

                return $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->gte($this->getColumn(), sprintf(':%s', $parameterName.'_start')),
                    $queryBuilder->expr()->lte($this->getColumn(), sprintf(':%s', $parameterName.'_end'))
                );
        }

        return false;
    }

    protected function getDateFormat(): string
    {
        return 'd.m.Y H:i';
    }

    protected function getQueryDataFormat(): string
    {
        return 'Y-m-d H:i:s';
    }

    /**
     * @throws \Exception
     *
     * @return \DateTime|false
     */
    protected function prepareDateValue(string $value): \DateTime
    {
        $value = \DateTime::createFromFormat($this->getDateFormat(), $value);
        if (!$value) {
            $value = new \DateTime();
        }

        return $value;
    }
}
