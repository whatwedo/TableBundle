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

namespace whatwedo\TableBundle\Extension;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class PaginationExtension
 * @package whatwedo\TableBundle\Extension
 */
class PaginationExtension implements ExtensionInterface
{

    const QUERY_PARAMETER_PAGE = 'page';
    const QUERY_PARAMETER_LIMIT = 'limit';

    /**
     * @var RequestStack $requestStack
     */
    protected $requestStack;

    /**
     * @var int
     */
    protected $limit = 25;

    /**
     * @var int
     */
    protected $totalResults = 0;

    /**
     * @var string
     */
    protected $tableIdentifier;

    /**
     * PaginationExtension constructor.
     * @param RequestStack $requestStack
     */
    public function __construct($requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * returns current page number.
     * @return int
     */
    public function getCurrentPage()
    {
        $page = $this->getRequest()->query->getInt($this->tableIdentifier.'_'.static::QUERY_PARAMETER_PAGE, 1);
        if ($page < 1) {
            $page = 1;
        }
        return $page;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param $defaultLimit
     * @return $this
     */
    public function setLimit($defaultLimit)
    {
        $this->limit = $this->getRequest()->query->getInt(
            $this->tableIdentifier.'_'.static::QUERY_PARAMETER_LIMIT, $defaultLimit
        );
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalResults()
    {
        return $this->totalResults;
    }

    /**
     * @param int $totalResults
     * @return $this
     */
    public function setTotalResults($totalResults)
    {
        $this->totalResults = $totalResults;
        return $this;
    }

    /**
     * @return int
     */
    public function getTotalPages()
    {
        if ($this->limit === -1) {
            return 1;
        }
        return ceil($this->getTotalResults() / $this->limit);
    }

    /**
     * @return int
     */
    public function getOffsetResults()
    {
        if ($this->limit === -1) {
            return 0;
        }
        return ($this->getCurrentPage() - 1) * $this->limit;
    }

    /**
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    protected function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @param string $identifier
     * @return $this
     */
    public function setTableIdentifier($identifier)
    {
        $this->tableIdentifier = $identifier;
        return $this;
    }

    /**
     * @param $enabledBundles
     * @return boolean
     */
    public static function isEnabled($enabledBundles)
    {
        return true;
    }

}
