# Emergency

## Install Laravel <a href="https://laravel.com/docs/9.x/installation">here</a>

Install the composer dependencies
```
composer install
```

Create the environment variables
```
cp .env.example .env
```

Setup Docker
```
docker-compose up -d
```

Install migrations and seed the database
```
php artisan migrate:fresh --seed
```

Start the server at port 8000 (or your port of choosing)
```
php artisan serve --host=0.0.0.0 --port=8000
```

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
