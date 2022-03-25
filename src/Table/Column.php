<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Table;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use whatwedo\CoreBundle\Formatter\DefaultFormatter;
use whatwedo\CoreBundle\Manager\FormatterManager;


class Column extends AbstractColumn implements FormattableColumnInterface
{
    public const OPTION_LABEL = 'label';

    public const OPTION_ACCESSOR_PATH = 'accessor_path';

    public const OPTION_CALLABLE = 'callable';

    public const OPTION_IS_PRIMARY = 'is_primary';

    public const OPTION_FORMATTER = 'formatter';

    public const OPTION_FORMATTER_OPTIONS = 'formatter_options';

    public const OPTION_ATTRIBUTES = 'attributes';

    public const OPTION_SORTABLE = 'sortable';

    public const OPTION_SORT_EXPRESSION = 'sort_expression';

    public const OPTION_PRIORITY = 'priority';
    public const OPTION_EXPORT = 'export';
    public const OPTION_EXPORT_EXPORTABLE = 'exportable';
    protected string $tableIdentifier;

    protected FormatterManager $formatterManager;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPTION_LABEL => $this->identifier,
            self::OPTION_ACCESSOR_PATH => $this->identifier,
            self::OPTION_CALLABLE => null,
            self::OPTION_IS_PRIMARY => false,
            self::OPTION_FORMATTER => DefaultFormatter::class,
            self::OPTION_FORMATTER_OPTIONS => [],
            self::OPTION_ATTRIBUTES => [],
            self::OPTION_SORTABLE => true,
            self::OPTION_SORT_EXPRESSION => $this->identifier,
            self::OPTION_PRIORITY => 100,
        ]);

        $resolver->setAllowedTypes(self::OPTION_PRIORITY, 'int');
        $resolver->setAllowedTypes(self::OPTION_ACCESSOR_PATH, 'string');
        $resolver->setAllowedTypes(self::OPTION_SORT_EXPRESSION, 'string');
        $resolver->setAllowedTypes(self::OPTION_IS_PRIMARY, 'boolean');
        $resolver->setAllowedTypes(self::OPTION_SORTABLE, 'boolean');


        $resolver->setDefault(self::OPTION_EXPORT, function (OptionsResolver $exportResolver) {
            $exportResolver->setDefaults([
                self::OPTION_EXPORT_EXPORTABLE => true,
            ]);

            $exportResolver->setAllowedTypes(self::OPTION_EXPORT_EXPORTABLE, 'boolean');
        });

    }

    /**
     * @deprecated
     */
    public function render($row): string
    {
        throw new \Exception('\whatwedo\TableBundle\Table\Column::render is deprecated, use twig function whatwedo_table_column_render()');
    }

    public function getContent($row)
    {
        if (is_callable($this->options[self::OPTION_CALLABLE])) {
            if (is_array($this->options[self::OPTION_CALLABLE])) {
                return call_user_func($this->options[self::OPTION_CALLABLE], [$row]);
            }

            return $this->options[self::OPTION_CALLABLE]($row);
        }

        try {
            return PropertyAccess::createPropertyAccessorBuilder()
                ->enableMagicCall()
                ->getPropertyAccessor()
                ->getValue($row, $this->options[self::OPTION_ACCESSOR_PATH])
            ;
        } catch (NoSuchPropertyException $e) {
            return $e->getMessage();
        }
    }

    /**
     * can be removed.
     */
    public function setFormatterManager(FormatterManager $formatterManager)
    {
        $this->formatterManager = $formatterManager;
    }
}
