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

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use whatwedo\CoreBundle\Formatter\DefaultFormatter;

/**
 * @author Ueli Banholzer <ueli@whatwedo.ch>
 */
class Column extends AbstractColumn implements SortableColumnInterface
{
    /**
     * @var string
     */
    protected $tableIdentifier;

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => $this->acronym,
            'callable' => null,
            'accessor_path' => $this->acronym,
            'formatter' => DefaultFormatter::class,
            'formatter_options' => [],
            'sortable' => true,
        ]);

        $resolver->setDefault('sort_expression', function (Options $options) {
            return $options['accessor_path'];
        });
    }

    /**
     * gets the content of the row.
     *
     * @return string
     */
    public function getContents($row)
    {
        if (\is_callable($this->options['callable'])) {
            if (\is_array($this->options['callable'])) {
                return \call_user_func($this->options['callable'], [$row]);
            }

            return $this->options['callable']($row);
        }

        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        try {
            return $propertyAccessor->getValue($row, $this->options['accessor_path']);
        } catch (UnexpectedTypeException $e) {
            return '';
        } catch (NoSuchPropertyException $e) {
            return $e->getMessage();
        }
    }

    protected function formatData($data, $formatter, $formatterOptions)
    {
        if (\is_string($formatter)) {
            $formatterObj = $this->formatterManager->getFormatter($formatter);
            $formatterObj->processOptions($formatterOptions);

            return $formatterObj->getHtml($data);
        }

        if (\is_callable($formatter)) {
            return $formatter($data);
        }

        if (\is_array($formatter)) {
            foreach ($formatter as $index => $aFormatter) {
                $data = $this->formatData($data, $aFormatter, $formatterOptions[$index]);
            }

            return $data;
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function render($row)
    {
        return (string) $this->formatData($this->getContents($row), $this->options['formatter'], $this->options['formatter_options']);
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->options['label'];
    }

    /**
     * @return string
     */
    public function getSortExpression()
    {
        return $this->options['sort_expression'];
    }

    /**
     * @return bool
     */
    public function isSortable()
    {
        return $this->options['sortable'];
    }

    /**
     * @param bool $sortable
     *
     * @return $this
     */
    public function setSortable($sortable)
    {
        $this->options['sortable'] = $sortable;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrderQueryASC(ParameterBag $query)
    {
        return $this->getOrderQuery($query, '1', '1');
    }

    /**
     * @return string
     */
    public function getOrderQueryDESC(ParameterBag $query)
    {
        return $this->getOrderQuery($query, '1', '0');
    }

    /**
     * @return string
     */
    public function getDeleteOrder(ParameterBag $query)
    {
        return $this->getOrderQuery($query, '0', '1');
    }

    private function getOrderQuery(ParameterBag $query, string $enabled, string $asc): string
    {
        $queryData = array_replace($query->all(), [
            $this->getOrderEnabledQueryParameter() => $enabled,
            $this->getOrderAscQueryParameter() => $asc,
        ]);
        // remove parameter where is_order_... equals '0' aka not active
        $removeLater = [];
        $offset = \mb_strlen(SortableColumnInterface::ORDER_ENABLED);

        foreach (array_keys($queryData) as $key) {
            if (SortableColumnInterface::ORDER_ENABLED === mb_substr($key, 0, $offset)) {
                if (!$queryData[$key]) {
                    $removeLater[] = $key;
                    $removeLater[] = SortableColumnInterface::ORDER_ASC.mb_substr($key, $offset);
                }
            }
        }
        foreach ($removeLater as $key) {
            if (\array_key_exists($key, $queryData)) {
                unset($queryData[$key]);
            }
        }

        return !empty($queryData) ? '?'.http_build_query($queryData) : '?';
    }

    /**
     * @param string $identifier
     */
    public function setTableIdentifier($identifier)
    {
        $this->tableIdentifier = $identifier;
    }

    private function getColumnIdentifier()
    {
        return str_replace('.', '_', $this->tableIdentifier.'_'.$this->getAcronym());
    }

    /**
     * @return string
     */
    public function getOrderEnabledQueryParameter()
    {
        return static::ORDER_ENABLED.$this->getColumnIdentifier();
    }

    /**
     * @return string
     */
    public function getOrderAscQueryParameter()
    {
        return static::ORDER_ASC.$this->getColumnIdentifier();
    }
}
