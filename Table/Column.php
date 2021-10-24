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
    protected string $tableIdentifier;
    protected FormatterManager $formatterManager;

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => $this->identifier,
            'accessor_path' => $this->identifier,
            'callable' => null,
            'is_primary' => false,
            'formatter' => DefaultFormatter::class,
            'formatter_options' => [],
            'attributes' => [],
            'sortable' => true,
            'sort_expression' => $this->identifier,
            'priority' => 100,
        ]);
    }

    public function render($row): string
    {
        $formatter = $this->formatterManager->getFormatter($this->options['formatter']);
        $formatter->processOptions($this->options['formatter_options']);

        return $formatter->getHtml($this->getContent($row));
    }

    protected function getContent($row)
    {
        if (is_callable($this->options['callable'])) {
            if (is_array($this->options['callable'])) {
                return call_user_func($this->options['callable'], [$row]);
            }

            return $this->options['callable']($row);
        }

        try {
            return (string) (PropertyAccess::createPropertyAccessor())->getValue($row, $this->options['accessor_path']);
        } catch (NoSuchPropertyException $e) {
            return $e->getMessage();
        }
    }

    public function setFormatterManager(FormatterManager $formatterManager)
    {
        $this->formatterManager = $formatterManager;
    }
}
