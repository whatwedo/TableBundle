<?php

declare(strict_types=1);

namespace araise\TableBundle\Twig;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Helper\RouterHelper;
use araise\TableBundle\Table\Column;
use araise\TableBundle\Table\Table;

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

    public function getName(): string
    {
        return 'whatwedo_table_table_extension';
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('whatwedo_table_parameter', fn (Table $table, string $parameter, $addition = null) => $this->routerHelper->getParameterName($table->getIdentifier(), $parameter, $addition)),
            new TwigFunction('whatwedo_table_column_sort_parameters', fn (Column $column, ?string $order) => $column->getTable()->getSortExtension() ? $column->getTable()->getSortExtension()->getOrderParameters($column, $order) : []),
            /*
             * generates the same route with replaced or new arguments
             */
            new TwigFunction('whatwedo_table_path_replace_arguments', [$this, 'pathReplaceArguments']),
        ];
    }

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

    public function pathReplaceArguments(array $arguments, bool $returnParameters = false): string|array
    {
        $request = $this->requestStack->getCurrentRequest();

        $attributes = array_filter(
            $request->attributes->all(),
            static fn ($key) => ! str_starts_with($key, '_'),
            ARRAY_FILTER_USE_KEY
        );

        $parameters = array_replace(array_merge($attributes, $request->query->all()), $arguments);
        if ($returnParameters) {
            return $this->post2Name($parameters);
        }

        return $this->router->generate(
            $request->attributes->get('_route'),
            $parameters
        );
    }

    /**
     * https://gist.github.com/richjenks/75b715bcb728290096ea
     */
    protected function post2Name(array $array): array
    {
        $result = [];
        $array = $this->flattenArray($array);
        foreach ($array as $key => $value) {
            $parts = explode('.', $key);
            $i = 0;
            $new_key = '';
            foreach ($parts as $part) {
                if ($i !== 0) {
                    $part = '[' . $part . ']';
                }
                $new_key .= $part;
                $i++;
            }
            $result[$new_key] = $value;
        }
        return $result;
    }

    /**
     * https://gist.github.com/richjenks/75b715bcb728290096ea
     */
    protected function flattenArray($array, $prefix = '')
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = $result + $this->flattenArray($value, $prefix . $key . '.');
            } else {
                $result[$prefix . $key] = $value;
            }
        }
        return $result;
    }
}
