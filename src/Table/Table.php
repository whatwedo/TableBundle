<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Table;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CoreBundle\Action\Action;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\DataLoader\DataLoaderInterface;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Extension\ExtensionInterface;
use whatwedo\TableBundle\Extension\FilterExtension;
use whatwedo\TableBundle\Extension\PaginationExtension;
use whatwedo\TableBundle\Extension\SearchExtension;
use whatwedo\TableBundle\Extension\SortExtension;

class Table
{
    public const OPTION_TITLE = 'title';

    public const OPTION_PRIMARY_LINK = 'primary_link';

    public const OPTION_ATTRIBUTES = 'attributes';

    public const OPTION_SEARCHABLE = 'searchable';

    public const OPTION_SORTABLE = 'sortable';

    public const OPTION_DEFAULT_SORT = 'default_sort';

    public const OPTION_DEFAULT_LIMIT = 'default_limit';

    public const OPTION_LIMIT_CHOICES = 'limit_choices';

    public const OPTION_THEME = 'theme';

    public const OPTION_DEFINITION = 'definition';

    public const OPTION_DATALOADER_OPTIONS = 'dataloader_options';

    public const OPTION_DATA_LOADER = 'data_loader';

    protected array $columns = [];

    protected array $actions = [];

    protected \Traversable $rows;

    protected bool $loaded = false;

    public function __construct(
        protected string $identifier,
        protected array $options,
        protected EventDispatcherInterface $eventDispatcher,
        protected array $extensions,
        protected FormatterManager $formatterManager
    ) {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($this->options);
        foreach ($this->extensions as $extension) {
            $extension->setTable($this);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPTION_TITLE => null,
            self::OPTION_PRIMARY_LINK => null,
            self::OPTION_ATTRIBUTES => [],
            self::OPTION_SEARCHABLE => false,
            self::OPTION_SORTABLE => true,
            self::OPTION_DEFAULT_SORT => [],
            self::OPTION_DEFAULT_LIMIT => 25,
            self::OPTION_LIMIT_CHOICES => [25, 50, 100, 200, 500],
            self::OPTION_THEME => '@whatwedoTable/tailwind_2_layout.html.twig',
            self::OPTION_DEFINITION => null,
            self::OPTION_DATALOADER_OPTIONS => [],
        ]);

        $resolver->setAllowedTypes(self::OPTION_TITLE, ['null', 'string']);
        $resolver->setAllowedTypes(self::OPTION_PRIMARY_LINK, ['null', 'callable']);
        $resolver->setAllowedTypes(self::OPTION_ATTRIBUTES, ['array']);
        $resolver->setAllowedTypes(self::OPTION_SEARCHABLE, ['boolean']);

        $resolver->setAllowedTypes(self::OPTION_DEFAULT_LIMIT, ['integer']);

        $resolver->setAllowedTypes(self::OPTION_THEME, ['string']);
        $resolver->setAllowedTypes(self::OPTION_LIMIT_CHOICES, ['array']);
        $resolver->setAllowedTypes(self::OPTION_DEFAULT_SORT, ['array']);
        $resolver->setAllowedTypes(self::OPTION_DEFINITION, ['null', 'object']);

        $resolver->setRequired(self::OPTION_DATA_LOADER);
        $resolver->setAllowedTypes(self::OPTION_DATA_LOADER, allowedTypes: DataLoaderInterface::class);

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
        return is_callable($this->options[self::OPTION_PRIMARY_LINK]) ? $this->options[self::OPTION_PRIMARY_LINK]($row) : null;
    }

    public function setOption(string $key, $value): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->options[$key] = $value;
        $this->options = $resolver->resolve($this->options);

        return $this;
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        usort($this->columns, fn (Column $a, Column $b) => $b->getOption(Column::OPTION_PRIORITY) <=> $a->getOption(Column::OPTION_PRIORITY));

        return $this->columns;
    }

    public function addColumn(string $acronym, $type = null, array $options = [], ?int $position = null): static
    {
        if ($type === null) {
            $type = Column::class;
        }

        if ($this->options[self::OPTION_DEFINITION]) {
            if (! isset($options[Column::OPTION_LABEL])) {
                $options[Column::OPTION_LABEL] = sprintf('wwd.%s.property.%s', $this->options[self::OPTION_DEFINITION]->getEntityAlias(), $acronym);
            }
        }

        // set is_primary on first column if not set
        if (! isset($options[Column::OPTION_IS_PRIMARY]) && count($this->columns) === 0) {
            $options[Column::OPTION_IS_PRIMARY] = true;
        }

        $column = new $type($this, $acronym, $options);

        // only DoctrineTable can sort nested properties. Therefore disable them for other tables.
        // TODO: refactor
        if (! $this->options[self::OPTION_DATA_LOADER] instanceof DoctrineDataLoader
            && $column->getOption(Column::OPTION_SORTABLE)
            && str_contains($column->getOption(Column::OPTION_SORT_EXPRESSION), '.')) {
            $column->setOption(Column::OPTION_SORTABLE, false);
        }

        if ($column instanceof FormattableColumnInterface) {
            $column->setFormatterManager($this->formatterManager);
        }

        if ($position === null) {
            $this->columns[$acronym] = $column;
        } else {
            $this->insertColumnAtPosition($acronym, $column, $position);
        }

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
            static fn (Action $a, Action $b) => $a->getOption('priority') <=> $b->getOption('priority')
        );

        return $this->actions;
    }

    public function addAction(string $acronym, array $options = [], $type = Action::class): static
    {
        $this->actions[$acronym] = new $type($acronym, $options);

        return $this;
    }

    public function getRows(): \Traversable
    {
        $this->loadData();

        return $this->rows;
    }

    /**
     * @deprecated  use twig function whatwedo_table_render()
     */
    public function render(): string
    {
        throw new \Exception('\whatwedo\TableBundle\Table\Table::render is deprecated, use twig function whatwedo_table_render()');
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

    public function getDataLoader(): DataLoaderInterface
    {
        return $this->options[self::OPTION_DATA_LOADER];
    }

    protected function loadData(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::PRE_LOAD);
        $this->rows = $this->options[self::OPTION_DATA_LOADER]->getResults();
        $this->loaded = true;
        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::POST_LOAD);
    }

    protected function insertColumnAtPosition($key, $value, $position)
    {
        $newArray = [];
        $added = false;
        $i = 0;
        foreach ($this->columns as $elementsAcronym => $elementsElement) {
            if ($position === $i) {
                $newArray[$key] = $value;
                $added = true;
            }
            $newArray[$elementsAcronym] = $elementsElement;
            ++$i;
        }
        if (! $added) {
            $newArray[$key] = $value;
        }
        $this->columns = $newArray;
    }
}
