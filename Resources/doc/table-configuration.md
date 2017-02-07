# Table Configuration

In the table configuration you define all the columns of the table

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
