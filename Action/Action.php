<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Action;

use InvalidArgumentException;
use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Twig\Environment;
use whatwedo\TableBundle\Table\Table;

class Action
{
    /**
     * it's possible to pass functions as option value to create dynamic labels, routes and more.
     * TODO: create docs
     */
    public function __construct(
        protected Environment $templating,
        protected Table $table,
        protected $acronym,
        protected array $options
    ) {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'label' => null,
            'attr' => null,
            'icon' => null,
            'route' => null,
            'route_parameters' => [],
            'voter_attribute' => null,
            'block_prefix' => StringUtil::fqcnToBlockPrefix(static::class),
            'priority' => 0,
        ]);

        $this->options = $resolver->resolve($this->options);
    }

    public function render(object|array $row): string
    {
        return $this->templating
            ->load($this->table->getOption('theme'))
            ->renderBlock('table_action', array_map(
                static fn($value) => is_callable($value) ? $value($row) : $value,
                array_merge($this->options, [
                    'row' => $row,
                ])
            ));
    }

    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        throw new InvalidArgumentException(sprintf('Option "%s" for table action does not exist.', $name));
    }

    public function getTable(): Table
    {
        return $this->table;
    }
}
