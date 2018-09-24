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

namespace whatwedo\TableBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;
use whatwedo\TableBundle\Entity\Filter;
use whatwedo\TableBundle\Enum\FilterStateEnum;

/**
 * @author Nicolo Singer <nicolo@whatwedo.ch>
 */
class FilterRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Filter::class);
    }

    /**
     * @param string $path Route-Path
     * @param string $username Username
     * @return Filter[]
     */
    public function findSavedFilter($path, $username)
    {
        $qb = $this->createQueryBuilder('f');

        return $qb->where(
                    $qb->expr()->andX(
                        $qb->expr()->eq('f.route', ':path'),
                        $qb->expr()->orX(
                            $qb->expr()->orX(
                                $qb->expr()->eq('f.state', FilterStateEnum::ALL),
                                $qb->expr()->eq('f.state', FilterStateEnum::SYSTEM)
                            ),
                            $qb->expr()->andX(
                                $qb->expr()->eq('f.state', FilterStateEnum::SELF),
                                $qb->expr()->eq('f.creatorUsername', ':username')
                            )
                        )
                    )
                )
            ->orderBy('f.name')
            ->setParameter('path', $path)
            ->setParameter('username', $username)
            ->getQuery()
            ->getResult();
    }
}
