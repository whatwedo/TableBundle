<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests;

use araise\TableBundle\DataLoader\ArrayDataLoader;
use araise\TableBundle\Extension\FilterExtension;
use araise\TableBundle\Factory\TableFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

class FilterExtensionOptionTest extends KernelTestCase
{
    public function testFitlerOptiosTable()
    {
        /** @var TableFactory $tableFactory */
        $tableFactory = self::getContainer()->get(TableFactory::class);

        $dataLoaderOptions[ArrayDataLoader::OPT_DATA] =
            new ArrayCollection([
                [
                    'id' => 1,
                    'name' => 'name1',
                ],
            ]);

        $table = $tableFactory->create('test', ArrayDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
        ]);

        $table->getFilterExtension()->setOption(FilterExtension::OPT_ENABLE, true);
        $table->getFilterExtension()->setOption(FilterExtension::OPT_ADD_ALL, true);

        $this->assertSame(true, $table->getFilterExtension()->getOption(FilterExtension::OPT_ENABLE));
        $this->assertSame(true, $table->getFilterExtension()->getOption(FilterExtension::OPT_ADD_ALL));

        $table->getFilterExtension()->setOption(FilterExtension::OPT_ENABLE, false);
        $table->getFilterExtension()->setOption(FilterExtension::OPT_ADD_ALL, false);

        $this->assertSame(false, $table->getFilterExtension()->getOption(FilterExtension::OPT_ENABLE));
        $this->assertSame(false, $table->getFilterExtension()->getOption(FilterExtension::OPT_ADD_ALL));

        $this->expectException(InvalidOptionsException::class);
        $table->getFilterExtension()->setOption(FilterExtension::OPT_ENABLE, 'false');
    }
}
