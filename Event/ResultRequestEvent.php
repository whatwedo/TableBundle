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

namespace whatwedo\TableBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Class ResultRequestEvent.
 */
class ResultRequestEvent extends Event
{
    const FILTER_SET = 'whatwedo_ajax.result_request.filter_set';
    const RELATION_SET = 'whatwedo_ajax.result_request.relation_set';

    /**
     * @var JsonResponse
     */
    protected $result;

    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $term;

    /**
     * @var QueryBuilder|null
     */
    protected $queryBuilder;

    /**
     * ResultRequestEvent constructor.
     *
     * @param string $entity
     * @param string $term
     */
    public function __construct($entity, $term)
    {
        $this->entity = $entity;
        $this->term = $term;
    }

    /**
     * @return JsonResponse
     */
    public function getResult()
    {
        return $this->result;
    }

    /**
     * @param JsonResponse $result
     *
     * @return $this
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity(string $entity): void
    {
        $this->entity = $entity;
    }

    /**
     * @return string
     */
    public function getTerm()
    {
        return $this->term;
    }

    public function getQueryBuilder(): ?QueryBuilder
    {
        return $this->queryBuilder;
    }

    public function setQueryBuilder(?QueryBuilder $queryBuilder): void
    {
        $this->queryBuilder = $queryBuilder;
    }
}
