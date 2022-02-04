<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use whatwedo\TableBundle\Factory\TableFactory;

class WiringTest extends KernelTestCase
{
    public function testServiceWiring()
    {
        foreach ([
            TableFactory::class,
        ] as $serviceClass) {
            $this->assertInstanceOf(
                $serviceClass,
                self::getContainer()->get($serviceClass)
            );
        }
    }
}
