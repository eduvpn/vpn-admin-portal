<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2018, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Admin;

use Twig_SimpleFilter;

class TwigFilters
{
    /**
     * @return \Twig_SimpleFilter
     */
    public static function sizeToHuman()
    {
        return new Twig_SimpleFilter(
            'sizeToHuman',
            /**
             * @param int $byteSize
             *
             * @return string
             */
            function ($byteSize) {
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
