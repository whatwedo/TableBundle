<?php
/*
 * Copyright (c) 2021, whatwedo GmbH
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

namespace whatwedo\TableBundle\Manager;


use Doctrine\ORM\QueryBuilder;
use whatwedo\TableBundle\Provider\QueryBuilderProvider;

/**
 * @author Timo Bühlmann
 */
class QueryBuilderManager
{
    protected iterable $queryBuilderProviders;

    public function __construct(iterable $queryBuilderProviders)
    {
        $this->queryBuilderProviders = $queryBuilderProviders;
    }

    /**
     * @return QueryBuilder|null
     */
    public function getQueryBuilderForEntity(string $class)
    {
        /** @var QueryBuilderProvider[] $matchingProviders */
        $matchingProviders = array_filter(iterator_to_array($this->queryBuilderProviders), function(QueryBuilderProvider $provider) use ($class) {
            return $provider->getEntity() === $class;
        });
        if ($matchingProviders && count($matchingProviders) > 1) {
            throw Exception('Multiple query-builder-providers found. It is not clear, which to use.');
        }
        if (!$matchingProviders || count($matchingProviders) < 1) {
            return null;
        }
        return $matchingProviders[0]->getQueryBuilder();
    }

    public function getQueryBuilderProviders(): iterable
    {
        return $this->queryBuilderProviders;
    }
}
