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

namespace araise\TableBundle\Extension;

use araise\TableBundle\Helper\RouterHelper;
use araise\TableBundle\Table\Column;
use araise\TableBundle\Table\ColumnInterface;
use araise\TableBundle\Table\Table;
use Symfony\Component\HttpFoundation\RequestStack;

class SortExtension extends AbstractExtension
{
    public function __construct(
        protected RequestStack $requestStack
    ) {
    }

    public function getOrder(ColumnInterface $column): ?string
    {
        if (! $column->getOption(Column::OPT_SORTABLE)) {
            return null;
        }

        return $this->getOrderFromQuery($column);
    }

    public function getOrderParameters(ColumnInterface $column, ?string $order): array
    {
        return [
            RouterHelper::getParameterName(
                $this->table->getIdentifier(),
                RouterHelper::PARAMETER_SORT_DIRECTION,
                $column->getIdentifier()
            ) => $column->getOption(Column::OPT_SORTABLE) ? $order : null,
            RouterHelper::getParameterName(
                $this->table->getIdentifier(),
                RouterHelper::PARAMETER_SORT_ENABLED,
                $column->getIdentifier()
            ) => $order && $column->getOption(Column::OPT_SORTABLE) ? '1' : null,
            RouterHelper::getParameterName(
                $this->table->getIdentifier(),
                RouterHelper::PARAMETER_PAGINATION_PAGE
            ) => null,
        ];
    }

    public function getOrders($useDefault = true): array
    {
        $sortedColumns = [];

        foreach ($this->table->getColumns() as $column) {
            if (! $column->getOption(Column::OPT_SORTABLE)) {
                continue;
            }
            $order = $this->getOrderFromQuery($column);
            if ($order) {
                $sortedColumns[$column->getOption(Column::OPT_SORT_EXPRESSION)] = $order;
            }
        }

        if (! $sortedColumns && $useDefault) {
            return $this->table->getOption(Table::OPT_DEFAULT_SORT);
        }

        return $sortedColumns;
    }

    public static function isEnabled(array $enabledBundles): bool
    {
        return true;
    }

    protected function getOrderFromQuery(ColumnInterface $column): ?string
    {
        $order = strtolower((string) $this->requestStack->getCurrentRequest()->query->get(
            RouterHelper::getParameterName(
                $this->table->getIdentifier(),
                RouterHelper::PARAMETER_SORT_DIRECTION,
                $column->getIdentifier()
            )
        ));

        if (! in_array($order, ['asc', 'desc'], true)) {
            return null;
        }

        return $order;
    }
}
