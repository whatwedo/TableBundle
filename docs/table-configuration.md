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
- `table_box_template`: Box Template default: `@whatwedoTable/table.html.twig`
- `table_template`: Table Template default  `@whatwedoTable/tableOnly.html.twig`
- `default_sort`:,


## Column Options

- `label`: Column name
- `callable`: Callable to get the data
- `accessor_path`: defaults to the acronym
- `formatter`: [Formatter](formatter.md)
- `sort_expression`: ex.  'trainerGroup.name',

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
