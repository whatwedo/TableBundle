# Array Data Loader

The standard data loader is the `DoctrineDataLoader`. It loads data from a database table.
Also when using the full araise framework, mostly the data is stored in a database.

There are cases where you just need to dispaly a table with data from an array.
For this case the `ArrayDataLoader` is the right choice.

## Usage
`Controller/DefaultController.php`:
```php
namespace App\Controller;

use araise\TableBundle\DataLoader\ArrayDataLoader;
use araise\TableBundle\Factory\TableFactory;
use araise\TableBundle\Table\Table;
use Doctrine\Common\Collections\ArrayCollection;
use Faker\Factory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{

    public function __construct(protected TableFactory $tableFactory)
    {
    }

    #[Route('/', name: 'index')]
    public function indexAction(): Response
    {
        $table = $this->tableFactory->create('project', ArrayDataLoader::class, [
            Table::OPT_DATALOADER_OPTIONS => [
                ArrayDataLoader::OPT_DATA => $this->getTestDataArray(),
            ],
        ]);
        $table
            ->addColumn('Name', null, [
                'accessor_path' => '[name]',
            ])
            ->addColumn('Phone', null, [
                'accessor_path' => '[phone]',
            ])
            ->addColumn('Firma', null, [
                'accessor_path' => '[company]',
            ])
        ;

        return $this->render('index.html.twig', [
            'table' => $table,
        ]);
    }

    protected function getTestDataArray(int $many = 200): ArrayCollection
    {
        $faker = Factory::create();
        $faker->seed(1234); // to always get the same data
        $col = new ArrayCollection();
        for ($i = 1; $i <= $many; $i++) {
            $col->add([
                'name' => $faker->name(),
                'phone' => $faker->phoneNumber(),
                'company' => $faker->company(),
            ]);
        }
        return $col;
    }

}
```
`templates/index.html.twig`:
```twig
{% extends 'base.html.twig' %}

{% block body %}
    {{ araise_table_render(table) }}
{% endblock %}
```

You could also use `araise_table_only_render` in your twig template, this will skip the pagination and filters. 
