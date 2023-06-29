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

use Symfony\Component\HttpFoundation\RequestStack;
use araise\TableBundle\Helper\RouterHelper;
use araise\TableBundle\Table\Table;

class PaginationExtension extends AbstractExtension
{
    protected int $limit = 25;

    protected int $totalResults = 0;

    public function __construct(
        protected RequestStack $requestStack
    ) {
    }

    public function setTable(Table $table): self
    {
        parent::setTable($table);
        $this->limit = $table->getOption(Table::OPT_DEFAULT_LIMIT);

        return $this;
    }

    public function getCurrentPage(): int
    {
        if (! $this->requestStack->getCurrentRequest()) {
            return 1;
        }

        $page = $this->requestStack->getCurrentRequest()->query->getInt(RouterHelper::getParameterName($this->table->getIdentifier(), RouterHelper::PARAMETER_PAGINATION_PAGE), 1);

        if ($page < 1) {
            return 1;
        }

        if ($this->getTotalPages() < $page && $this->getTotalPages() > 0) {
            return $this->getTotalPages();
        }

        return $page;
    }

    public function getLimit(): int
    {
        if ($this->requestStack->getCurrentRequest()) {
            $this->limit = $this->requestStack->getCurrentRequest()->query->getInt(RouterHelper::getParameterName($this->table->getIdentifier(), RouterHelper::PARAMETER_PAGINATION_LIMIT), $this->limit);
        }

        return $this->limit;
    }

    public function setLimit(int $defaultLimit): self
    {
        $this->limit = $defaultLimit;

        return $this;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    public function setTotalResults(int $totalResults): self
    {
        $this->totalResults = $totalResults;

        return $this;
    }

    public function getTotalPages(): int
    {
        if ($this->getLimit() <= 0) {
            return 1;
        }

        return (int) ceil($this->getTotalResults() / $this->getLimit());
    }

    public function getOffsetResults(): int
    {
        if ($this->getLimit() <= 0) {
            return 0;
        }

        return ($this->getCurrentPage() - 1) * $this->getLimit();
    }

    public static function isEnabled(array $enabledBundles): bool
    {
        return true;
    }
}
