<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests\App\Controller;

use Symfony\Component\Routing\Annotation\Route;

class DummyController
{
    #[Route(name: 'dummy')]
    public function index()
    {
    }
}
