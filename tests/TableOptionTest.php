<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use whatwedo\TableBundle\DataLoader\ArrayDataLoader;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\Table;

class TableOptionTest extends KernelTestCase
{
    public function testFitlerOptiosTable()
    {
        /** @var TableFactory $tableFactory */
        $tableFactory = self::getContainer()->get(TableFactory::class);

        $dataLoaderOptions[ArrayDataLoader::OPTION_DATA] =
            new ArrayCollection([
                [
                    'id' => 1,
                    'name' => 'name1',
                ],
            ]);

        $table = $tableFactory->create('test', ArrayDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
        ]);

        $this->assertSame(true, $table->getOption(Table::OPTION_FILTER)[Table::OPTION_FILTER_ENABLE]);
        $this->assertSame(true, $table->getOption(Table::OPTION_FILTER)[Table::OPTION_FILTER_ADD_ALL]);

        $table->setOption(Table::OPTION_FILTER, [
            Table::OPTION_FILTER_ENABLE => false,
            Table::OPTION_FILTER_ADD_ALL => false,
        ]);

        $this->assertSame(false, $table->getOption(Table::OPTION_FILTER)[Table::OPTION_FILTER_ENABLE]);
        $this->assertSame(false, $table->getOption(Table::OPTION_FILTER)[Table::OPTION_FILTER_ADD_ALL]);

        $this->expectException(InvalidOptionsException::class);
        $table->setOption(Table::OPTION_FILTER, [
            Table::OPTION_FILTER_ENABLE => 'false',
            Table::OPTION_FILTER_ADD_ALL => 'false',
        ]);
    }
}
