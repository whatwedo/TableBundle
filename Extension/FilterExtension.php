<?php
/*
 * Copyright (c) 2017, whatwedo GmbH
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

namespace whatwedo\TableBundle\Extension;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\QueryBuilder;
use whatwedo\TableBundle\Filter\Type\AjaxManyToManyFilterType;
use whatwedo\TableBundle\Filter\Type\AjaxRelationFilterType;
use whatwedo\TableBundle\Filter\Type\BooleanFilterType;
use whatwedo\TableBundle\Filter\Type\DateFilterType;
use whatwedo\TableBundle\Filter\Type\DatetimeFilterType;
use whatwedo\TableBundle\Filter\Type\NumberFilterType;
use whatwedo\TableBundle\Filter\Type\TextFilterType;
use whatwedo\TableBundle\Table\DoctrineTable;

/**
 * Class FilterExtension
 * @package whatwedo\TableBundle\Extension
 */
class FilterExtension extends AbstractExtension
{

    /**
     * @var Registry $doctrine
     */
    protected $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * @param DoctrineTable $table
     * @param string $entityClass
     * @param string $queryAlias
     * @param QueryBuilder $queryBuilder
     * @param callable $labelCallable
     */
    public function addFiltersAutomatically(DoctrineTable $table, $entityClass, $queryAlias, $queryBuilder, $labelCallable = null)
    {
        $reader = new AnnotationReader();
        $reflectionClass = new \ReflectionClass($entityClass);
        $properties = $reflectionClass->getProperties();

        foreach ($properties as $property)
        {
            /** @var Column $ormColumn */
            $ormColumn = $reader->getPropertyAnnotation($property, Column::class);
            /** @var ManyToOne $ormManyToOne */
            $ormManyToOne = $reader->getPropertyAnnotation($property, ManyToOne::class);
            /** @var ManyToMany $ormManyToMany */
            $ormManyToMany = $reader->getPropertyAnnotation($property, ManyToMany::class);
            $acronym = $property->getName();

            if (is_null($labelCallable)) {
                $labelCallable = function (DoctrineTable $table, $property) {
                    /** @var \whatwedo\TableBundle\Table\Column $column */
                    foreach ($table->getColumns() as $column) {
                        if ($column->getAcronym() == $property) {
                            return $column->getLabel();
                        }
                    }
                    return ucfirst($property);
                };
            }

            $label = call_user_func($labelCallable, $table, $property->getName());
            $accessor = $acronym;
            if (!is_null($ormColumn)) {
                $accessor = sprintf('%s.%s', $queryAlias, $acronym);
                switch ($ormColumn->type){
                    case 'string':
                        $table->addFilter($acronym, $label, new TextFilterType($accessor));
                        break;
                    case 'date':
                        $table->addFilter($acronym, $label, new DateFilterType($accessor));
                        break;
                    case 'datetime':
                        $table->addFilter($acronym, $label, new DatetimeFilterType($accessor));
                        break;
                    case 'integer':
                    case 'float':
                    case 'decimal':
                        $table->addFilter($acronym, $label, new NumberFilterType($accessor));
                        break;
                    case 'boolean':
                        $table->addFilter($acronym, $label, new BooleanFilterType($accessor));
                }
            } else if (!is_null($ormManyToOne)) {
                $target = $ormManyToOne->targetEntity;
                if (strpos($target, '\\') === false) {
                    $target = preg_replace('#[a-zA-Z0-9]+$#i', $target, $entityClass);
                }

                $joins = [];
                if (!in_array($acronym, $queryBuilder->getAllAliases())) {
                    $joins = [$acronym => sprintf('%s.%s', $queryAlias, $acronym)];
                }
                $table->addFilter($acronym, $label, new AjaxRelationFilterType($accessor, $target, $this->doctrine, $joins));
            } else if (!is_null($ormManyToMany)) {
                $accessor = sprintf('%s.%s', $queryAlias, $acronym);
                $joins = [];
                if (!in_array($acronym, $queryBuilder->getAllAliases())) {
                    $joins = [$acronym => ['leftJoin', sprintf('%s.%s', $queryAlias, $acronym)]];
                }
                $target = $ormManyToMany->targetEntity;
                if (strpos($target, '\\') === false) {
                    $target = $reflectionClass->getNamespaceName() . '\\' . $target;
                }
                $table->addFilter($acronym, $label, new AjaxManyToManyFilterType($accessor, $target, $this->doctrine, $joins));
            }
        }
    }


    /**
     * @param $enabledBundles
     * @return boolean
     */
    public static function isEnabled($enabledBundles)
    {
        return true;
    }
}
