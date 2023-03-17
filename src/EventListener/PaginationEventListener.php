<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\EventListener;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Event\DataLoadEvent;

class PaginationEventListener
{
    public function __construct(
        protected RequestStack $requestStack,
        protected RouterInterface $router
    ) {
    }

    public function checkPageValid(DataLoadEvent $event)
    {
        if (! $event->getTable()->getDataLoader() instanceof DoctrineDataLoader
            || ! $event->getTable()->getPaginationExtension()) {
            return;
        }

        $extension = $event->getTable()->getPaginationExtension();
        $queryBuilder = $event->getTable()->getDataLoader()->getOption(DoctrineDataLoader::OPT_QUERY_BUILDER);
        $resultCount = count($queryBuilder->getQuery()->getResult());
        if ($resultCount < ($extension->getCurrentPage() - 1) * $extension->getLimit()) {
            if ($this->requestStack->getCurrentRequest()->attributes->has('_route')) {
                $route = $this->requestStack->getCurrentRequest()->attributes->get('_route');
                $response = new RedirectResponse($this->router->generate($route));
                $response->send();
            }
        }
    }
}
