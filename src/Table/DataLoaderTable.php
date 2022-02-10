<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Table;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Event\DataLoadEvent;

class DataLoaderTable extends Table
{
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
            'dataloader_options' => [],
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
        $resolver->setAllowedTypes('dataloader_options', ['array']);

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
        $resolver->setAllowedTypes('data_loader', ['whatwedo\TableBundle\DataLoader\DataLoaderInterface']);
    }

    /**
     * @return Column[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    public function addColumn(string $acronym, $type = null, array $options = [], ?int $position = null): static
    {
        if ($type === null) {
            $type = Column::class;
        }

        if ($this->options['definition']) {
            if (! isset($options[Column::OPTION_LABEL])) {
                $options[Column::OPTION_LABEL] = sprintf('wwd.%s.property.%s', $this->options['definition']->getEntityAlias(), $acronym);
            }
        }

        // set is_primary on first column if not set
        if (! isset($options[Column::OPTION_IS_PRIMARY]) && count($this->columns) === 0) {
            $options[Column::OPTION_IS_PRIMARY] = true;
        }

        $column = new $type($this, $acronym, $options);

        // only DoctrineTable can sort nested properties. Therefore disable them for other tables.
        if (! $this instanceof DoctrineTable
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

    protected function loadData(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::PRE_LOAD);
        $this->rows = $this->options['data_loader']->getResults();
        $this->loaded = true;
        $this->eventDispatcher->dispatch(new DataLoadEvent($this), DataLoadEvent::POST_LOAD);
    }
}
