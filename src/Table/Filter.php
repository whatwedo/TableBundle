<?php

declare(strict_types=1);

namespace whatwedo\TableBundle\Table;

use whatwedo\TableBundle\Filter\Type\FilterTypeInterface;

class Filter
{
    public function __construct(
        protected string $acronym,
        protected string $name,
        protected FilterTypeInterface $type
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAcronym(): string
    {
        return $this->acronym;
    }

    public function setAcronym(string $acronym): self
    {
        $this->acronym = $acronym;

        return $this;
    }

    public function getType(): FilterTypeInterface
    {
        return $this->type;
    }

    public function setType(FilterTypeInterface $type): self
    {
        $this->type = $type;

        return $this;
    }
}
