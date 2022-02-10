<?php

declare(strict_types=1);
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

namespace whatwedo\TableBundle\Helper;

class RouterHelper
{
    public const PARAMETER_PAGINATION_PAGE = 'page';

    public const PARAMETER_PAGINATION_LIMIT = 'limit';

    public const PARAMETER_FILTER_PREDEFINED = 'filter_predefined';

    public const PARAMETER_SEARCH_QUERY = 'query';

    public const PARAMETER_SORT_ENABLED = 'sort_enabled';

    public const PARAMETER_SORT_DIRECTION = 'sort_direction';

    public const PARAMETER_FILTER_COLUMN = 'filter_column';

    public const PARAMETER_FILTER_OPERATOR = 'filter_operator';

    public const PARAMETER_FILTER_VALUE = 'filter_value';

    public static function getParameterName(string $identifier, string $parameter, $addition = null): string
    {
        return sprintf('%s_%s', str_replace('.', '_', $identifier), $parameter)
            . ($addition ? '_' . $addition : '');
    }
}
