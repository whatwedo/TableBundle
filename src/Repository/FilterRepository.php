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

namespace whatwedo\TableBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use whatwedo\TableBundle\Entity\Filter;
use whatwedo\TableBundle\Entity\UserInterface;

/**
 * @method Filter|null find($id, $lockMode = null, $lockVersion = null)
 * @method Filter|null findOneBy(array $criteria, array $orderBy = null)
 * @method Filter[]    findAll()
 * @method Filter[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FilterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Filter::class);
    }

    public function getMineQB(string $alias, ?UserInterface $user = null): QueryBuilder
    {
        $qb = $this
            ->createQueryBuilder($alias)
            ->where($alias . '.createdBy is null')
        ;
        if ($user) {
            $qb
                ->orWhere($alias . '.createdBy = :user')
                ->setParameter('user', $user)
            ;
        }

        return $qb;
    }

    /**
     * @return Filter[]
     */
    public function findSaved(string $path, ?UserInterface $user): array
    {
        $qb = $this->createQueryBuilder('f');

        $qb = $qb->where($qb->expr()->eq('f.route', ':path'))
            ->orderBy('f.name')
            ->setParameter('path', $path);

        if ($user) {
            $qb
                ->leftJoin('f.createdBy', 'wwd_user')
                ->andWhere(
                    $qb->expr()->orX(
                        $qb->expr()->isNull('f.createdBy'),
                        $qb->expr()->eq('wwd_user.id', ':user_id')
                    )
                )->setParameter('user_id', $user->getId());
        } else {
            $qb->andWhere($qb->expr()->isNull('f.createdBy'));
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}
