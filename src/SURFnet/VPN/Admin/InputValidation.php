<?php
/**
 *  Copyright (C) 2016 SURFnet.
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
namespace SURFnet\VPN\Admin;

use SURFnet\VPN\Common\Http\Exception\HttpException;

class InputValidation
{
    const USER_ID_PATTERN = '/^[a-zA-Z0-9-.@]+$/';
    const CONFIG_NAME_PATTERN = '/^[a-zA-Z0-9-_.@]+$/';

    public static function userId($userId)
    {
        if (0 === preg_match(self::USER_ID_PATTERN, $userId)) {
            throw new HttpException('invalid value for "user_id"', 400);
        }
        if ('..' === $userId) {
            throw new HttpException('"user_id" cannot be ".."', 400);
        }
    }

    public static function configName($configName)
    {
        if (0 === preg_match(self::CONFIG_NAME_PATTERN, $configName)) {
            throw new HttpException('invalid value for "config_name"', 400);
        }
        if ('..' === $configName) {
            throw new HttpException('"config_name" cannot be ".."', 400);
        }
    }

    public static function checkboxValue($checkBoxValue)
    {
        if (!is_null($checkBoxValue) && 'on' !== $checkBoxValue) {
            throw new HttpException('invalid form checkbox value', 400);
        }
    }
}
