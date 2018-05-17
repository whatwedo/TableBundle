<?php
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

namespace whatwedo\TableBundle\EventListener;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use whatwedo\SearchBundle\Repository\IndexRepository;
use whatwedo\SearchBundle\whatwedoSearchBundle;
use whatwedo\TableBundle\Event\ResultRequestEvent;

/**
 * Class AjaxFilterSearchListener
 * @package whatwedo\TableBundle\EventListener
 */
class AjaxFilterSearchListener
{

    /**
     * @var EntityManager $em
     */
    protected $em;

    /**
     * @var IndexRepository
     */
    protected $indexRepository;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * AjaxFilterSearchListener constructor.
     * @param EntityManagerInterface $em
     * @param ContainerInterface $container
     * @param IndexRepository $indexRepository
     */
    public function __construct(EntityManagerInterface $em, ContainerInterface $container, IndexRepository $indexRepository)
    {
        $this->em = $em;
        $this->container = $container;
        $this->indexRepository = $indexRepository;
    }

    /**
     * @param ResultRequestEvent $requestEvent
     */
    public function searchResultSet(ResultRequestEvent $requestEvent)
    {
        // check if whatwedo serach bundle is enabled
        if (!in_array(whatwedoSearchBundle::class, $this->container->getParameter('kernel.bundles'))) {
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
            $ids = $this->indexRepository->search($term);
            $entities = $this->em->getRepository($class)
                ->createQueryBuilder('e')
                ->where('e.id IN (:ids)')
                ->setParameter('ids', $ids)
                ->getQuery()
                ->getResult()
            ;
            $items = array_map(function($entity) {
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
