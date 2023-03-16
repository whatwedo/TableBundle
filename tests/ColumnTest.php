<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Table\Column;
use whatwedo\TableBundle\Table\Table;
use whatwedo\TableBundle\Tests\App\Entity\Company;
use whatwedo\TableBundle\Tests\App\Factory\CompanyFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ColumnTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testColumnPriorityDefault()
    {
        $table = $this->prepareTable();

        $table->addColumn('name')
            ->addColumn('city')
            ->addColumn('country')
            ->addColumn('taxIdentificationNumber')
        ;

        $columns = $table->getColumns();

        $this->assertSame('name', $columns[0]->getIdentifier());
        $this->assertSame('city', $columns[1]->getIdentifier());
        $this->assertSame('country', $columns[2]->getIdentifier());
        $this->assertSame('taxIdentificationNumber', $columns[3]->getIdentifier());
    }

    public function testColumnPriorityReorder()
    {
        $table = $this->prepareTable();

        $table->addColumn('name', null, [
            Column::OPT_PRIORITY => 1,
        ])
            ->addColumn('city', null, [
                Column::OPT_PRIORITY => 2,
            ])
            ->addColumn('country', null, [
                Column::OPT_PRIORITY => 3,
            ])
            ->addColumn('taxIdentificationNumber', null, [
                Column::OPT_PRIORITY => 4,
            ])
        ;

        $columns = $table->getColumns();

        $this->assertSame('taxIdentificationNumber', $columns[0]->getIdentifier());
        $this->assertSame('country', $columns[1]->getIdentifier());
        $this->assertSame('city', $columns[2]->getIdentifier());
        $this->assertSame('name', $columns[3]->getIdentifier());
    }

    public function testColumnPrioritySameOrder()
    {
        $table = $this->prepareTable();

        $table->addColumn('name', null, [
            Column::OPT_PRIORITY => 1,
        ])
            ->addColumn('city', null, [
                Column::OPT_PRIORITY => 2,
            ])
            ->addColumn('country', null, [
                Column::OPT_PRIORITY => 2,
            ])
            ->addColumn('taxIdentificationNumber', null, [
                Column::OPT_PRIORITY => 4,
            ])
        ;

        $columns = $table->getColumns();

        $this->assertSame('taxIdentificationNumber', $columns[0]->getIdentifier());
        $this->assertSame('city', $columns[1]->getIdentifier());
        $this->assertSame('country', $columns[2]->getIdentifier());
        $this->assertSame('name', $columns[3]->getIdentifier());
    }

    protected function prepareTable(): Table
    {
        CompanyFactory::createMany(40);

        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = self::getContainer()->get(RequestStack::class);
        $requestStack->push($fakeRequest);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
            'default_limit' => 10,
        ]);

        return $table;
    }
}
