<?php

/**
 * Copyright 2015 François Kooman <fkooman@tuxed.net>.
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

use SURFnet\VPN\Common\TplInterface;
use Twig_Loader_Filesystem;
use Twig_Environment;
use Twig_SimpleFilter;
use RuntimeException;
use Twig_Extensions_Extension_I18n;

class TwigTpl implements TplInterface
{
    /** @var Twig_Environment */
    private $twig;

    /** @var array */
    private $defaultVariables;

    /**
     * Create TwigTemplateManager.
     *
     * @param array  $templateDirs template directories to look in where later
     *                             paths override the earlier paths
     * @param string $cacheDir     the writable directory to store the cache
     */
    public function __construct(array $templateDirs, $cacheDir = null)
    {
        $existingTemplateDirs = array();
        foreach ($templateDirs as $templateDir) {
            if (false !== is_dir($templateDir)) {
                $existingTemplateDirs[] = $templateDir;
            }
        }
        $existingTemplateDirs = array_reverse($existingTemplateDirs);

        $environmentOptions = array(
            'strict_variables' => true,
        );

        if (null !== $cacheDir) {
            if (false === is_dir($cacheDir)) {
                if (false === @mkdir($cacheDir, 0700, true)) {
                    throw new RuntimeException('unable to create template cache directory');
                }
            }
            $environmentOptions['cache'] = $cacheDir;
        }

        $this->twig = new Twig_Environment(
            new Twig_Loader_Filesystem(
                $existingTemplateDirs
            ),
            $environmentOptions
        );

        $this->defaultVariables = array();
    }

    public function setDefault(array $templateVariables)
    {
        $this->defaultVariables = $templateVariables;
    }

    public function addDefault(array $templateVariables)
    {
        $this->defaultVariables = array_merge(
            $this->defaultVariables, $templateVariables
        );
    }

    public function setI18n($appName, $languageStr, $localeDir)
    {
        putenv(sprintf('LC_ALL=%s', $languageStr));

        if (false === setlocale(LC_ALL, [$languageStr, sprintf('%s.UTF-8', $languageStr)])) {
            throw new RuntimeException(sprintf('unable to set locale "%s"', $languageStr));
        }

        if ($localeDir !== bindtextdomain($appName, $localeDir)) {
            throw new RuntimeException('unable to bind text domain');
        }

        if (!is_string(bind_textdomain_codeset($appName, 'UTF-8'))) {
            throw new RuntimeException('unable to bind text domain codeset');
        }

        if ($appName !== textdomain($appName)) {
            throw new RuntimeException('unable to set text domain');
        }

        $this->twig->addExtension(new Twig_Extensions_Extension_I18n());
    }

    public function addFilter(Twig_SimpleFilter $filter)
    {
        $this->twig->addFilter($filter);
    }

    public function render($templateName, array $templateVariables = array())
    {
        $templateVariables = array_merge($this->defaultVariables, $templateVariables);

        return $this->twig->render(
            sprintf(
                '%s.twig',
                $templateName
            ),
            $templateVariables
        );
    }
}
