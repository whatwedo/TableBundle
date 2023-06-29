<?php

declare(strict_types=1);

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;
use araise\CoreBundle\whatwedoCoreBundle;
use araise\SearchBundle\whatwedoSearchBundle;
use araise\TableBundle\whatwedoTableBundle;
use whatwedo\TwigBootstrapIcons\whatwedoTwigBootstrapIconsBundle;
use Zenstruck\Foundry\ZenstruckFoundryBundle;

return [
    FrameworkBundle::class => [
        'all' => true,
    ],
    DoctrineBundle::class => [
        'all' => true,
    ],
    TwigBundle::class => [
        'all' => true,
    ],
    ZenstruckFoundryBundle::class => [
        'all' => true,
    ],
    whatwedoCoreBundle::class => [
        'all' => true,
    ],
    whatwedoTableBundle::class => [
        'all' => true,
    ],
    whatwedoSearchBundle::class => [
        'all' => true,
    ],
    WebpackEncoreBundle::class => [
        'all' => true,
    ],
    whatwedoTwigBootstrapIconsBundle::class => [
        'all' => true,
    ],
    SecurityBundle::class => [
        'all' => true,
    ],
];
