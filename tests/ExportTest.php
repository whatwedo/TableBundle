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
use whatwedo\TableBundle\Manager\ExportManager;
use whatwedo\TableBundle\Table\Table;
use whatwedo\TableBundle\Tests\App\Entity\Company;
use whatwedo\TableBundle\Tests\App\Factory\CompanyFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ExportTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testDoctrineDataLoderTable()
    {
        CompanyFactory::createMany(40, [
            'name' => 'company',
        ]);
        $table = $this->prepareTable();

        $table->addColumn('name')
            ->addColumn('city')
            ->addColumn('country')
            ->addColumn('taxIdentificationNumber');

        /** @var ExportManager $exportManager */
        $exportManager = self::getContainer()->get(ExportManager::class);

        $sheet = $exportManager->createSpreadsheet($table);

        $this->assertNotNull($sheet);

        // Check headers
        $this->assertSame('name', $sheet->getActiveSheet()->getCell('A1')->getValue());
        $this->assertSame('city', $sheet->getActiveSheet()->getCell('B1')->getValue());
        $this->assertSame('country', $sheet->getActiveSheet()->getCell('C1')->getValue());
        $this->assertSame('taxIdentificationNumber', $sheet->getActiveSheet()->getCell('D1')->getValue());
        $this->assertSame(null, $sheet->getActiveSheet()->getCell('E1')->getValue());

        $this->assertSame(true, $sheet->getActiveSheet()->getCell('A1')->getStyle()->getFont()->getBold());
        $this->assertSame(true, $sheet->getActiveSheet()->getCell('B1')->getStyle()->getFont()->getBold());
        $this->assertSame(true, $sheet->getActiveSheet()->getCell('C1')->getStyle()->getFont()->getBold());
        $this->assertSame(true, $sheet->getActiveSheet()->getCell('D1')->getStyle()->getFont()->getBold());

        $this->assertSame('company', $sheet->getActiveSheet()->getCell('A41')->getValue());
        $this->assertSame(null, $sheet->getActiveSheet()->getCell('A42')->getValue());
        $this->assertIsString($sheet->getActiveSheet()->getCell('B41')->getValue());
        $this->assertIsString($sheet->getActiveSheet()->getCell('C41')->getValue());
        $this->assertIsString($sheet->getActiveSheet()->getCell('D41')->getValue());
        $this->assertSame(null, $sheet->getActiveSheet()->getCell('E41')->getValue());
    }

    protected function prepareTable(): Table
    {
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

        return $table;
    }
}
