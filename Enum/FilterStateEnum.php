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

namespace whatwedo\TableBundle\Enum;

/**
 * @author Nicolo Singer <nicolo@whatwedo.ch>
 */
class FilterStateEnum
{

    const ALL       = 1;
    const SELF      = 2;
    const SYSTEM    = 3;

    protected static $values = [
        FilterStateEnum::ALL        =>  'Öffentlich',
        FilterStateEnum::SELF       =>  'Privat',
        FilterStateEnum::SYSTEM     =>  'System'
    ];

    public static function getArray()
    {
        return static::$values;
    }

    public static function getValues()
    {
        return array_keys(static::$values);
    }

    public static function has($value)
    {
        return isset(static::$values[$value]);
    }

    public static function getRepresentation($value)
    {
        if (isset(static::$values[$value])) {
            return static::$values[$value];
        }

        return null;
    }

    public static function getFormValues()
    {
        return array_flip(static::$values);
    }

}
