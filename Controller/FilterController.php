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

namespace whatwedo\TableBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use whatwedo\TableBundle\Entity\Filter;
use whatwedo\TableBundle\Enum\FilterStateEnum;
use whatwedo\TableBundle\Event\ResultRequestEvent;

/**
 * Class FilterController.
 */
class FilterController extends AbstractController
{
    /**
     * @var RouterInterface
     */
    private $router;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * FilterController constructor.
     */
    public function __construct(RouterInterface $router, EntityManagerInterface $entityManager, EventDispatcherInterface $eventDispatcher)
    {
        $this->router = $router;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/whatwedo/table/filter/create", name="whatwedo_table_filter_direct_create", methods="POST")
     */
    public function directCreateAction(Request $request)
    {
        $filter = new Filter();
        $filter->setName($request->request->get('filter_name'));
        $filter->setDescription($request->request->get('filter_description'));
        $filter->setState($request->request->getBoolean('filter_public') ? FilterStateEnum::ALL : FilterStateEnum::SELF);
        $filter->setCreatorUsername($this->getUser()->getUsername());

        $filter->setRoute($request->request->get('filter_route'));
        $filter->setArguments(json_decode($request->request->get('filter_route_arguments'), true));
        $filter->setConditions(json_decode($request->get('filter_conditions'), true));

        if (!\is_array($filter->getConditions()) || !\is_array($filter->getArguments())) {
            throw new BadRequestHttpException();
        }

        if (null === $this->router->getRouteCollection()->get($filter->getRoute())) {
            throw new BadRequestHttpException();
        }

        $this->entityManager->persist($filter);
        $this->entityManager->flush();

        return $this->redirect($this->router->generate(
            $filter->getRoute(),
            array_merge($filter->getArguments(), $filter->getConditions())
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route("/whatwedo/table/filter/delete/{id}", name="whatwedo_table_filter_direct_delete")
     */
    public function deleteAction(Filter $filter, Request $request)
    {
        if (!$this->isCsrfTokenValid('token', $request->get('token'))) {
            throw new InvalidCsrfTokenException('Invalid CSRF token');
        }

        if ($filter->getCreatorUsername() !== $this->getUser()->getUsername()) {
            throw $this->createAccessDeniedException();
        }
        $this->entityManager->remove($filter);
        $this->entityManager->flush();

        return $this->redirect($request->headers->get('referer'));
    }

    /**
     * @return JsonResponse
     *
     * @Route("/whatwedo/table/filter/relation", name="whatwedo_table_filter_load_relation_filter", methods="GET")
     */
    public function loadRelationFilterTypeAction(Request $request)
    {
        $class = $request->get('entity');
        $term = $request->get('q');
        $resultRequestEvent = new ResultRequestEvent($class, $term);
        $this->eventDispatcher->dispatch($resultRequestEvent, ResultRequestEvent::FILTER_SET);

        return $resultRequestEvent->getResult();
    }
}
