<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests;

use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Repository\FilterRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class WiringTest extends KernelTestCase
{
    public function testServiceWiring()
    {
        foreach ([
            TableFactory::class,
            FilterRepository::class,
        ] as $serviceClass) {
            $this->assertInstanceOf(
                $serviceClass,
                self::getContainer()->get($serviceClass)
            );
        }
    }
}
