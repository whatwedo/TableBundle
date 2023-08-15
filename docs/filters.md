# Filters
Filters can be defined inside a `Controller`.


## Add a custom filter

You can write your own filters with your own custom logic. You will find a lot of filter types at `araise\table-bundle\Filter\Type`. Use them directly or as an example on how to write your own. 
You may also consult the chapter [FilterType Examples](#filtertype-examples) for some more insight on how to use the araise FilterTypes.

```php
$table->getFilterExtension()
    ->addFilterType('firstname', 'Firstname', TextFilterType::class);
// addFilter(acronym, label, FilterType)
```

The `acronym` should basically match the property name on the `Entity` in lower case letters.
The `label` is what will be displayed as the filter option.
The `FilterType` determines the logic that will be applied.

## FilterType Examples

Here are some more examples on how to create your own custom filters by using the araise `FilterTypes`.

### NumberFilterType

The number filter type allows you to filter your data by a column which holds a number.

```php
$table->getFilterExtension()->addFilterType('age', 'Age', NumberFilterType::class, [
    FilterType::OPT_COLUMN => (self::getQueryAlias() . '.age')
])
```

### ChoiceFilterType

With this filter type you can create a dropdown with predefined values for filtering your data.

```php
$table->getFilterExtension()->addFilterType('state', 'State', ChoiceFilterType::class, [
    FilterType::OPT_COLUMN => 's.state',
    ChoiceFilterType::OPT_CHOICES => ['open', 'in_progress', 'done'],
    FilterType::OPT_JOINS => ['s' => self::getQueryAlias() . '.task'])
)
```

### AjaxRelationFilterType

This filter allows you to request the data for the dropdown via AJAX. It will create a filter from a relation between two entities.

```php
$table->getFilterExtension()->addFilterType('city', 'City', AjaxRelationFilterType::class, [
    FilterType::OPT_COLUMN => 'c',
    AjaxRelationFilterType::OPT_TARGET_ENTITY => City::class,
    AjaxRelationFilterType::OPT_JSON_SEARCH_URL => $router->generate(CityDefinition::getRoute(Page::JSONSEARCH)), [
        's' => $queryAlias . '.street',
        'c' => 's.city',
    ]]);
```

Let's say for this example we have a `BuildingDefinition` where we want to filter the buildings by their city. The city is stored as a property of the `Street` entity for the purpose of this example.

In this case the following properties must exist:
- Building->street
- Street->city
- City->name

ℹ️ Hint: `City->toString()` should return the city name. This will get displayed as the options of the dropdown menu.

## Predefine often used filters

Lets say your business needs to track certain customers very often. You can predefine a filter that the user can then apply.
(For instance we often search for customers with blonde hair, a height of at least 180cm and only persons that identify as women) 

```php
$table->getFilterExtension()
    ->predefineFilter('custom_query', 'hair', TextFilterType::CRITERIA_EQUAL, HairColorEnum::BLONDE)
        ->and('height', NumberFilterType::CRITERIA_BIGGER_THAN, 180)
        ->and('gender', TextFilterType::CRITERIA_EQUAL, GenderEnum::WOMAN)
    ->end();
```

It is now possible to open `http://[domain].[tld]/[your-site-with-the-table]?[table-identifier]_filter_predefined=custom_query` and the declared filters will be applied. 

## Remove an unwanted filter

If - for some reason - you generate a filter that you don't want, you can always remove them with ease:

```php
$table->getFilterExtension()
    ->removeFilter('acronym');
```

