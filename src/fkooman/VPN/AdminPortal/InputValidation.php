<?php
/**
 * Copyright 2016 François Kooman <fkooman@tuxed.net>.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace fkooman\VPN\AdminPortal;

use fkooman\Http\Exception\BadRequestException;

class InputValidation
{
    const USER_ID_PATTERN = '/^[a-zA-Z0-9-.@]+$/';
    const CONFIG_NAME_PATTERN = '/^[a-zA-Z0-9-_.@]+$/';

    public static function userId($userId)
    {
        if (0 === preg_match(self::USER_ID_PATTERN, $userId)) {
            throw new BadRequestException('invalid value for "user_id"');
        }
        if ('..' === $userId) {
            throw new BadRequestException('"user_id" cannot be ".."');
        }
    }

    public static function configName($configName)
    {
        if (0 === preg_match(self::CONFIG_NAME_PATTERN, $configName)) {
            throw new BadRequestException('invalid value for "config_name"');
        }
        if ('..' === $configName) {
            throw new BadRequestException('"config_name" cannot be ".."');
        }
    }

    public static function checkboxValue($checkBoxValue)
    {
        if (!is_null($checkBoxValue) && 'on' !== $checkBoxValue) {
            throw new BadRequestException('invalid form checkbox value');
        }
    }
}
