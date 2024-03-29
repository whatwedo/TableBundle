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

namespace whatwedo\TableBundle\Extension;

use Symfony\Component\HttpFoundation\RequestStack;
use whatwedo\SearchBundle\whatwedoSearchBundle;
use whatwedo\TableBundle\Helper\RouterHelper;

class SearchExtension extends AbstractExtension
{
    public const QUERY_PARAMETER_QUERY = 'query';

    public function __construct(
        protected RequestStack $requestStack
    ) {
    }

    public function getQuery(): string
    {
        return $this->requestStack->getCurrentRequest()->query->get(RouterHelper::getParameterName($this->table->getIdentifier(), RouterHelper::PARAMETER_SEARCH_QUERY), '');
    }

    public static function isEnabled(array $enabledBundles): bool
    {
        foreach ($enabledBundles as $bundles) {
            if (in_array(whatwedoSearchBundle::class, $bundles, true)) {
                return true;
            }
        }

        return false;
    }
}
