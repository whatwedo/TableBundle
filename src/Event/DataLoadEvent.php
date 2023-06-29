<?php

declare(strict_types=1);

namespace araise\TableBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use araise\TableBundle\Table\Table;

class DataLoadEvent extends Event
{
    public const PRE_LOAD = 'whatwedo_table.data_load.pre_load';

    public const POST_LOAD = 'whatwedo_table.data_load.post_load';

    public function __construct(
        protected Table $table
    ) {
    }

    public function getTable(): Table
    {
        return $this->table;
    }
}
