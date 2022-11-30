# Filters

Filters can be defined inside a `Definition`.

A lot of the filter configuration is done for you automatically.

## Default behaviour

Whenever you create a `Definition` it will create some filters for you.
Out of the box the filters are defined by the properties of the `Entity` of said `Definition`.
If this behaviour does not fit your needs properly, you can always override the `configureTable()`-method and create custom filters or remove auto-generated ones.

## Override Filter Label

The bundle tries to detect labels automatically, you can however override the generated labels easily:
 ```
    public function configureTable(Table $table)
    {
        parent::configureTable($table);
        $table->getFilterExtension()
            ->overrideFilterName('acronym', 'new Label Value');
    }
```

## Add a custom filter

You can write your own filters with your own custom logic. You will find a lot of filters at `whatwedo\table-bundle\Filter\Type`. Use them directly or as an example on how to write your own.
```
    public function configureTable(Table $table)
    {
        parent::configureTable($table);
        $table->getFilterExtension()
            ->addFilter('acronym', 'label', new TextFilterType('column'));
    }
```

## Remove an unwanted filter

If the Definition generates a filter that you don't want, you can always remove them with ease:
```
    public function configureTable(Table $table)
    {
        parent::configureTable($table);
        $table->getFilterExtension()
            ->removeFilter('acronym');
    }
```
The `acronym` basically equals the property name on the `Entity`.

## Predefine often used filters

Lets say your business needs to track certain customers very often. You can predefine a filter for such situations.
(For instance we search customers with blonde hair, a height of at least 180cm and only persons that identify as women) 
```
    public function configureTable(Table $table)
    {
        parent::configureTable($table);
        $table->getFilterExtension()
            ->predefineFilter('custom_query', 'hair', TextFilterType::CRITERIA_EQUAL, HairColorEnum::BLONDE)
                ->and('height', NumberFilterType::CRITERIA_BIGGER_THAN, 180)
                ->and('gender', TextFilterType::CRITERIA_EQUAL, GenderEnum::WOMAN)
            ->end();
    }
```
It is now possible to open `http://[domain].[tld]/[your-site-with-the-table]?[table-identifier]_predefined_filter=custom_query` and the declared filters will be applied. 

## Do I need to call `parent::configureTable($table)` ?

No, but if you don't call it, you will not have the advantage of the automatically created filters. 

## FilterType Examples

Here are some examples on how to create your own custom filters by using our `FilterTypes`.

### NumberFilterType

The number filter type allows you to filter your data by a column which holds a number.
```php
public function configureFilters(Table $table): void
{
    $table->getFilterExtension()->addFilter('age', 'Age', new NumberFilterType(self::getQueryAlias() . '.age'))
}
```

### ChoiceFilterType

With this filter type you can create a dropdown with predefined values for filtering your data.

```php
public function configureFilters(Table $table): void
{
    $table->getFilterExtension()->addFilter('state', 'State', new ChoiceFilterType('s.state', ['open', 'in_progress', 'done'], ['s' => self::getQueryAlias() . '.task']))
}
```

### AjaxRelationFilterType

This filter allows you to request the data for the dropdown via AJAX. It will create a filter from a relation between two entities.

```php
public function configureFilters(Table $table): void
{
    $table->getFilterExtension()->addFilter('city', 'City', new AjaxRelationFilterType('c', City::class, $entityManager, $router->generate(CityDefinition::getRoute(Page::JSONSEARCH)), [
                's' => $queryAlias . '.street',
                'c' => 's.city',
            ]));
}
```

Let's say for this example we have a `BuildingDefinition` where we want to filter the buildings by their city. The city is stored as a property of the `Street` entity for the purpose of this example.

In this case the following properties must exist:
- Building->street
- Street->city
- City->name

ℹ️ Hint: `City->toString()` should return the city name. This will get displayed as the options of the dropdown menu.

