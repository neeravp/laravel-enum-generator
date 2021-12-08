# Generate PHP enum from array with Laravel

For when there is a need to generate enum from config values provided in a config file.

**Work in progress ....**
Usable but not tested for all edge cases

```php
use \Studeo\Support\Enumm;

Enumm::make(
    name: 'CardType',
    values: [
      'icon' => 'Icon Card',
      'image' => 'Image Card',
      'text' => 'Text Card',
      'component' => 'Component Card',
    ]
)
->namespace('Studeo\Cms\Enums')
->path(Cms::rootPath('src/Enums'));
```

Will generate: _in directory/path_ `modules/cms/src/Enums`

`Cms::rootPath() = modules/cms` root dir for module as an example.
Can define any absolute path with `realpath()` or `base_path()` relative to the root of the project.

```php
<?php

namespace Studeo\Cms\Enums;

enum CardType: string
{
    case icon = 'Icon Card';
	case image = 'Image Card';
	case text = 'Text Card';
	case component = 'Component Card';
}
```

```php
Enumm::make(name: 'CardType', values:['icon', 'text']);
```

Will generate: _in directory/path_ `app/Enums`.

By default it will apply the `App` namespace and store the generated file in `app/Enums` dir

```php
<?php

namespace App;

enum CardType
{
    case icon;
	case text;
}
```

```php
Enumm::make('PostStatus', ['draft' => 1, 'pulished' => 2])
    ->namespace('Studeo\Cms\Enums')
    ->path(Cms::rootPath('src/Enums'));
```

Will generate: _in directory/path:_ `modules/cms/src/Enums`

```php
<?php

namespace Studeo\Cms\Enums;

enum PostStatus: int
{
    case draft = 1;
	case pulished = 2;
}
```

Will throw an exception if the keys of array are non-sequential integers or starts with a number or contains special (disallowed) characters

```php
use \Studeo\Support\Enumm;

$values = [1 => 'icon', 'Two#' => 'text'];

Enumm::make(name: 'Dummy', values:$values)
    ->namespace('Studeo\Cms\Enums')
    ->path(Cms::rootPath('src/Enums'));
```

Will throw the following exception:

```
Exception with message 'Invalid enum keys: 1, Two#
Enum keys must be strings and start with a letter
Enum keys must be unique
Enum keys must not be empty
Enum keys must not contain spaces
Enum keys must not start with a number
Enum keys must not contain special character like: !@#$%^&*()_+=-[]{};':"/|,.<>/?
Enum keys must not start with a reserved keyword'
```

Will automatically determine the interface of the enum from the array values:

### Backed Enums

-   Will generate **Backed Enum** for associative arrays with string keys
-   Will automatically determine the data type for the enum (string or int) based on array values

### Unit Enums

-   Will generate **Unit Enum** for arrays with keys in sequential index starting from 0
-   Will throw an exception if the keys of array are non-sequential integers or starts with a number or contains special (disallowed) characters

Fluent Api with methods to specify the interface and datatype apart from namespace and path are also available
`backed(string $type)`, `namespace(string $namespace)`, `path(string $pathToStore)`
