# Laravelha Generator
[Laravel](https://laravel.com/) [RAD](https://pt.wikipedia.org/wiki/Desenvolvimento_r%C3%A1pido_de_aplica%C3%A7%C3%B5es) Package  based on [Laravel-5-Generators-Extended](https://github.com/laracasts/Laravel-5-Generators-Extended) 

## Install
After install fresh Laravel application:

1. Install preset `composer require laravelha/preset-api --dev` or `composer require laravelha/preset-web --dev` 
2. Run preset `php artisan ha-api` or `php artisan ha-web --option=auth`
3. Via `composer require laravelha/generator --dev`
4. Run `php artisan ha-generator:<COMMAND> <ARGUMENTS> <OPTIONS>` to create automatically generated code.
5. The following commands are available.:
```shell script
ha-generator:migration   "Create a new migration class and apply schema at the same time"
ha-generator:model       "Create a new model class and apply schema at the same time"
ha-generator:factory     "Create a new factory class and apply schema at the same time"
ha-generator:requests    "Create a new requests class and apply schema at the same time"
ha-generator:controller  "Create a new controller and resources for api"
ha-generator:resources   "Create a new resources class and apply schema at the same time"
ha-generator:route       "Insert new resources routes"
ha-generator:test        "Create a new feature test and apply schema at the same time"
ha-generator:lang        "Create a new lang resource and apply schema at the same time"
ha-generator:view        "Create a new views resource and apply schema at the same time"
ha-generator:breadcrumb  "Insert new resources breadcrumb"
ha-generator:nav         "Insert new nav item"
ha-generator:crud        "Run all commands"
ha-generator:package     "Create scaffolding structure to packages"
```
4. For more information for each command use:
`php artisan help ha-generator:<COMMAND>`

## Auto generated structure
  
```
app/
├── <SINGULAR_CLASS_NAME>.php
│
└── Http
    ├── Controllers
    |   ├── Auth
    |   ├── IndexController.php
    |   └── <SINGULAR_CLASS_NAME>Controller.php
    |
    └── Requests
        └── Requests
            ├── <SINGULAR_CLASS_NAME>StoreRequest.php
            └── <SINGULAR_CLASS_NAME>UpdateRequest.php
config/
└── ha-generator.php

database
├── factories
|   └── <SINGULAR_CLASS_NAME>Factory.php
|
└── migrations
  └── YYYY_MM_DD_HHmmSS_create_<PLURAL_CLASS_NAME>_table.php

resources
├── lang/pt-br
|   └── <PLURAL_CLASS_NAME>.php
|
└── views
   └── <PLURAL_CLASS_NAME>
       ├── create.blade
       ├── delete.blade
       ├── edit.blade
       ├── index.blade
       └── show.blade
  
routes
├── api.php
├── breadcrumbs.php
└── web.php

```




