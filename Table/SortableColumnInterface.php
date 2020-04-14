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

namespace whatwedo\TableBundle\Table;

use Symfony\Component\HttpFoundation\ParameterBag;

interface SortableColumnInterface
{
    const ORDER_ENABLED = 'is_order_';

    const ORDER_ASC = 'asc_order_';

    /**
     * @return string
     */
    public function getSortExpression();

    /**
     * @return bool
     */
    public function isSortable();

    /**
     * @return string
     */
    public function getOrderQueryASC(ParameterBag $query);

    /**
     * @return string
     */
    public function getOrderQueryDESC(ParameterBag $query);

    /**
     * @return string
     */
    public function getDeleteOrder(ParameterBag $query);

    /**
     * @return string
     */
    public function getOrderEnabledQueryParameter();

    /**
     * @return string
     */
    public function getOrderAscQueryParameter();

    /**
     * @param string $identifier
     */
    public function setTableIdentifier($identifier);

    /**
     * @param bool $sortable
     *
     * @return $this
     */
    public function setSortable($sortable);
}
