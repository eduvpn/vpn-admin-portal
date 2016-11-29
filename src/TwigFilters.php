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

use Twig_SimpleFilter;
use Twig_Error_Runtime;

class TwigFilters
{
    public static function sizeToHuman()
    {
        return new Twig_SimpleFilter(
            'sizeToHuman',
            function ($byteSize) {
                if (!is_int($byteSize)) {
                    throw new Twig_Error_Runtime('number of bytes must be a number.');
                }

                $kB = 1024;
                $MB = $kB * 1024;
                $GB = $MB * 1024;
                $TB = $GB * 1024;

                if ($byteSize > $TB) {
                    return sprintf('%0.2fTB', $byteSize / $TB);
                }
                if ($byteSize > $GB) {
                    return sprintf('%0.2fGB', $byteSize / $GB);
                }
                if ($byteSize > $MB) {
                    return sprintf('%0.2fMB', $byteSize / $MB);
                }

                return sprintf('%0.0fkB', $byteSize / $kB);
            }
        );
    }
}
