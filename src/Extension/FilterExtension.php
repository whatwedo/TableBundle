<?php

declare(strict_types=1);
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

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use whatwedo\TableBundle\Builder\FilterBuilder;
use whatwedo\TableBundle\Entity\Filter as FilterEntity;
use whatwedo\TableBundle\Entity\UserInterface;
use whatwedo\TableBundle\Exception\InvalidFilterAcronymException;
use whatwedo\TableBundle\Filter\Type\AjaxManyToManyFilterType;
use whatwedo\TableBundle\Filter\Type\AjaxOneToManyFilterType;
use whatwedo\TableBundle\Filter\Type\AjaxRelationFilterType;
use whatwedo\TableBundle\Filter\Type\BooleanFilterType;
use whatwedo\TableBundle\Filter\Type\DateFilterType;
use whatwedo\TableBundle\Filter\Type\DatetimeFilterType;
use whatwedo\TableBundle\Filter\Type\FilterTypeInterface;
use whatwedo\TableBundle\Filter\Type\NumberFilterType;
use whatwedo\TableBundle\Filter\Type\SimpleEnumFilterType;
use whatwedo\TableBundle\Filter\Type\TextFilterType;
use whatwedo\TableBundle\Helper\RouterHelper;
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\Filter;

class FilterExtension extends AbstractExtension
{
    public const QUERY_PREDEFINED_FILTER = 'predefined_filter';

    /**
     * @var Filter[]
     */
    protected array $filters = [];

    /**
     * @var Filter[]
     */
    protected array $predefinedFilters = [];

    private array $scalarType = [
        'string' => TextFilterType::class,
        'text' => TextFilterType::class,
        'date' => DateFilterType::class,
        'datetime' => DatetimeFilterType::class,
        'integer' => NumberFilterType::class,
        'float' => NumberFilterType::class,
        'decimal' => NumberFilterType::class,
        'boolean' => BooleanFilterType::class,
    ];

    private array $relationType = [
        OneToMany::class => AjaxOneToManyFilterType::class,
        ManyToOne::class => AjaxRelationFilterType::class,
        ManyToMany::class => AjaxManyToManyFilterType::class,
    ];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack $requestStack
    ) {
    }

    /**
     * @return $this
     */
    public function addFilter(string $acronym, string $label, FilterTypeInterface $type)
    {
        $this->filters[$acronym] = new Filter($acronym, $label, $type);

        return $this;
    }

    /**
     * @param $acronym
     *
     * @return $this
     */
    public function removeFilter($acronym)
    {
        unset($this->filters[$acronym]);

        return $this;
    }

    /**
     * @return $this
     */
    public function removeAll()
    {
        $this->filters = [];

        return $this;
    }

    /**
     * @param $acronym
     *
     * @return Filter
     */
    public function getFilter($acronym)
    {
        if (! isset($this->filters[$acronym])) {
            throw new InvalidFilterAcronymException($acronym);
        }

        return $this->filters[$acronym];
    }

    /**
     * @return Filter[]
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param $acronym
     * @param $label
     *M
     *
     * @return $this
     */
    public function overrideFilterName($acronym, $label)
    {
        $this->getFilter($acronym)->setName($label);

        return $this;
    }

    /**
     * @param $acronym
     * @param $class
     *
     * @return $this
     */
    public function configureSimpleEnumFilter($acronym, $class)
    {
        $filter = $this->getFilter($acronym);
        $name = $filter->getName();
        $column = $filter->getType()->getColumn();
        $this->filters[$acronym] = new Filter($acronym, $name, new SimpleEnumFilterType($column, [], $class));

        return $this;
    }

    /**
     * @param $route
     *
     * @return \whatwedo\TableBundle\Entity\Filter[]
     */
    public function getSavedFilter(?UserInterface $user, $route)
    {
        return $this->entityManager->getRepository(FilterEntity::class)->findSaved($route, $user);
    }

    /**
     * @param callable $labelCallable
     * @param string[] $propertyNames
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function addFiltersAutomatically(DoctrineTable $table, ?callable $labelCallable = null, ?callable $jsonSearchCallable = null, ?array $propertyNames = null)
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $table->getOption('query_builder');
        $entityClass = $queryBuilder->getRootEntities()[0];

        $reflectionClass = new \ReflectionClass($entityClass);
        $labelCallable = \is_callable($labelCallable) ? $labelCallable : [$this, 'labelCallable'];
        $jsonSearchCallable = \is_callable($jsonSearchCallable) ? $jsonSearchCallable : [$this, 'jsonSearchCallable'];

        $properties = $propertyNames ? array_map([$reflectionClass, 'getProperty'], $propertyNames) : $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $this->addFilterAutomatically($table, $queryBuilder, $labelCallable, $jsonSearchCallable, $property, $reflectionClass->getNamespaceName());
        }
    }

    /**
     * @param $id
     * @param $acronym
     * @param $operator
     * @param $value
     *
     * @return FilterBuilder
     */
    public function predefineFilter($id, $acronym, $operator, $value)
    {
        return new FilterBuilder($this, $id, $acronym, $operator, $value);
    }

    /**
     * @param $id
     * @param $filter
     *
     * @return $this
     *
     * @internal
     */
    public function addPredefinedFilter($id, $filter)
    {
        $this->predefinedFilters[$id] = $filter;

        return $this;
    }

    /**
     * @param $id
     *
     * @return array|null
     */
    public function getPredefinedFilter($id)
    {
        if (\array_key_exists($id, $this->predefinedFilters)) {
            return $this->predefinedFilters[$id];
        }

        return null;
    }

    /**
     * @return array
     */
    public function getFilterData()
    {
        $operators = $this->getFilterOperators();
        $values = $this->getFilterValues();

        $data = [];
        foreach ($this->getFilterColumns() as $groupIndex => $columns) {
            $group = [];

            foreach ($columns as $index => $column) {
                $group[$index] = [
                    'column' => $column,
                    'operator' => $operators[$groupIndex][$index],
                    'value' => $values[$groupIndex][$index],
                ];
            }

            $data[] = $group;
        }

        return $data;
    }

    /**
     * @return array
     */
    public function getFilterColumns()
    {
        return $this->getFromRequest('filter_column');
    }

    /**
     * @return array
     */
    public function getFilterOperators()
    {
        return $this->getFromRequest('filter_operator');
    }

    /**
     * @return array
     */
    public function getFilterValues()
    {
        return $this->getFromRequest('filter_value');
    }

    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    /**
     * @param $enabledBundles
     */
    public static function isEnabled(array $enabledBundles): bool
    {
        return true;
    }

    private static function labelCallable(DoctrineTable $table, $property)
    {
        foreach ($table->getColumns() as $column) {
            if ($column->getIdentifier() === $property) {
                return $column->getLabel() ?: ucfirst($property);
            }
        }

        return ucfirst($property);
    }

    private static function jsonSearchCallable(string $entityClass)
    {
        throw new \Exception('you need to define a json search callable for class "' . $entityClass . '".');
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     *
     * @return Filter|null
     */
    private function addFilterAutomatically(DoctrineTable $table, QueryBuilder $queryBuilder, callable $labelCallable, callable $jsonSearchCallable, \ReflectionProperty $property, string $namespace)
    {
        $acronymNoSuffix = $property->getName();
        $acronym = '_' . $property->getName();

        $label = \call_user_func($labelCallable, $table, $property->getName());

        $annotations = (new AnnotationReader())->getPropertyAnnotations($property);
        $attributes = $property->getAttributes();
        $annotationsAndAttributes = [...$annotations, ...$attributes];

        $allAliases = $queryBuilder->getAllAliases();
        $isPropertySelected = \in_array($acronym, $allAliases, true);

        $accessor = sprintf('%s.%s', $allAliases[0], $acronymNoSuffix);

        $isAttribute = fn ($x) => $x instanceof \ReflectionAttribute;
        $getClass = function ($x) use ($isAttribute) {
            if ($isAttribute($x)) {
                return $x->getName();
            }

            return get_class($x);
        };
        $getType = function ($x) use ($isAttribute) {
            if ($isAttribute($x)) {
                return $x->getArguments()['type'];
            }

            return $x->type;
        };
        $getTargetEntity = function ($x) use ($isAttribute) {
            if ($isAttribute($x)) {
                return $x->getArguments()['targetEntity'];
            }

            return $x->targetEntity;
        };

        foreach ($annotationsAndAttributes as $abstractHolder) {
            if ($getClass($abstractHolder) === Column::class) {
                if (array_key_exists($getType($abstractHolder), $this->scalarType)) {
                    $this->addFilter($acronymNoSuffix, $label, new $this->scalarType[$getType($abstractHolder)]($accessor));

                    return $this->getFilter($acronymNoSuffix);
                }

                return null;
            }

            $joins = ! $isPropertySelected ? [
                $acronym => $accessor,
            ] : [];

            if ($getClass($abstractHolder) === ManyToMany::class) {
                $target = $getTargetEntity($abstractHolder);

                $this->addFilter($acronymNoSuffix, $label, new $this->relationType[$getClass($abstractHolder)](
                    $acronym,
                    $target,
                    $this->entityManager,
                    $jsonSearchCallable($target),
                    $joins
                ));

                return $this->getFilter($acronymNoSuffix);
            }

            if ($getClass($abstractHolder) === OneToMany::class || $getClass($abstractHolder) === ManyToOne::class) {
                $target = $getTargetEntity($abstractHolder);
                if (mb_strpos($target, '\\') === false) {
                    $target = $namespace . '\\' . $target;
                }

                $filterType = $this->relationType[$getClass($abstractHolder)];

                $this->addFilter($acronymNoSuffix, $label, new $filterType(
                    $acronym,
                    $target,
                    $this->entityManager,
                    $jsonSearchCallable($target),
                    $joins
                ));

                return $this->getFilter($acronymNoSuffix);
            }
        }
    }

    private function getFromRequest(string $param)
    {
        $value = [];

        if ($this->getRequest()->query->has(RouterHelper::getParameterName($this->table, $param))) {
            $value = $this->getRequest()->query->all()[RouterHelper::getParameterName($this->table, $param)];
            if (! is_array($value)) {
                $value = [];
            }
        }

        $predefined = $this->getPredefinedFilter($this->getRequest()->query->get(RouterHelper::getParameterName($this->table, RouterHelper::PARAMETER_FILTER_PREDEFINED), ''));

        return $predefined ? array_merge($value, $predefined[$param]) : $value;
    }
}
