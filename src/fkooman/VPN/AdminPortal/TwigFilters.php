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

use Twig_SimpleFilter;

class TwigFilters
{
    public static function sizeToHuman()
    {
        return new Twig_SimpleFilter(
            'sizeToHuman',
            function ($byteSize) {
                $kB = 1024;
                $MB = $kB * 1024;
                $GB = $MB * 1024;

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
