<?php

declare(strict_types=1);
/*
 * Copyright (c) 2017, whatwedo GmbH
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

namespace araise\TableBundle\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use araise\SearchBundle\Repository\IndexRepository;
use araise\SearchBundle\whatwedoSearchBundle;
use araise\TableBundle\Event\ResultRequestEvent;

class AjaxFilterSearchListener
{
    public function __construct(
        protected EntityManagerInterface $entityManager,
        protected array $kernelBundles,
        protected IndexRepository $indexRepository
    ) {
    }

    public function searchResultSet(ResultRequestEvent $requestEvent)
    {
        // check if whatwedo serach bundle is enabled
        if (! \in_array(whatwedoSearchBundle::class, $this->kernelBundles, true)) {
            $result = new \stdClass();
            $result->items = [];
            $result->error = false;
            $requestEvent->setResult(new JsonResponse($result));

            return;
        }
        $class = $requestEvent->getEntity();
        $term = $requestEvent->getTerm();
        $result = new \stdClass();
        $result->error = true;
        if ($class !== false && $term !== false) {
            $ids = $this->indexRepository->search($term, $class);
            $queryBuilder = $requestEvent->getQueryBuilder() ?: $this->entityManager->getRepository($class)
                ->createQueryBuilder('e');
            $entities = $queryBuilder->andWhere($queryBuilder->getRootAliases()[0] . '.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult();
            $items = array_map(static function (mixed $entity) {
                $std = new \stdClass();
                $std->id = $entity->getId();
                $std->text = $entity->__toString();

                return $std;
            }, $entities);
            $result->items = $items;
            $result->error = false;
        }
        $requestEvent->setResult(new JsonResponse($result));
    }
}
