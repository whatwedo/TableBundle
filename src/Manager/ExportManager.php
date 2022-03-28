<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Manager;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;
use whatwedo\CoreBundle\Manager\FormatterManager;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\Table;

class ExportManager
{
    private array $reports = [];

    public function __construct(
        protected FormatterManager    $formatterManager,
        protected TranslatorInterface $translator
    )
    {
    }

    public function createSpreadsheet(Table $table): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $tableColumns = $table->getColumns();
        $this->createHeader($sheet, $tableColumns);

        $this->createTable($sheet, $table, $tableColumns);

        for ($index = 1, $indexMax = count($tableColumns); $index < $indexMax; ++$index) {
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
            $sheet->getColumnDimensionByColumn($index)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * @param Column[] $tableColumns
     */
    protected function createHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, array $tableColumns): void
    {
        foreach ($tableColumns as $colIndex => $column) {
            if ($column->getOption(Column::OPTION_EXPORT)[Column::OPTION_EXPORT_EXPORTABLE] === false) {
                continue;
            }

            $value = '';
            if ($column->getOption(Column::OPTION_LABEL)) {
                $value = $this->translator->trans(
                    $column->getOption(Column::OPTION_LABEL)
                );
            }

            $columnIndex = $sheet->getColumnDimensionByColumn($colIndex + 1)->getColumnIndex();
            $cell = $sheet->getCell($columnIndex . '1');
            $cell->setValueExplicit($value, DataType::TYPE_STRING2);
            $cell->getStyle()->getFont()->setBold(true);
        }
    }

    /**
     * @param Column[] $tableColumns
     */
    protected function createTable(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, Table $table, array $tableColumns): void
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        foreach ($table->getRows() as $rowIndex => $row) {
            $colIndex = 1;
            foreach ($tableColumns as $column) {
                if ($column->getOption(Column::OPTION_EXPORT)[Column::OPTION_EXPORT_EXPORTABLE] === false) {
                    continue;
                }
                $data = $propertyAccessor->getValue($row, $column->getOption(Column::OPTION_ACCESSOR_PATH));
                if ($column->getOption(Column::OPTION_FORMATTER)) {

                    if (is_callable($column->getOption(Column::OPTION_FORMATTER))) {
                        $formatter = $column->getOption(Column::OPTION_FORMATTER);
                        $data = $formatter($data);
                    } else {
                        $formatter = $this->formatterManager->getFormatter($column->getOption(Column::OPTION_FORMATTER));
                        $formatter->processOptions($column->getOption(Column::OPTION_FORMATTER_OPTIONS));
                        $data = $formatter->getString($data);
                    }
                }
                $columnIndex = $sheet->getColumnDimensionByColumn($colIndex)->getColumnIndex();
                $cell = $sheet->getCell($columnIndex . ($rowIndex + 2));

                $cell->setValueExplicit($data, DataType::TYPE_STRING2);
                $colIndex++;
            }
        }
    }
}
