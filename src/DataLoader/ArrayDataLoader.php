<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\DataLoader;

class ArrayDataLoader implements DataLoaderInterface
{
    private \Doctrine\Common\Collections\ArrayCollection $data;

    public function setData(\Doctrine\Common\Collections\ArrayCollection $data)
    {
        $this->data = $data;
    }

    public function getResults()
    {
        return $this->data;
    }
}
