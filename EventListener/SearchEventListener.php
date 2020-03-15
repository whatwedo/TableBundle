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
use whatwedo\SearchBundle\Entity\Index;
use whatwedo\SearchBundle\whatwedoSearchBundle;
use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Extension\SearchExtension;
use whatwedo\TableBundle\Table\DoctrineTable;

/**
 * Class SearchEventListener.
 */
class SearchEventListener
{
    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var array
     */
    protected $kernelBundles;

    /**
     * TableSearchEventListener constructor.
     *
     * @param EntityManager $em
     * @param string        $kernelBundles
     */
    public function __construct(EntityManagerInterface $em, array $kernelBundles)
    {
        $this->em = $em;
        $this->kernelBundles = $kernelBundles;
    }

    /**
     * Search listener.
     */
    public function searchResultSet(DataLoadEvent $event)
    {
        if (!\in_array(whatwedoSearchBundle::class, $this->kernelBundles, true)) {
            return;
        }

        $table = $event->getTable();

        if (!$table instanceof DoctrineTable) {
            return;
        }

        // Exec only if query is set
        if ($table->hasExtension(SearchExtension::class)) {
            $query = $table->getSearchExtension()->getSearchQuery();
            if (0 === \mb_strlen(trim($query))) {
                return;
            }
        } else {
            return;
        }

        $model = $table->getQueryBuilder()->getRootEntities()[0];
        $ids = $this->em->getRepository(Index::class)->search($query, $model);

        $table->getQueryBuilder()->andWhere(sprintf(
            '%s.id IN (:q_ids)',
            $table->getQueryBuilder()->getRootAliases()[0]
        ))->setParameter('q_ids', $ids);
    }
}
