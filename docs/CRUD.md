#Basic Crud

## Generating a basic Category CRUD
To generate a basic CRUD run:
```
php artisan ha-generator:crud Category -s 'title:string(150), description:text:nullable, published_at:timestamp:nullable'
```
Then you will be prompted to generate a Migration, Model, Controller, Tests, Factory, Routes and Requests.
```
Do you wish to create the migration? [Y/n]
```
Accept all and your files will be created. If you do not wish to be prompted , you can run the past command with the -y flag:
```
php artisan ha-generator:crud Category -s 'title:string(150), description:text:nullable, published_at:timestamp:nullable -y'
```
And that's it!
