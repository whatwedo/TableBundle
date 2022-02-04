<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Twig;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Action\Action;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\Table;

class TableRenderExtension extends AbstractExtension
{
    public function __construct(
        protected Environment $templating,
        protected FormatterManager $formatterManager
    ) {
    }

    public function getFunctions(): array
    {
        $options = [
            'needs_context' => true,
            'is_safe' => ['html'],
            'is_safe_callback' => true,
            'blockName' => 'blockName',
        ];

        return [
            new TwigFunction('whatwedo_table_render', fn ($context, Table $table) => $this->renderTable($context, $table), $options),
            new TwigFunction('whatwedo_table_action_render', fn ($context, Action $action, $entity) => $this->renderTableAction($context, $action, $entity), $options),
            new TwigFunction('whatwedo_table_column_render', fn ($context, Column $column, $entity) => $this->renderTableColumn($context, $column, $entity), $options),
        ];
    }

    private function renderTable($context, Table $table)
    {
        $this->template = $this->getTemplate($this->getTheme());
        $table->loadData();
        $context['table'] = $table;
        $blockName = 'table';
        $context['blockName'] = $blockName;

        return $this->template->renderBlock($blockName, $context);
    }

    private function renderTableAction($context, Action $action, $entity)
    {
        $this->template = $this->getTemplate($this->getTheme());
        $context['action'] = $action;
        $context['entity'] = $entity;
        $blockName = 'table_action';
        $context['blockName'] = $blockName;

        return $this->template->renderBlock($blockName, $context);
    }

    private function renderTableColumn($context, Column $column, $entity)
    {
        $formatter = $this->formatterManager->getFormatter($column->getOption(Column::OPTION_FORMATTER));
        $formatter->processOptions($column->getOption(Column::OPTION_FORMATTER_OPTIONS));

        return $formatter->getHtml($column->getContent($entity));
    }

    private function getTemplate(string $layoutFile): \Twig\TemplateWrapper
    {
        return $this->templating->load($layoutFile);
    }

    private function getTheme(): string
    {
        //TODO get this from some where central..
        return '@whatwedoTable/tailwind_2_layout.html.twig';
    }
}
