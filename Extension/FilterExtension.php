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
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use whatwedo\TableBundle\Builder\FilterBuilder;
use whatwedo\TableBundle\Entity\Filter as FilterEntity;
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
use whatwedo\TableBundle\Table\DoctrineTable;
use whatwedo\TableBundle\Table\Filter;

/**
 * Class FilterExtension.
 */
class FilterExtension extends AbstractExtension
{
    const QUERY_PREDEFINED_FILTER = 'predefined_filter';

    /**
     * @var Registry
     */
    protected $doctrine;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var Filter[]
     */
    protected $filters = [];

    /**
     * @var Filter[]
     */
    protected $predefinedFilters = [];

    private $scalarType = [
        'string' => TextFilterType::class,
        'date' => DateFilterType::class,
        'datetime' => DatetimeFilterType::class,
        'integer' => NumberFilterType::class,
        'float' => NumberFilterType::class,
        'decimal' => NumberFilterType::class,
        'boolean' => BooleanFilterType::class,
    ];

    private $relationType = [
        OneToMany::class => AjaxOneToManyFilterType::class,
        ManyToOne::class => AjaxRelationFilterType::class,
        ManyToMany::class => AjaxManyToManyFilterType::class,
    ];

    /**
     * FilterExtension constructor.
     *
     * @param Registry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine, RequestStack $requestStack)
    {
        $this->doctrine = $doctrine;
        $this->requestStack = $requestStack;
    }

    /**
     * @return $this
     */
    public function addFilter(string $acronym, string $name, FilterTypeInterface $type)
    {
        $this->filters[$acronym] = new Filter($acronym, $name, $type);

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
        if (!isset($this->filters[$acronym])) {
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
        $name = $filter->getName();
        $column = $filter->getType()->getColumn();
        $this->filters[$acronym] = new Filter($acronym, $name, new SimpleEnumFilterType($column, [], $class));

        return $this;
    }

    /**
     * @param $username
     * @param $route
     *
     * @return \whatwedo\TableBundle\Entity\Filter[]
     */
    public function getSavedFilter($username, $route)
    {
        return $this->doctrine->getRepository(FilterEntity::class)->findSavedFilter($route, $username);
    }

    /**
     * @param callable $labelCallable
     * @param string[] $propertyNames
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function addFiltersAutomatically(DoctrineTable $table, callable $labelCallable = null, array $propertyNames = null)
    {
        $queryBuilder = $table->getQueryBuilder();
        $entityClass = $queryBuilder->getRootEntities()[0];

        $reflectionClass = new \ReflectionClass($entityClass);
        $labelCallable = \is_callable($labelCallable) ? $labelCallable : [$this, 'labelCallable'];

        $properties = $propertyNames ? array_map([$reflectionClass, 'getProperty'], $propertyNames) : $reflectionClass->getProperties();

        foreach ($properties as $property) {
            $this->addFilterAutomatically($table, $queryBuilder, $labelCallable, $property, $reflectionClass->getNamespaceName());
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
        return new FilterBuilder($id, $acronym, $operator, $value, $this);
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
     *
     * @return bool
     */
    public static function isEnabled($enabledBundles)
    {
        return true;
    }

    private static function labelCallable(DoctrineTable $table, $property)
    {
        foreach ($table->getColumns() as $column) {
            if ($column->getAcronym() === $property) {
                return $column->getLabel() ?: ucfirst($property);
            }
        }

        return ucfirst($property);
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     *
     * @return Filter|null
     */
    private function addFilterAutomatically(DoctrineTable $table, QueryBuilder $queryBuilder, callable $labelCallable, \ReflectionProperty $property, string $namespace)
    {
        $acronym = $property->getName();

        $label = \call_user_func($labelCallable, $table, $property->getName());

        $annotations = (new AnnotationReader())->getPropertyAnnotations($property);

        $allAliases = $queryBuilder->getAllAliases();
        $isPropertySelected = \in_array($acronym, $allAliases, true);

        $accessor = sprintf('%s.%s', $allAliases[0], $acronym);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof Column) {
                if (array_key_exists($annotation->type, $this->scalarType)) {
                    $this->addFilter($acronym, $label, new $this->scalarType[$annotation->type]($accessor));

                    return $this->getFilter($acronym);
                }

                return null;
            }

            if ($annotation instanceof OneToMany || $annotation instanceof ManyToOne || $annotation instanceof ManyToMany) {
                $target = $annotation->targetEntity;
                if (false === mb_strpos($target, '\\')) {
                    $target = $namespace.'\\'.$target;
                }

                $filterType = $this->relationType[\get_class($annotation)];

                $joins = !$isPropertySelected ? [$acronym => $annotation instanceof ManyToMany ? ['leftJoin', $accessor] : $accessor] : [];

                $this->addFilter($acronym, $label, new $filterType($acronym, $target, $this->doctrine, $joins));

                return $this->getFilter($acronym);
            }
        }
    }

    private function getFromRequest(string $param)
    {
        $value = $this->getRequest()->query->get($this->getActionQueryParameter($param), []);
        $predefined = $this->getPredefinedFilter($this->getRequest()->query->get($this->getActionQueryParameter(static::QUERY_PREDEFINED_FILTER), ''));

        return $predefined ? array_merge($value, $predefined[$param]) : $value;
    }
}
