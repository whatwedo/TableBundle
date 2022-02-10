<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;
use whatwedo\TableBundle\Factory\TableFactory;
use whatwedo\TableBundle\Tests\App\Entity\Company;
use whatwedo\TableBundle\Tests\App\Factory\CompanyFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class TableRenderTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

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
}
