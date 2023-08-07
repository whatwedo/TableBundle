<?php

declare(strict_types=1);

/*
 * Copyright (c) 2023, whatwedo GmbH
 * All rights reserved
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 *    this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED.
 * IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
 * INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT
 * NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR
 * PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY,
 * WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace araise\TableBundle\Tests;

use araise\TableBundle\DataLoader\DoctrineDataLoader;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Tests\App\Entity\Category;
use araise\TableBundle\Tests\App\Factory\CategoryFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class DoctrineDataLoaderTest extends KernelTestCase
{
    use ResetDatabase;
    use Factories;

    protected DoctrineDataLoader $dataLoader;

    /**
     * @before
     */
    public function setupTest(): void
    {
        $tableFactory = self::getContainer()->get(TableFactory::class);
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        CategoryFactory::createMany(10);

        $dataLoaderOptions[DoctrineDataLoader::OPT_QUERY_BUILDER] = $entityManager->getRepository(Category::class)->createQueryBuilder('c');

        $this->dataLoader = $tableFactory->create('test', DoctrineDataLoader::class, [
            'dataloader_options' => $dataLoaderOptions,
        ])->getDataLoader();
    }

    public function testNextPrev(): void
    {
        $data = [];
        foreach (CategoryFactory::repository()->createQueryBuilder('c')->getQuery()->getResult() as $category) {
            $data[] = $category;
            if (count($data) === 3) {
                break;
            }
        }

        // all null as no session
        self::assertNull($this->dataLoader->getPrev($data[0]));
        self::assertNull($this->dataLoader->getPrev($data[1]));
        self::assertNull($this->dataLoader->getPrev($data[2]));
    }
}
