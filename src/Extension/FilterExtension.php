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

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\TableBundle\Builder\FilterBuilder;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Entity\Filter as FilterEntity;
use whatwedo\TableBundle\Exception\InvalidFilterAcronymException;
use whatwedo\TableBundle\Filter\FilterGuesser;
use whatwedo\TableBundle\Filter\Type\FilterTypeInterface;
use whatwedo\TableBundle\Helper\RouterHelper;
use whatwedo\TableBundle\Table\Filter;
use whatwedo\TableBundle\Table\Table;

class FilterExtension extends AbstractExtension
{
    public const QUERY_PREDEFINED_FILTER = 'predefined_filter';

    public const OPT_ADD_ALL = 'add_all';

    public const OPT_INCLUDE_FIELDS = 'include_fields';

    public const OPT_EXCLUDE_FIELDS = 'exclude_fields';

    public const OPT_ENABLE = 'enable';

    /**
     * @var Filter[]
     */
    protected array $filters = [];

    /**
     * @var Filter[]
     */
    protected array $predefinedFilters = [];

    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected RequestStack $requestStack,
        protected FilterGuesser $filterGuesser,
        protected LoggerInterface $logger
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($this->options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPT_ADD_ALL => true,
            self::OPT_ENABLE => true,
            self::OPT_INCLUDE_FIELDS => [],
            self::OPT_EXCLUDE_FIELDS => [],
        ]);

        $resolver->setAllowedTypes(self::OPT_ADD_ALL, 'boolean');
        $resolver->setAllowedTypes(self::OPT_ENABLE, 'boolean');
        $resolver->setAllowedTypes(self::OPT_INCLUDE_FIELDS, 'string[]');
        $resolver->setAllowedTypes(self::OPT_EXCLUDE_FIELDS, 'string[]');
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
     * @return $this
     */
    public function overrideFilterName($acronym, $label)
    {
        $this->getFilter($acronym)->setName($label);

        return $this;
    }

    /**
     * @return \whatwedo\TableBundle\Entity\Filter[]
     */
    public function getSavedFilter($route)
    {
        return $this->entityManager->getRepository(FilterEntity::class)->findSaved($route);
    }

    /**
     * @throws \Doctrine\Common\Annotations\AnnotationException
     * @throws \ReflectionException
     */
    public function addFiltersAutomatically(Table $table, ?callable $labelCallable = null, ?callable $jsonSearchCallable = null, ?array $propertyNames = null)
    {
        if (! $this->getOption(self::OPT_ENABLE)) {
            return;
        }

        if ($table->getDataLoader() instanceof DoctrineDataLoader) {
            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = $table->getDataLoader()->getOption(DoctrineDataLoader::OPT_QUERY_BUILDER);
            $entityClass = $queryBuilder->getRootEntities()[0];

            $reflectionClass = new \ReflectionClass($entityClass);
            $labelCallable = \is_callable($labelCallable) ? $labelCallable : [$this, 'labelCallable'];
            $jsonSearchCallable = \is_callable($jsonSearchCallable) ? $jsonSearchCallable : [$this, 'jsonSearchCallable'];

            $properties = $propertyNames ? array_map([$reflectionClass, 'getProperty'], $propertyNames) : $reflectionClass->getProperties();

            foreach ($properties as $property) {
                if ($this->getOption(self::OPT_ADD_ALL) && in_array($property->getName(), $this->getOption(self::OPT_EXCLUDE_FIELDS), true)) {
                    continue;
                }
                if (! $this->getOption(self::OPT_ADD_ALL) && ! in_array($property->getName(), $this->getOption(self::OPT_INCLUDE_FIELDS), true)) {
                    continue;
                }
                $this->addFilterAutomatically($table, $queryBuilder, $labelCallable, $jsonSearchCallable, $property, $reflectionClass->getNamespaceName());
            }
        }
    }

    /**
     * @return FilterBuilder
     */
    public function predefineFilter($id, $acronym, $operator, $value)
    {
        return new FilterBuilder($this, $id, $acronym, $operator, $value);
    }

    /**
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
    public function getFilterData(bool $withPredefined = true)
    {
        $operators = $this->getFilterOperators($withPredefined);
        $values = $this->getFilterValues($withPredefined);

        $data = [];
        foreach ($this->getFilterColumns($withPredefined) as $groupIndex => $columns) {
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
    public function getFilterColumns(bool $withPredefined = true)
    {
        return $this->getFromRequest('filter_column', $withPredefined);
    }

    /**
     * @return array
     */
    public function getFilterOperators(bool $withPredefined = true)
    {
        return $this->getFromRequest('filter_operator', $withPredefined);
    }

    /**
     * @return array
     */
    public function getFilterValues(bool $withPredefined = true)
    {
        return $this->getFromRequest('filter_value', $withPredefined);
    }

    public function getRequest()
    {
        return $this->requestStack->getCurrentRequest();
    }

    public static function isEnabled(array $enabledBundles): bool
    {
        return true;
    }

    private static function labelCallable(Table $table, $property)
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
     * @return Filter|null
     *
     * @throws \Doctrine\Common\Annotations\AnnotationException
     */
    private function addFilterAutomatically(Table $table, QueryBuilder $queryBuilder, callable $labelCallable, callable $jsonSearchCallable, \ReflectionProperty $property, string $namespace)
    {
        $acronymNoSuffix = $property->getName();
        $acronym = '_' . $property->getName();
        $label = \call_user_func($labelCallable, $table, $property->getName());
        $allAliases = $queryBuilder->getAllAliases();
        $isPropertySelected = \in_array($acronym, $allAliases, true);
        $accessor = sprintf('%s.%s', $allAliases[0], $acronymNoSuffix);
        $joins = ! $isPropertySelected ? [
            $acronym => $accessor,
        ] : [];
        try {
            $filterType = $this->filterGuesser->getFilterType($property, $accessor, $acronym, $jsonSearchCallable, $joins, $namespace);
            if ($filterType) {
                $this->addFilter($acronymNoSuffix, $label, $filterType);

                return $this->getFilter($acronymNoSuffix);
            }
        } catch (\InvalidArgumentException $exception) {
            $this->logger->warning('could not automatically add filter for "' . $label . '"', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return null;
    }

    private function getFromRequest(string $param, bool $withPredefined = true)
    {
        $value = [];

        if ($this->getRequest()->query->has(RouterHelper::getParameterName($this->table->getIdentifier(), $param))) {
            $value = $this->getRequest()->query->all()[RouterHelper::getParameterName($this->table->getIdentifier(), $param)];
            if (! is_array($value)) {
                $value = [];
            }
        }

        if ($withPredefined) {
            $predefined = $this->getPredefinedFilter($this->getRequest()->query->get(RouterHelper::getParameterName($this->table->getIdentifier(), RouterHelper::PARAMETER_FILTER_PREDEFINED), ''));
            if (! $predefined) {
                return $value;
            }
            foreach ($value as $i => $group) {
                $value[$i] = array_merge($group, $predefined[$param][0] ?? []);
            }
            if ($value === []) {
                $value = $predefined[$param] ?? [];
            }
        }

        return $value;
    }
}
