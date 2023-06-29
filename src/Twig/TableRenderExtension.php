<?php

declare(strict_types=1);

namespace araise\TableBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TemplateWrapper;
use Twig\TwigFilter;
use Twig\TwigFunction;
use araise\CoreBundle\Action\Action;
use araise\CoreBundle\Manager\FormatterManager;
use araise\TableBundle\Entity\TreeInterface;
use araise\TableBundle\Table\Column;
use araise\TableBundle\Table\Table;

class TableRenderExtension extends AbstractExtension
{
    public function __construct(
        protected Environment $templating,
        protected FormatterManager $formatterManager,
        protected EntityManagerInterface $entityManager
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
            new TwigFunction('whatwedo_table_only_render', fn ($context, Table $table) => $this->renderTable($context, $table, 'table_table'), $options),
            new TwigFunction('whatwedo_table_action_render', fn ($context, Action $action, $entity) => $this->renderTableAction($context, $action, $entity), $options),
            new TwigFunction('whatwedo_table_column_render', fn ($context, Column $column, $entity) => $this->renderTableColumn($context, $column, $entity), $options),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('whatwedo_entity_is_tree', fn ($entity) => $this->isTree($entity)),
        ];
    }

    private function renderTable($context, Table $table, string $blockName = 'table')
    {
        $template = $this->getTemplate($this->getTheme($context));
        $context['table'] = $table;
        $context['blockName'] = $blockName;

        return $template->renderBlock($blockName, $context);
    }

    private function renderTableAction($context, Action $action, $entity)
    {
        $template = $this->getTemplate($this->getTheme($context));
        $context['action'] = $action;
        $context['entity'] = $entity;
        $blockName = $action->getOption('block_prefix');
        $context['blockName'] = $blockName;

        return $template->renderBlock($blockName, $context);
    }

    private function renderTableColumn($context, Column $column, $entity)
    {
        $formatter = $this->formatterManager->getFormatter($column->getOption(Column::OPT_FORMATTER));
        $formatter->processOptions($column->getOption(Column::OPT_FORMATTER_OPTIONS));

        return $formatter->getHtml($column->getContent($entity));
    }

    private function isTree($entity)
    {
        return $entity instanceof TreeInterface;
    }

    private function getTemplate(string $layoutFile): TemplateWrapper
    {
        return $this->templating->load($layoutFile);
    }

    private function getTheme(array $context): string
    {
        if (isset($context['table']) && $context['table'] instanceof Table) {
            return $context['table']->getOption(Table::OPT_THEME);
        }

        return '@whatwedoTable/tailwind_2_layout.html.twig';
    }
}
