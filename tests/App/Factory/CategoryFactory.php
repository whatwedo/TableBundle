<?php

declare(strict_types=1);

namespace araise\TableBundle\Tests\App\Factory;

use araise\TableBundle\Tests\App\Entity\Category;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;
use Zenstruck\Foundry\RepositoryProxy;

/**
 * @method static         Category|Proxy createOne(array $attributes = [])
 * @method static         Category[]|Proxy[] createMany(int $number, $attributes = [])
 * @method static         Category|Proxy find($criteria)
 * @method static         Category|Proxy findOrCreate(array $attributes)
 * @method static         Category|Proxy first(string $sortedField = 'id')
 * @method static         Category|Proxy last(string $sortedField = 'id')
 * @method static         Category|Proxy random(array $attributes = [])
 * @method static         Category|Proxy randomOrCreate(array $attributes = [])
 * @method static         Category[]|Proxy[] all()
 * @method static         Category[]|Proxy[] findBy(array $attributes)
 * @method static         Category[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static         Category[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static         CategoryRepository|RepositoryProxy repository()
 * @method Category|Proxy create($attributes = [])
 */
final class CategoryFactory extends ModelFactory
{
    protected function getDefaults(): array
    {
        return [
            'name' => self::faker()->company(),
        ];
    }

    protected static function getClass(): string
    {
        return Category::class;
    }
}
