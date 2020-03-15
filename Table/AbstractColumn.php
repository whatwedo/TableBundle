<?php
/*
 * Copyright (c) 2016, whatwedo GmbH
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

namespace whatwedo\TableBundle\Table;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use whatwedo\CoreBundle\Manager\FormatterManager;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
abstract class AbstractColumn implements ColumnInterface, TemplateableColumnInterface, FormattableColumnInterface
{
    /**
     * @var string column acronym
     */
    protected $acronym = '';

    /**
     * @var array column options
     */
    protected $options = [];

    /**
     * @var Environment
     */
    protected $templating = null;

    /**
     * @var FormatterManager
     */
    protected $formatterManager;

    /**
     * {@inheritdoc}
     */
    public function __construct($acronym, array $options = [])
    {
        $this->acronym = $acronym;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * @return string
     */
    public function getTdClass()
    {
        return '';
    }

    public function setTemplating(Environment $templating)
    {
        $this->templating = $templating;
    }

    /**
     * @return $this
     */
    public function setFormatterManager(FormatterManager $formatterManager)
    {
        $this->formatterManager = $formatterManager;

        return $this;
    }

    /**
     * @return Environment
     */
    protected function getTemplating()
    {
        return $this->templating;
    }

    /**
     * @return string
     */
    public function getAcronym()
    {
        return $this->acronym;
    }

    /**
     * @return bool
     */
    public function isSortableColumn()
    {
        return $this instanceof SortableColumnInterface;
    }

    public function overrideOptions(array $newOptions)
    {
        $this->options = array_merge_recursive($this->options, $newOptions);
    }
}
