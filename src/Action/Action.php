<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Action;

use Symfony\Component\Form\Util\StringUtil;
use Symfony\Component\OptionsResolver\OptionsResolver;
use whatwedo\TableBundle\Table\Table;

class Action
{
    /**
     * it's possible to pass functions as option value to create dynamic labels, routes and more.
     * TODO: create docs.
     */
    public function __construct(
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
            'type' => 'get',
        ]);

        $this->options = $resolver->resolve($this->options);
    }

    /**
     * @deprecated
     */
    public function render(object|array $entity): string
    {
        throw new \Exception('\whatwedo\TableBundle\Action\Action::render is deprecated, use twig function whatwedo_table_render()');
    }

    public function getOption($name)
    {
        if (isset($this->options[$name])) {
            return $this->options[$name];
        }

        throw new \InvalidArgumentException(sprintf('Option "%s" for table action does not exist.', $name));
    }

    public function getRoute()
    {
        return $this->getOption('route');
    }

    public function getLabel()
    {
        return $this->getOption('label');
    }

    public function getRouteParameters($entity)
    {
        if (is_callable($this->getOption('route_parameters'))) {
            return $this->getOption('route_parameters')($entity);
        }

        return $this->getOption('route_parameters');
    }

    public function getIcon()
    {
        return $this->getOption('icon');
    }

    public function getTable(): Table
    {
        return $this->table;
    }
}
