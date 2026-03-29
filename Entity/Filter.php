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

namespace whatwedo\TableBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use whatwedo\TableBundle\Enum\FilterStateEnum;
use whatwedo\TableBundle\Repository\FilterRepository;

#[ORM\Table(name: 'whatwedo_table_filter')]
#[ORM\Entity(repositoryClass: FilterRepository::class)]
class Filter
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'IDENTITY')]
    protected $id;

    #[ORM\Column(name: 'name', type: 'string', length: 50, nullable: false)]
    #[Assert\Length(max: 50)]
    protected $name;

    #[ORM\Column(name: 'path', type: 'string', length: 256, nullable: false)]
    protected $route;

    #[ORM\Column(name: 'arguments', type: 'array', nullable: false)]
    protected $arguments;

    #[ORM\Column(name: 'conditions', type: 'text', nullable: false)]
    protected $conditions;

    #[ORM\Column(name: 'creator_username', type: 'string', length: 256, nullable: false)]
    protected $creatorUsername;

    #[ORM\Column(name: 'state', type: 'smallint', nullable: false)]
    protected $state;

    #[ORM\Column(name: 'description', type: 'string', length: 256, nullable: true)]
    #[Assert\Length(max: 256)]
    protected $description;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getRoute()
    {
        return $this->route;
    }

    public function setRoute($route)
    {
        $this->route = $route;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    public function getConditions()
    {
        return unserialize($this->conditions);
    }

    public function setConditions($conditions)
    {
        $this->conditions = serialize($conditions);
    }

    public function getCreatorUsername()
    {
        return $this->creatorUsername;
    }

    public function setCreatorUsername($creatorUsername)
    {
        $this->creatorUsername = $creatorUsername;
    }

    public function getState()
    {
        return $this->state;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function getStateAsString()
    {
        return FilterStateEnum::getRepresentation($this->state);
    }

    public function getStateIcon()
    {
        switch ($this->state) {
            case FilterStateEnum::ALL:
                return 'world';
            case FilterStateEnum::SELF:
                return 'lock';
            case FilterStateEnum::SYSTEM:
                return 'gears';
        }
    }
}
