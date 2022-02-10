<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Twig;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Helper\RouterHelper;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\Table;

class TableExtension extends AbstractExtension
{
    public function __construct(
        protected Environment $templating,
        protected RequestStack $requestStack,
        protected TableFactory $tableFactory,
        protected RouterHelper $routerHelper,
        protected RouterInterface $router,
        protected TranslatorInterface $translator
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('whatwedo_table_parameter', fn (Table $table, string $parameter, $addition = null) => $this->routerHelper->getParameterName($table->getIdentifier(), $parameter, $addition)),
            new TwigFunction('whatwedo_table_column_sort_parameters', fn (Column $column, ?string $order) => $column->getTable()->getSortExtension() ? $column->getTable()->getSortExtension()->getOrderParameters($column, $order) : []),
            /*
             * generates the same route with replaced or new arguments
             */
            new TwigFunction('whatwedo_table_path_replace_arguments', function ($arguments) {
                /** @var Request $request */
                $request = $this->requestStack->getCurrentRequest();

                $attributes = array_filter(
                    $request->attributes->all(),
                    static fn ($key) => ! str_starts_with($key, '_'),
                    ARRAY_FILTER_USE_KEY
                );

                return $this->router->generate(
                    $request->attributes->get('_route'),
                    array_replace(array_merge($attributes, $request->query->all()), $arguments)
                );
            }),
        ];
    }

    /*
     * @TODO Refacotr
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('whatwedo_table_filter_operators', function ($data) {
                foreach (array_keys($data) as $key) {
                    $data[$key] = $this->translator->trans($data[$key]);
                }

                return json_encode($data, JSON_THROW_ON_ERROR);
            }),
        ];
    }

    public function getName(): string
    {
        return 'whatwedo_table_table_extension';
    }
}
