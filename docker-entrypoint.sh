#!/bin/bash

# Wait for database to be ready
echo "Waiting for PostgreSQL database to be ready..."
until nc -z -v -w30 db 5432
do
  echo "Waiting for database connection..."
  sleep 5
done
echo "Database is ready!"

# Run database migrations
php artisan migrate --force

# Optimize the application
php artisan optimize

# Start the application
echo "Starting the application..."
php artisan serve --host=0.0.0.0 --port=8000
