<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests;

use araise\TableBundle\DataLoader\ArrayDataLoader;
use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Helper\RouterHelper;
use araise\TableBundle\Tests\App\Entity\Company;
use araise\TableBundle\Tests\App\Factory\CompanyFactory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TableTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testArrayDataLoaderTable()
    {
        $tableFactory = self::getContainer()->get(TableFactory::class);

        $dataLoaderOptions[ArrayDataLoader::OPT_DATA] =
            new ArrayCollection([
                [
                    'id' => 1,
                    'name' => 'name1',
                ],
                [
                    'id' => 2,
                    'name' => 'name2',
                ],
                [
                    'id' => 3,
                    'name' => 'name3',
                ],
            ]);

        $table = $tableFactory->create('test', ArrayDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
        ]);

        $this->assertSame([
            [
                'id' => 1,
                'name' => 'name1',
            ],
            [
                'id' => 2,
                'name' => 'name2',
            ],
            [
                'id' => 3,
                'name' => 'name3',
            ],
        ], iterator_to_array($table->getRows()->getIterator()));
    }

    public function testDoctrineDataLoderTable()
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

        $this->assertSame(
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );
    }

    public function testDoctrineDataLoderTableNoLimit()
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
            'default_limit' => 0,
        ]);

        $this->assertSame(
            [
                1, 2, 3, 4, 5, 6, 7, 8, 9, 10,
                11, 12, 13, 14, 15, 16, 17, 18, 19, 20,
                21, 22, 23, 24, 25, 26, 27, 28, 29, 30,
                31, 32, 33, 34, 35, 36, 37, 38, 39, 40,
            ],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );
    }

    public function testDoctrineDataLoderPaginateTable()
    {
        CompanyFactory::createMany(10);

        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = self::getContainer()->get(RequestStack::class);
        $requestStack->push($fakeRequest);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
            'default_limit' => 5,
        ]);

        $this->assertSame(
            [1, 2, 3, 4, 5],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );

        $this->assertSame(5, $table->getPaginationExtension()->getLimit());
        $this->assertSame(10, $table->getPaginationExtension()->getTotalResults());
        $this->assertSame(2, $table->getPaginationExtension()->getTotalPages());
        $this->assertSame(0, $table->getPaginationExtension()->getOffsetResults());
        $this->assertSame(1, $table->getPaginationExtension()->getCurrentPage());
    }

    public function testDoctrineDataLoderPaginatePage2Table()
    {
        CompanyFactory::createMany(10);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $fakeRequest = Request::create('/', 'GET', [
            'test_'.RouterHelper::PARAMETER_PAGINATION_PAGE => 2,
        ]);

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
            'default_limit' => 5,
        ]);

        $this->assertSame(
            [6, 7, 8, 9, 10],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );
    }

    public function testDoctrineDataLoderPaginateTableSorting()
    {
        CompanyFactory::createOne([
            'name' => 'aaa',
        ]);
        CompanyFactory::createOne([
            'name' => 'zzz',
        ]);
        CompanyFactory::createOne([
            'name' => 'ccc',
        ]);
        CompanyFactory::createOne([
            'name' => 'yyy',
        ]);
        CompanyFactory::createOne([
            'name' => 'ddd',
        ]);
        CompanyFactory::createOne([
            'name' => 'xxx',
        ]);
        CompanyFactory::createOne([
            'name' => 'eee',
        ]);
        CompanyFactory::createOne([
            'name' => 'uuu',
        ]);
        CompanyFactory::createOne([
            'name' => 'fff',
        ]);
        CompanyFactory::createOne([
            'name' => 'vvv',
        ]);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $fakeRequest = Request::create('/', 'GET', [
            'test_sort_direction_name' => 'asc',
            'test_sort_enabled_name' => 1,
        ]);

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
        ]);

        $table->addColumn('name');

        $this->assertSame(
            [
                'aaa',
                'ccc',
                'ddd',
                'eee',
                'fff',
                'uuu',
                'vvv',
                'xxx',
                'yyy',
                'zzz',
            ],
            array_map(
                fn ($data) => $data->getName(),
                iterator_to_array($table->getRows())
            )
        );
    }
}
