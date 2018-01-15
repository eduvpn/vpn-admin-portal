<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2018, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Admin;

use Twig_Error_Runtime;
use Twig_SimpleFilter;

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
                    return sprintf('%0.2f TiB', $byteSize / $TB);
                }
                if ($byteSize > $GB) {
                    return sprintf('%0.2f GiB', $byteSize / $GB);
                }
                if ($byteSize > $MB) {
                    return sprintf('%0.2f MiB', $byteSize / $MB);
                }

                return sprintf('%0.0f kiB', $byteSize / $kB);
            }
        );
    }
}
