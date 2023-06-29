<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\DataLoader\DoctrineTreeDataLoader;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Tests\App\Entity\Category;
use araise\TableBundle\Tests\App\Factory\CategoryFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TreeTableTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testTreeDataLoaderTable()
    {
        $this->initRequest();
        $this->createTestData();

        $tableFactory = self::getContainer()->get(TableFactory::class);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Category::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineTreeDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
            'default_limit' => 5,
        ]);

        $this->assertSame(
            [
                '1',
                '1.1',
                '2',
                '2.1',
                '3',
            ],
            array_map(
                fn ($data) => $data->getName(),
                iterator_to_array($table->getRows())
            )
        );
    }

    public function testTreeDataLoaderTablePage2()
    {
        $request = $this->initRequest();
        $this->createTestData();

        $request->query->set('test_page', 2);

        $tableFactory = self::getContainer()->get(TableFactory::class);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Category::class)->createQueryBuilder('c');

        $table = $tableFactory->create('test', DoctrineTreeDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
            'default_limit' => 5,
        ]);

        $this->assertSame(
            [
                '3.1',
                '4',
                '4.1',
            ],
            array_map(
                fn ($data) => $data->getName(),
                iterator_to_array($table->getRows())
            )
        );
    }

    protected function createTestData(): void
    {
        $item1 = CategoryFactory::createOne([
            'name' => '1',
        ]);
        $item2 = CategoryFactory::createOne([
            'name' => '2',
        ]);
        $item3 = CategoryFactory::createOne([
            'name' => '3',
        ]);
        $item4 = CategoryFactory::createOne([
            'name' => '4',
        ]);

        CategoryFactory::createOne([
            'name' => '1.1',
            'parent' => $item1,
        ]);
        CategoryFactory::createOne([
            'name' => '2.1',
            'parent' => $item2,
        ]);
        CategoryFactory::createOne([
            'name' => '3.1',
            'parent' => $item3,
        ]);
        CategoryFactory::createOne([
            'name' => '4.1',
            'parent' => $item4,
        ]);
    }

    protected function initRequest(): Request
    {
        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        $requestStack = self::getContainer()->get(RequestStack::class);
        $requestStack->push($fakeRequest);

        return $fakeRequest;
    }
}
