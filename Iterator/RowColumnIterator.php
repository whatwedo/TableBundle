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

namespace whatwedo\TableBundle\Iterator;

use whatwedo\TableBundle\Collection\ColumnCollection;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\ColumnReflection;

class RowColumnIterator implements \Iterator
{
    /**
     * @var int
     */
    protected $position = 0;

    /**
     * @var array
     */
    protected $keys = [];

    protected $row;

    /**
     * @var ColumnCollection|Column[]
     */
    protected $columns;

    /**
     * @var string|int
     */
    protected $primaryKey;

    public function __construct($row, ColumnCollection $columns)
    {
        $this->row = $row;
        $columns = iterator_to_array($columns->getIterator());
        $this->keys = array_keys($columns);
        $this->columns = array_values($columns);
    }

    public function getData()
    {
        return $this->row;
    }

    /**
     * @return ColumnReflection
     */
    public function current()
    {
        return new ColumnReflection($this->columns[$this->position], $this->row);
    }

    public function next()
    {
        ++$this->position;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return isset($this->columns[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }
}
