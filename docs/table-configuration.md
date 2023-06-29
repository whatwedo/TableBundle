# Table Configuration

In the table configuration you define all the columns of the table

## Table Options

All Options are as constants in `Table` class.

- `title`: String|Null
- `searchable`: Boolean, default: `false`
- `sortable`: Boolean, default: `true`
- `attr`:
- `table_attr`: 
- `default_limit` : Int, dafault: `25`
- `limit_choices`:  Int, possible values: [`10`, `25`, `50`, `100`, `200`]
- `table_box_template`: String, box template, default: `@araiseTable/table.html.twig`
- `table_template`: String, table template, default:  `@araiseTable/tableOnly.html.twig`
- `default_sort`:
- `content_visibility`: Array with option, default:
- - `content_show_pagination`: Boolean, default: `true`
- - `content_show_result_label`: Boolean, default: `true`
- - `content_show_header`: Boolean, default: `true`
- - `content_show_entry_dropdown`: Boolean, default: `true`


## Column Options

Example
```php
public function configureTable(Table $table): void
    {
        parent::configureTable($table);
        $table
            ->addColumn('firstname')
            ->addColumn('lastname')
            ->addColumn('birthday', null, [
                'sortable' => false,
                'formatter' => UserBirthdayFormatter::class,
            ])
        ;
    }
```

For the columns you have a few options to tweak how they behave:

All Options are as constants in `Column` class.

- `label`: String, column name
- `callable`: Function, callable to get the data
- `accessor_path`: String, defaults to the acronym
- `formatter`: [Formatter](formatter.md)
- `sort_expression`: String, example: `'trainerGroup.name'`

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

Our tables can be filtered very easily. Most of it works automatically. See chapter [Filters](filters.md) for more info. 
