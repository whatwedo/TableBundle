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
    public const OPT_LABEL = 'label';

    public const OPT_ACCESSOR_PATH = 'accessor_path';

    public const OPT_CALLABLE = 'callable';

    public const OPT_IS_PRIMARY = 'is_primary';

    public const OPT_FORMATTER = 'formatter';

    public const OPT_FORMATTER_OPTIONS = 'formatter_options';

    public const OPT_ATTRIBUTES = 'attributes';

    public const OPT_SORTABLE = 'sortable';

    public const OPT_SORT_EXPRESSION = 'sort_expression';

    public const OPT_PRIORITY = 'priority';

    public const OPT_EXPORT = 'export';

    public const OPT_EXPORT_EXPORTABLE = 'exportable';

    protected FormatterManager $formatterManager;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            self::OPT_LABEL => $this->identifier,
            self::OPT_ACCESSOR_PATH => $this->identifier,
            self::OPT_CALLABLE => null,
            self::OPT_IS_PRIMARY => false,
            self::OPT_FORMATTER => DefaultFormatter::class,
            self::OPT_FORMATTER_OPTIONS => [],
            self::OPT_ATTRIBUTES => [],
            self::OPT_SORTABLE => true,
            self::OPT_SORT_EXPRESSION => $this->identifier,
            self::OPT_PRIORITY => 100,
        ]);

        $resolver->setAllowedTypes(self::OPT_PRIORITY, 'int');
        $resolver->setAllowedTypes(self::OPT_ACCESSOR_PATH, 'string');
        $resolver->setAllowedTypes(self::OPT_SORT_EXPRESSION, 'string');
        $resolver->setAllowedTypes(self::OPT_IS_PRIMARY, 'boolean');
        $resolver->setAllowedTypes(self::OPT_SORTABLE, 'boolean');

        $resolver->setDefault(self::OPT_EXPORT, function (OptionsResolver $exportResolver) {
            $exportResolver->setDefaults([
                self::OPT_EXPORT_EXPORTABLE => true,
            ]);

            $exportResolver->setAllowedTypes(self::OPT_EXPORT_EXPORTABLE, 'boolean');
        });
    }

    public function getContent($row)
    {
        if (is_callable($this->options[self::OPT_CALLABLE])) {
            if (is_array($this->options[self::OPT_CALLABLE])) {
                return call_user_func($this->options[self::OPT_CALLABLE], [$row]);
            }

            return $this->options[self::OPT_CALLABLE]($row);
        }

        try {
            return PropertyAccess::createPropertyAccessorBuilder()
                ->enableMagicCall()
                ->getPropertyAccessor()
                ->getValue($row, $this->options[self::OPT_ACCESSOR_PATH])
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
