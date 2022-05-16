# Getting Started

This documentation provides a basic view of the possibilities of the whatwedoCrudBundle. 
The documentation will be extended while developing the bundle.

## Requirements

This bundle has been tested on PHP >= 7.0 and Symfony >= 3.0. 
We don't guarantee that it works on lower versions.

## Templates

The views of this template are based on [AdminLTE](https://almsaeedstudio.com/) boxes. You can overwrite them at any time. 

## Installation

First, add the bundle to your dependencies and install it.

```
composer require whatwedo/table-bundle
```

Secondly, enable this bundle and the whatwedoTableBundle in your ```config/bundles.php```. Normaly not needed for Symfony 4.                                                                  

```
<?php
return [
// ...
    whatwedo\TableBundle\whatwedoTableBundle::class => ['all' => true],
// ...
];
```

thirdly, add our routing file to your ```config/routes.yaml```

```
whatwedo_table_bundle:
    resource: "@whatwedoTableBundle/Resources/config/routing.yml"
    prefix:   /
```

fourthly, enable the templating component in your config.
``` 
framework:
    templating:
        engines: ['twig']
```
## Use the bundle

In your controller, you have to configure the table:

```
// src/Agency/UserBundle/Controller/UserController.php

// ...
    public function listAction()
    {
        /** @var TableFactory $tableFactory */
        $tableFactory = $this->get('whatwedo_table.factory.table');
    
        // static table with custom data
        $tableStatic = $tableFactory->createTable('static', [
            'data_loader' => function($page, $limit) {
                $tableData = new SimpleTableData();
    
                $tableData->setResults([
                    (object)[
                        'zip' => 3011,
                        'city' => 'Bern',
                    ],
                    (object)[
                        'zip' => 3097,
                        'city' => 'Liebefeld',
                    ],
                    (object)[
                        'zip' => 3775,
                        'city' => 'Lenk im Simmental',
                    ],
                    (object)[
                        'zip' => 3753,
                        'city' => 'Oey-Diemtigen',
                    ],
                ]);
                $tableData->setTotalResults(4);
    
                return $tableData;
            }
        ]);
    
        $tableStatic->addColumn('zip', null, [
            'label' => 'ZIP',
        ]);
    
        $tableStatic->addColumn('city', null, [
            'label' => 'City',
        ]);
    
        // dynamic table with query builder
        $tableDynamic = $tableFactory->createDoctrineTable('dynamic', [
            'query_builder' => $this->getDoctrine()->getRepository('AgencyUserBundle:User')->createQueryBuilder('server'),
            'title' => 'Dynamische Tabelle',
            'attr' => [
                'class' => 'box-primary'
            ]
        ]);
    
        $tableDynamic->addColumn('name', null, [
            'label' => 'Name',
        ]);
    
        // Render view
        return $this->render('whatwedoServerManagerBundle:Dashboard:dashboard.html.twig', [
            'tableStatic' => $tableStatic,
            'tableDynamic' => $tableDynamic,
        ]);
    }
// ...
```

add JS to your Tempalate, popover is needed for the filter

```html

<script src="https://stackpath.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js" integrity="sha384-aJ21OjlMXNL5UyIl/XNwTMqvzeRMZH2w8c5cRVpzpU8Y5bApTppSuUkhZXN0VxHd" crossorigin="anonymous"></script>

<script>
$(function () {
    $('[data-toggle="popover"]').popover({
        // importatant!!
        sanitize: false,
    });
</script>

```

and in your template

```
{* list.html.twig *}

{* render the whole table functionality *}
{{ table.renderTableBox|raw }}

{* only render the table *} 

{{ table.renderTable|raw }}
```

That's it!
