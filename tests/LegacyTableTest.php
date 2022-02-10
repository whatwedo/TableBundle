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
use Twig\Environment;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Helper\RouterHelper;
use whatwedo\TableBundle\Model\SimpleTableData;
use whatwedo\TableBundle\Tests\App\Entity\Company;
use whatwedo\TableBundle\Tests\App\Factory\CompanyFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class LegacyTableTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testSimpleTable()
    {
        $tableFactory = self::getContainer()->get(TableFactory::class);

        $simpleData = new SimpleTableData();
        $simpleData->setResults(
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
            ])
        );
        $simpleData->setTotalResults(3);

        $table = $tableFactory->createTable('test', [
            'data_loader' => fn ($currentPage, $limit) => $simpleData,
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

    public function testDoctrineTable()
    {
        CompanyFactory::createMany(10);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $fakeRequest = Request::create('/', 'GET');

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $table = $tableFactory->createDoctrineTable('test', [
            'query_builder' => $entityManager->getRepository(Company::class)->createQueryBuilder('c'),
        ]);

        $this->assertSame(
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );
    }

    public function testDoctrineLimitTable()
    {
        CompanyFactory::createMany(10);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $fakeRequest = Request::create('/', 'GET');

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $table = $tableFactory->createDoctrineTable('test', [
            'default_limit' => 5,
            'limit_choices' => [5, 10],
            'query_builder' => $entityManager->getRepository(Company::class)->createQueryBuilder('c'),
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

    public function testRenderDoctrineTable()
    {
        CompanyFactory::createMany(10);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $fakeRequest = Request::create('/', 'GET');
        $fakeRequest->attributes->set('_route', 'dummy');

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $table = $tableFactory->createDoctrineTable('test', [
            'query_builder' => $entityManager->getRepository(Company::class)->createQueryBuilder('c'),
        ]);

        $this->assertSame(
            [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );

        $twig = self::getContainer()->get(Environment::class);

        $twig->render('table.html.twig', [
            'table' => $table,
        ]);
    }

    public function testDoctrineLimitRequestTable()
    {
        CompanyFactory::createMany(10);

        $tableFactory = self::getContainer()->get(TableFactory::class);

        $fakeRequest = Request::create('/', 'GET', [
            'test_' . RouterHelper::PARAMETER_PAGINATION_LIMIT => 2,
        ]);

        $fakeRequest->setSession(new Session(new MockArraySessionStorage()));
        /** @var RequestStack $requestStack */
        $requestStack = self::getContainer()->get(RequestStack::class);

        $requestStack->push($fakeRequest);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);

        $table = $tableFactory->createDoctrineTable('test', [
            'default_limit' => 5,
            'limit_choices' => [5, 10],
            'query_builder' => $entityManager->getRepository(Company::class)->createQueryBuilder('c'),
        ]);

        $this->assertSame(
            [1, 2],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );

        $this->assertSame(2, $table->getPaginationExtension()->getLimit());
        $this->assertSame(10, $table->getPaginationExtension()->getTotalResults());
        $this->assertSame(5, $table->getPaginationExtension()->getTotalPages());
        $this->assertSame(0, $table->getPaginationExtension()->getOffsetResults());
        $this->assertSame(1, $table->getPaginationExtension()->getCurrentPage());
    }

    public function testDoctrineLimitPage2Table()
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

        $table = $tableFactory->createDoctrineTable('test', [
            'default_limit' => 5,
            'limit_choices' => [5, 10],
            'query_builder' => $entityManager->getRepository(Company::class)->createQueryBuilder('c'),
        ]);

        $this->assertSame(
            [6, 7, 8, 9, 10],
            array_map(
                fn ($data) => $data->getId(),
                iterator_to_array($table->getRows())
            )
        );

        $this->assertSame(5, $table->getPaginationExtension()->getLimit());
        $this->assertSame(10, $table->getPaginationExtension()->getTotalResults());
        $this->assertSame(2, $table->getPaginationExtension()->getTotalPages());
        $this->assertSame(5, $table->getPaginationExtension()->getOffsetResults());
        $this->assertSame(2, $table->getPaginationExtension()->getCurrentPage());
    }
}
