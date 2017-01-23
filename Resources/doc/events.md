# Events

There are some events which are triggered while editing or creating entites.

## Events available

- `whatwedo_table.data_load.pre_load`: is triggered before the resultset is loaded
- `whatwedo_table.data_load.post_load`: is triggered after the resultset is loaded

## Using events

In this example, we limit the number of rows returned based on the `?limit=n` Parameter.

```
<?php
// src/whatwedo/TableBundle/EventListener/LimitEventListener.php

namespace whatwedo\TableBundle\EventListener;

use whatwedo\TableBundle\Event\DataLoadEvent;
use whatwedo\TableBundle\Table\Table;

class LimitEventListener
{
    public function limitResultSet(DataLoadEvent $event)
    {
        $table = $event->getTable();

        if ($table->getRequest()->query->getInt('limit') === -1) {
            $table->getQueryBuilder()->setMaxResults(-1);
            $table->getQueryBuilder()->setFirstResult(0);
            return;
        }

        $table->getQueryBuilder()->setMaxResults($table->getRequest()->query->getInt('limit', 25));
        $table->getQueryBuilder()->setFirstResult(($table->getCurrentPage() - 1) * $table->getLimit());
    }
}

```

```
# src/whatwedo/TableBundle/Resources/config/services.yml
services:
    whatwedo_table.event_listener.limit:
        class: whatwedo\TableBundle\EventListener\LimitEventListener
        tags:
            - { name: kernel.event_listener, event: whatwedo_table.data_load.pre_load, method: limitResultSet }

```
