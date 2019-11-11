# Table Configuration

In the table configuration you define all the columns of the table

## Table Options


- `title`: null
- `searchable`: false
- `sortable`: true
- `attr`:
- `table_attr`: 
- `default_limit` : dafault 25
- `limit_choices`:  [10, 25, 50, 100, 200]
- `table_box_template`: Box Template default: `whatwedoTableBundle::table.html.twig`
- `table_template`: Table Template default  `whatwedoTableBundle::tableOnly.html.twig`
- `default_sort`:,


## Column Options

- `label`: Column name
- `callable`: Callable to get the data
- `accessor_path`: defaults to the acronym
- `formatter`: [Formatter](formatter.md)

## Action Columns

Action Columns are here to link to other pages (f.ex. link to edit or view).
This column has a special template to render the links.

### Options

- `items`: Array of columns. Every item has this options:
- `label`: Name of the button
- `icon`: Icon (in our templates, we're using Font Awesome 4)
- `button`: Type of button (f.ex. primary, we're using Bootstrap Button's in our base template)
- `route`: Route to call. Parameter `id` is always given

## Filters
Our Tables can be easily filtered. Most of it works automatically, what you can do is:

### Override Filter Label
The bundle tries to detect labels automatically, you can however override it easily:
 ```
    public function overrideTableConfiguration(DoctrineTable $table)
    {
        parent::overrideTableConfiguration($table);
        $table->getFilterExtension()
            ->overrideFilterName('acronym', 'new Label Value');
    } 
```

### Add a custom filter
You can write your own filters with your own custom logic. You will find a lot of filters at `whatwedo\table-bundle\Filter\Type` use them directly or as example how to write your own.
```
    public function overrideTableConfiguration(DoctrineTable $table)
    {
        parent::overrideTableConfiguration($table);
        $table->getFilterExtension()
            ->addFilter('acronym', 'label', new TextFilterType('column'));
    }
```

### Remove an unwanted filter

```
    public function overrideTableConfiguration(DoctrineTable $table)
    {
        parent::overrideTableConfiguration($table);
        $table->getFilterExtension()
            ->removeFilter('acronym');
    }
```

### Predefine often used filters
Lets say your business needs to track some customers very often. You can predefine a filter for such situations.
(For instance we search customers with blonde hair and a height of at least 180cm and women) 
```
    public function overrideTableConfiguration(DoctrineTable $table)
    {
        parent::overrideTableConfiguration($table);
        $table->getFilterExtension()
            ->predefineFilter('custom_query', 'hair', TextFilterType::CRITERIA_EQUAL, HairColorEnum::BLONDE)
                ->and('height', NumberFilterType::CRITERIA_BIGGER_THAN, 180)
                ->and('gender', TextFilterType::CRITERIA_EQUAL, GenderEnum::WOMAN)
            ->end();
    }
```
It is now possible to open `http://[domain].[tld]/[your-site-with-the-table]?[table-identifier]_predefined_filter=custom_query` and the declared filters will be applied. 

### Do I need to call `parent::overrideTableConfiguration($table)` ?
No, but if you don't call it you will not have the advantage of the automatically created filters. 

