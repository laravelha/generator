# Getting Started
## Introduction
**Laravelha** is an open source tool (MIT) made to speed up your development and make your life easier.
Stop writing boilerplate code, with a unique command you can build a whole CRUD (views included), using 
Bootstrap and soon Tailwindcss.
## Installation
After a fresh laravel application install, run the following command:
```
composer require laravelha/preset-api --dev
```
Or if you're building an api run:
```
composer require laravelha/preset-web --dev
```
and finally: 
```
composer require laravelha/generator --dev
```
Congratulations! Laravelha is installed.
## Running commands
```
ha-generator:migration      "Create a new migration class and apply schema at the same time"
ha-generator:model          "Create a new model class and apply schema at the same time"
ha-generator:factory        "Create a new factory class and apply schema at the same time"
ha-generator:requests       "Create a new requests class and apply schema at the same time"
ha-generator:controller     "Create a new controller and resources for api"
ha-generator:resources      "Create a new resources class and apply schema at the same time"
ha-generator:route          "Insert new resources routes"
ha-generator:test           "Create a new feature test and apply schema at the same time"
ha-generator:lang           "Create a new lang resource and apply schema at the same time"
ha-generator:view           "Create a new views resource and apply schema at the same time"
ha-generator:breadcrumb     "Insert new resources breadcrumb"
ha-generator:nav            "Insert new nav item"
ha-generator:crud           "Run all commands"
ha-generator:existing:crud  "Run all commands from a existing database"
ha-generator:package        "Create scaffolding structure to packages"
```
For more information, use:
```
php artisan help ha-generator:<COMMAND>
```
