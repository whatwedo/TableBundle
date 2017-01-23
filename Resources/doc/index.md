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

Secondly, enable this bundle and the whatwedoTableBundle in your kernel.

```
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new whatwedo\TableBundle\whatwedoTableBundle(),
        // ...
    );
}
```

## Use the bundle

In your controller, you have to configure the table:

```
// src/Agency/UserBundle/Controller/UserController.php

// ...
    public function listAction()
    {
        $table = $this->get('whatwedo_table.table')
            ->setQueryBuilder($this->getRepository('AgencyUserBundle:User')->getQueryBuilder());
        
        $table
            ->addColumn('id', null, [
                'label' => '#',
            ])
            ->addColumn('lastname', null, [
                'label' => 'Lastname',
            ])
            ->addColumn('firstname', null, [
                'label' => 'Firstname',
            ])
            ->addColumn('email', null, [
                'label' => 'E-Mail',
                'formatter' => EmailFormatter::class,
            ]);
    }
    
    return $this->render('list.html.twig', [
        'table' => $table,
    ]);
    
// ...
```

and in your template

```
{* list.html.twig *}

{{ table.renderTable|raw }}
```

That's it!

### More resources

- [Table Configuration](table-configuration.md)
- [Formatter](formatter.md)
- [Events](events.md)
