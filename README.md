# Iceberg Estates Rest API
* [Demo Link]()
* [Postman Collection]()

## Used in this project:
* Laravel 8
* JWT Auth

## Available Scripts To Run In Order
* Firstly, create a postgresql database and create .env file from .env.example

```bash 
$ composer install --ignore-platform-reqs
```
```bash 
$ php artisan key:generate
```
```bash 
$ php artisan migrate
```
```bash 
$ php artisan jwt:secret
```
```bash 
$ php artisan serve
```
