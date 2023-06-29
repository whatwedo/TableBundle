<?php

declare(strict_types=1);

namespace araise\TableBundle\Entity;

use Doctrine\Common\Collections\Collection;

interface TreeInterface
{
    public function getLevel(): int;

    public function getChildren(): Collection;
}
