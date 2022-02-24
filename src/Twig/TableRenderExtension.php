<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Twig;

use Doctrine\ORM\EntityManagerInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use whatwedo\CoreBundle\Action\Action;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\Table;

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

    private function renderTable($context, Table $table)
    {
        $this->template = $this->getTemplate($this->getTheme());
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
        $blockName = $action->getOption('block_prefix');
        $context['blockName'] = $blockName;

        return $this->template->renderBlock($blockName, $context);
    }

    private function renderTableColumn($context, Column $column, $entity)
    {
        $formatter = $this->formatterManager->getFormatter($column->getOption(Column::OPTION_FORMATTER));
        $formatter->processOptions($column->getOption(Column::OPTION_FORMATTER_OPTIONS));

        return $formatter->getHtml($column->getContent($entity));
    }

    private function isTree($entity)
    {
        return $entity instanceof \whatwedo\TableBundle\Entity\TreeInterface;
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
