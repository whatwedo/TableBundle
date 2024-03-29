<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Tests;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use whatwedo\TableBundle\Tests\App\Entity\Category;
use whatwedo\TableBundle\Tests\App\Factory\CategoryFactory;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class HierarchicalEntityTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    public function testCreateEntity()
    {
        /** @var Category $category */
        $category = CategoryFactory::createOne([
            'name' => 'Level 1',
        ])->object();

        /** @var Category $subcategory */
        $subcategory = CategoryFactory::createOne([
            'name' => 'Level 2',
            'parent' => $category,
        ])->object();

        $this->assertSame($category, $subcategory->getParent());
        $this->assertSame(0, $category->getLevel());
        $this->assertSame(1, $subcategory->getLevel());
        $this->assertCount(1, $category->getChildren());

        $subcategory->setName('Level Zwei');
        self::getContainer()->get(EntityManagerInterface::class)->flush();
    }
}
