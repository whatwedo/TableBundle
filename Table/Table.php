<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Table;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Action\Action;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Exception\DataLoaderNotAvailableException;
use whatwedo\TableBundle\Extension\ExtensionInterface;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Extension\PaginationExtension;
use whatwedo\TableBundle\Extension\SearchExtension;
use whatwedo\TableBundle\Extension\SortExtension;
use whatwedo\TableBundle\Model\TableDataInterface;

class Table
{
    protected array $columns = [];
    protected array $actions = [];
    protected \Traversable $rows;
    protected bool $loaded = false;

    public function __construct(
        protected string $identifier,
        protected array $options,
        protected EventDispatcherInterface $eventDispatcher,
        protected Environment $templating,
        protected FormatterManager $formatterManager,
        protected array $extensions,
        protected RequestStack $requestStack
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($this->options);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'title' => null,
            'primary_link' => null,
            'attributes' => [],
            'searchable' => false,
            'sortable' => true,
            'default_sort' => [],
            'default_limit' => 25,
            'limit_choices' => [25, 50, 100, 200, 500],
            'theme' => '@whatwedoTable/tailwind_2_layout.html.twig',
            'definition' => null,
        ]);

        $resolver->setAllowedTypes('title', ['null', 'string']);
        $resolver->setAllowedTypes('primary_link', ['null', 'callable']);
        $resolver->setAllowedTypes('attributes', ['array']);
        $resolver->setAllowedTypes('searchable', ['boolean']);
        $resolver->setAllowedTypes('default_limit', ['integer']);
        $resolver->setAllowedTypes('theme', ['string']);
        $resolver->setAllowedTypes('limit_choices', ['array']);
        $resolver->setAllowedTypes('default_sort', ['array']);
        $resolver->setAllowedTypes('definition', ['null', 'object']);

        /*
         * Data Loader
         * ---
         * Callable Arguments:
         *  - Current Page
         *  - Limit of results (-1 means all results)
         *
         * The callable must return a TableDataInterface.
         * you can pass an array to call_user_func
         */
        $resolver->setRequired('data_loader');
        $resolver->setAllowedTypes('data_loader', ['array', 'callable']);
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getOption(string $key, ...$args)
    {
        return $this->options[$key] ?? null;
    }

    public function getPrimaryLink(object|array $row)
    {
        return is_callable($this->options['primary_link']) ? $this->options['primary_link']($row) : null;
    }

    public function setOption(string $key, $value): static
    {
        $this->options[$key] = $value;

        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function addColumn(string $acronym, $type = null, array $options = []): static
    {
        if ($type === null) {
            $type = Column::class;
        }

        if ($this->options['definition']) {
            if (!isset($options['label'])) {
                $options['label'] = sprintf('%s.%s', $this->options['definition']->getPrefix(), $acronym);
            }
        }

        $column = new $type($this, $acronym, $options);

        // only DoctrineTable can sort nested properties. Therefore disable them for other tables.
        if (! $this instanceof DoctrineTable
            && $column->getOption('sortable')
            && str_contains($column->getOption('sort_expression'), '.')) {
            $column->setOption('sortable', false);
        }

        if ($column instanceof FormattableColumnInterface) {
            $column->setFormatterManager($this->formatterManager);
        }

        $this->columns[$acronym] = $column;

        return $this;
    }

    public function removeColumn($acronym): static
    {
        unset($this->columns[$acronym]);

        return $this;
    }

    /**
     * @return Action[]
     */
    public function getActions(): array
    {
        uasort(
            $this->actions,
            static fn(Action $a, Action $b) => $a->getOption('priority') <=> $b->getOption('priority')
        );
        return $this->actions;
    }

    public function addAction(string $acronym, array $options = []): static
    {
        $this->actions[$acronym] = new Action($this->templating, $this, $acronym, $options);

        return $this;
    }

    public function getRows(): \Traversable
    {
        return $this->rows;
    }

    public function loadData(): void
    {
        if ($this->loaded) {
            return;
        }

        if (! is_callable($this->options['data_loader'])
            && ! is_array($this->options['data_loader'])) {
            throw new DataLoaderNotAvailableException();
        }

        $currentPage = 1;
        $limit = -1;

        $paginationExtension = null;

        if ($this->hasExtension(PaginationExtension::class)) {
            /** @var PaginationExtension $paginationExtension */
            $paginationExtension = $this->getExtension(PaginationExtension::class);
            $paginationExtension->setLimit($this->options['default_limit']);
            $currentPage = $paginationExtension->getCurrentPage();
            $limit = $paginationExtension->getLimit();
        }

        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::PRE_LOAD);

        // loads the data from the data loader callable
        $tableData = null;

        if (is_callable($this->options['data_loader'])) {
            $tableData = ($this->options['data_loader'])($currentPage, $limit);
        }
        if (is_array($this->options['data_loader'])) {
            $tableData = call_user_func($this->options['data_loader'], $currentPage, $limit);
        }

        if (! $tableData instanceof TableDataInterface) {
            throw new \UnexpectedValueException('Table::dataLoader must return a TableDataInterface');
        }

        $this->rows = $tableData->getResults();

        if ($this->hasExtension(PaginationExtension::class)) {
            $paginationExtension->setTotalResults($tableData->getTotalResults());
        }

        $this->loaded = true;

        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::POST_LOAD);
    }

    public function render(): string
    {
        $this->loadData();

        return $this->templating
            ->load($this->getOption('theme'))
            ->renderBlock('table', [
                'table' => $this,
            ]);
    }

    public function getExtension(string $extension): ExtensionInterface
    {
        if (! $this->hasExtension($extension)) {
            throw new \InvalidArgumentException(sprintf('Extension %s is not enabled. Please configure it first.', $extension));
        }

        return $this->extensions[$extension]->setTable($this);
    }

    public function removeExtension(string $extension): void
    {
        unset($this->extensions[$extension]);
    }

    public function getSearchExtension(): ?SearchExtension
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasExtension(SearchExtension::class)
            ? $this->getExtension(SearchExtension::class)
            : null;
    }

    public function getPaginationExtension(): ?PaginationExtension
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasExtension(PaginationExtension::class)
            ? $this->getExtension(PaginationExtension::class)
            : null;
    }

    public function getFilterExtension(): ?FilterExtension
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasExtension(FilterExtension::class)
            ? $this->getExtension(FilterExtension::class)
            : null;
    }

    public function getSortExtension(): ?SortExtension
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->hasExtension(SortExtension::class)
            ? $this->getExtension(SortExtension::class)
            : null;
    }

    public function hasExtension(string $extension): bool
    {
        return \array_key_exists($extension, $this->extensions);
    }
}
