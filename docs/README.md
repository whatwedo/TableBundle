# Getting Started

This documentation provides a basic overview of the possibilities of the whatwedoTableBundle. 

## Requirements

This bundle has been tested on PHP >= 8.0 and Symfony >= 6.0.
We don't guarantee that it works on lower versions.  
It presumes a fresh symfony 6.x installation following the [symfony docs](https://symfony.com/doc/current/setup.html).

## Templates

The views of this template are based on the [Tailwind CSS](https://tailwindcss.com/) layout.
You can overwrite them at any time.  
More info about that can be found in the [Templating](templating.md) section of this documentation.

## Installation

### Composer

The bundle depends on bootstrap icons. To get them running smoothly in your project,
add this repository to your composer.json: ([Sadly composer cannot load repositories recursively](https://getcomposer.org/doc/faqs/why-cant-composer-load-repositories-recursively.md))
```json
"repositories": [
    {
        "type": "package",
        "package": {
            "name": "twbs/icons",
            "version": "1.8.1",
            "source": {
                "url": "https://github.com/twbs/icons",
                "type": "git",
                "reference": "tags/v1.8.1"
            }
        }
    }
]
```
Then add the bundle to your dependencies and install it:
```shell
composer require whatwedo/table-bundle
```
**remove after release**

The version `v1` is still in development,
so you need to add these lines manually to the `composer.json` `require` to get the version constraint right:
```json
"require": {
    ...
    "whatwedo/core-bundle": "dev-1.0-dev as v1.0.0",
    "whatwedo/table-bundle": "dev-1.0-dev as v1.0.0",
    "whatwedo/search-bundle": "dev-3.0-dev as v3.0.0",
    ...
}
```
Run `composer update`.
After successfully installing the bundle, you should see changes in these files:
- `composer.json`
- `composer.lock`
- `package.json`
- `symfony.lock`
- `assets/controllers.json`
- `assets/bundles.php`
 

### Tailwind and Webpack

To give you full access over the build and the look-and-feel of the application, you may install these dependencies in your project locally.  
To get it up and running like whatwedo, install the following:
```shell
yarn add tailwindcss postcss-loader sass-loader sass autoprefixer --dev
```

#### Tailwind

Be sure to extend tailwinds default config. You need a `primary` color and an `error` color.
Furthermore, you need to add our files to the `content` section. 
The config is located at `tailwind.config.js`.

If you don't already have this file, generate it with `npx tailwind init`. Here is what a config could look like:
````js
const colors = require('tailwindcss/colors')

module.exports = {
    content: [
        './assets/**/*.js',
        './templates/**/*.{html,html.twig}',
        './vendor/whatwedo/**/*.{html,html.twig,js}',
        './var/cache/twig/**/*.php',
        './src/Definition/*.php',
    ],
    theme: {
        extend: {
            colors: {
                primary: {
                    lightest: '#6EDBFF',
                    light: '#48C0E8',
                    DEFAULT: '#007EA8',
                    dark: '#336C80',
                    darkest: '#0F4152',
                },
                error: colors.red,
            }
        },
    },
    plugins: [],
}

````

#### Webpack

Create a `postcss.config.js` file in your root directory with this content:
```js
let tailwindcss = require('tailwindcss');

module.exports = {
    plugins: [
        tailwindcss('./tailwind.config.js'),
        require('autoprefixer'),
    ]
}
```
Enable sass and postcss support in the `webpack.config.js` like this:
```js
Encore
    .enableSassLoader()
    .enablePostCssLoader()
;
```
Your main style, for instance `assets/styles/app.scss`, should be a `sass` file.
If your file is named `app.css` rename it to `app.scss`. Also change the import in the main entrypoint file, for instance `assets/app.js`.
```js
import './styles/app.scss';
```

Import the following styles into the `app.scss`:
```scss
@tailwind base;
@tailwind components;
@tailwind utilities;

@import "~@whatwedo/core-bundle/styles/_tailwind.scss";
@import "~@whatwedo/table-bundle/styles/_tailwind.scss";
```
It is **important** that you include the @whatwedo styles **after** the tailwind styles.

Run `yarn dev`, it should end with the message `webpack compiled successfully`.

Done! The whatwedoTableBundle is fully installed. You can now start using it!




## Use the bundle

The Bundle uses translation files, currently only german is provided though. Feel free to open a PR with new translations!
To use it in german, set your applications `default_locale` to `de` like this:
```yaml
framework:
    default_locale: de
```

### Create a Basic Table loaded from Doctrine

```php
namespace App\Controller;

use App\Entity\Post;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use whatwedo\TableBundle\DataLoader\DoctrineDataLoader;
use whatwedo\TableBundle\Factory\TableFactory;

class DefaultController extends AbstractController
{

    #[Route('/', name: 'index')]
    public function indexAction(TableFactory $tableFactory, PostRepository $postRepository): Response
    {
        $mainTable = $tableFactory->create('main', null, [
            'dataloader_options' => [
                DoctrineDataLoader::OPTION_QUERY_BUILDER => $postRepository->createQueryBuilder('post'),
            ],
        ]);
        $mainTable
            ->addColumn('title')
            ->addColumn('description')
            ->addAction('detail', [
                'label' => 'Detail',
                'route' => 'detail',
                'route_parameters' => fn (Post $post) => ['id' => $post->getId()],
            ]);
        return $this->render('index.html.twig', [
            'mainTable' => $mainTable,
        ]);
    }

    #[Route('/{id}', name: 'detail')]
    public function detailAction(Post $post): Response
    {
        return new Response(sprintf('<h1>%s</h1><p>%s</p>', $post->getTitle(), $post->getDescription()));
    }
}
```

and in your template

```twig
{% extends 'base.html.twig' %}

{% block body %}
    {{ whatwedo_table_render(mainTable) }}
{% endblock %}
```

That's it!
