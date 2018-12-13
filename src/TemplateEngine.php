<?php

/*
 * eduVPN - End-user friendly VPN.
 *
 * Copyright: 2016-2018, The Commons Conservancy eduVPN Programme
 * SPDX-License-Identifier: AGPL-3.0+
 */

namespace SURFnet\VPN\Admin;

use SURFnet\VPN\Admin\Exception\TemplateException;
use SURFnet\VPN\Common\TplInterface;

class TemplateEngine implements TplInterface
{
    /** @var array */
    private $templateDirList;

    /** @var null|string */
    private $activeSectionName = null;

    /** @var array */
    private $sectionList = [];

    /** @var array */
    private $layoutList = [];

    /** @var array */
    private $defaultTemplateVariables = [];

    /**
     * @param array $templateDirList
     */
    public function __construct(array $templateDirList)
    {
        $this->templateDirList = $templateDirList;
    }

    /**
     * @param array $defaultTemplateVariables
     *
     * @return void
     */
    public function addDefault(array $defaultTemplateVariables)
    {
        $this->defaultTemplateVariables = array_merge($this->defaultTemplateVariables, $defaultTemplateVariables);
    }

    /**
     * @param string $templateName
     * @param array  $templateVariables
     *
     * @return string
     */
    public function render($templateName, array $templateVariables = [])
    {
        \extract(array_merge($this->defaultTemplateVariables, $templateVariables));
        \ob_start();
        /** @psalm-suppress UnresolvableInclude */
        include $this->templatePath($templateName);
        $templateStr = \ob_get_clean();

        if (0 !== \count($this->layoutList)) {
            $templateName = \array_keys($this->layoutList)[0];
            $templateVariables = $this->layoutList[$templateName];

            // because we use render we must empty the layoutList
            $this->layoutList = [];

            return $this->render($templateName, $templateVariables);
        }

        return $templateStr;
    }

    /**
     * @param string $templateName
     * @param array  $templateVariables
     *
     * @return string
     */
    public function insert($templateName, array $templateVariables = [])
    {
        \extract(array_merge($this->defaultTemplateVariables, $templateVariables));
        \ob_start();
        /** @psalm-suppress UnresolvableInclude */
        include $this->templatePath($templateName);
        $templateStr = \ob_get_clean();

        return $templateStr;
    }

    /**
     * @param string $sectionName
     *
     * @return void
     */
    public function start($sectionName)
    {
        if (null !== $this->activeSectionName) {
            throw new TemplateException(\sprintf('section "%s" already started', $this->activeSectionName));
        }

        $this->activeSectionName = $sectionName;
        \ob_start();
    }

    /**
     * @return void
     */
    public function stop()
    {
        if (null === $this->activeSectionName) {
            throw new TemplateException('no section started');
        }

        $this->sectionList[$this->activeSectionName] = \ob_get_clean();
        $this->activeSectionName = null;
    }

    /**
     * @param string $layoutName
     * @param array  $templateVariables
     *
     * @return void
     */
    public function layout($layoutName, array $templateVariables = [])
    {
        $this->layoutList[$layoutName] = $templateVariables;
    }

    /**
     * @param string $sectionName
     *
     * @return string
     */
    public function section($sectionName)
    {
        if (!\array_key_exists($sectionName, $this->sectionList)) {
            throw new TemplateException(\sprintf('section "%s" does not exist', $sectionName));
        }

        return $this->sectionList[$sectionName];
    }

    /**
     * @param string $v
     *
     * @return string
     */
    private function e($v)
    {
        return \htmlentities($v, ENT_QUOTES, 'UTF-8');
    }

    /**
     * @param string $v
     *
     * @return string
     */
    private function t($v)
    {
        return $v;
    }

    /**
     * @param string $templateName
     *
     * @return string
     */
    private function templatePath($templateName)
    {
        foreach ($this->templateDirList as $templateDir) {
            if (\file_exists($templateDir.'/'.$templateName)) {
                return $templateDir.'/'.$templateName;
            }
            if (\file_exists($templateDir.'/'.$templateName.'.php')) {
                return $templateDir.'/'.$templateName.'.php';
            }
        }

        throw new TemplateException(\sprintf('template "%s" does not exist', $templateName));
    }
}
