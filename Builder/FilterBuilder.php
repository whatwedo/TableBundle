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

namespace whatwedo\TableBundle\Builder;

use whatwedo\TableBundle\Extension\FilterExtension;

/**
 * Class FilterBuilder.
 *
 * @internal
 */
class FilterBuilder
{
    /**
     * @var string
     */
    protected $id;

    protected $filterExtension;

    protected $filters = [
        'filter_column' => [],
        'filter_operator' => [],
        'filter_value' => [],
    ];

    /**
     * FilterBuilder constructor.
     *
     * @param $id
     * @param $acronym
     * @param $operator
     * @param $value
     */
    public function __construct($id, $acronym, $operator, $value, FilterExtension $filterExtension)
    {
        $this->id = $id;
        $this->filterExtension = $filterExtension;
        $this->or($acronym, $operator, $value);
    }

    /**
     * @param $acronym
     * @param $operator
     * @param $value
     *
     * @return $this
     */
    public function and($acronym, $operator, $value)
    {
        $idx = \count($this->filters['filter_column']) - 1;
        $this->filters['filter_column'][$idx][] = $acronym;
        $this->filters['filter_operator'][$idx][] = $operator;
        $this->filters['filter_value'][$idx][] = $value;

        return $this;
    }

    /**
     * @param $acronym
     * @param $operator
     * @param $value
     *
     * @return $this
     */
    public function or($acronym, $operator, $value)
    {
        $this->filters['filter_column'][] = [$acronym];
        $this->filters['filter_operator'][] = [$operator];
        $this->filters['filter_value'][] = [$value];

        return $this;
    }

    /**
     * @return FilterExtension
     */
    public function end()
    {
        return $this->filterExtension->addPredefinedFilter($this->id, $this->filters);
    }
}
