# Seed
To run all seeder of DatabaseSeeder: 
```shell
php artisan db:seed
```
To run a specific seeder class:
```shell
php artisan db:seed --class=PaymentGatewaySeeder
```
Seed with migration:
```shell
php artisan migrate --seed
```
