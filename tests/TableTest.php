<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Tests;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use whatwedo\TableBundle\DataLoader\ArrayDataLoader;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Helper\RouterHelper;
use whatwedo\TableBundle\Tests\App\Entity\Company;
use whatwedo\TableBundle\Tests\App\Factory\CompanyFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TableTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testArrayDataLoaderTable()
    {
        $tableFactory = self::getContainer()->get(TableFactory::class);

        $dataLoaderOptions[ArrayDataLoader::OPTION_DATA] =
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

        $table = $tableFactory->createDataLoaderTable('test', ArrayDataLoader::class, [
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

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPTION_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->createDataLoaderTable('test', DoctrineDataLoader::class, [
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

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPTION_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');

        $table = $tableFactory->createDataLoaderTable('test', DoctrineDataLoader::class, [
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

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $fakeRequest = Request::create('/', 'GET');

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPTION_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');
        $dataLoaderOptions[DoctrineDataLoader::OPTION_DEFAULT_LIMIT] = 5;

        $table = $tableFactory->createDataLoaderTable('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
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
            'test_' . RouterHelper::PARAMETER_PAGINATION_PAGE => 2,
        ]);

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPTION_QUERY_BUILDER] = $entityManager->getRepository(Company::class)->createQueryBuilder('c');
        $dataLoaderOptions[DoctrineDataLoader::OPTION_DEFAULT_LIMIT] = 5;

        $table = $tableFactory->createDataLoaderTable('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
        ]);

        $this->assertSame(
            [6, 7, 8, 9, 10],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );
    }
}
