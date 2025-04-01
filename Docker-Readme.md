# Docker Setup for Unified Banking Application

This guide will help you set up and run the Unified Banking Application using Docker, which ensures consistency across all environments.

## Prerequisites

- Docker installed on your machine
- Docker Compose installed on your machine
- Git to clone the repository

## Setup Instructions

1. Clone the repository:
```bash
git clone https://github.com/shakib04/unified-payment-system.git
cd unified-payment-system
```

2. Create a `.env` file:
```bash
cp .env.example .env
```

3. Update the `.env` file with the following database settings:
```
DB_CONNECTION=pgsql
DB_HOST=db
DB_PORT=5432
DB_DATABASE=unified_banking
DB_USERNAME=postgres
DB_PASSWORD=password
```

4. Run the Docker containers:
```bash
docker-compose up -d
```

5. Set up the application within the container:
```bash
# Enter the application container
docker-compose exec app bash

# Inside the container, run:
composer install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

## Testing

At this point, your application should be running. You can access it by visiting:
```
http://localhost:8000
```

## Managing Docker

### Start the containers
```bash
docker-compose up -d
```

### Stop the containers
```bash
docker-compose down
```

### View running containers
```bash
docker-compose ps
```

### View container logs
```bash
docker-compose logs -f
```

### Rebuild the container (after changes to Dockerfile)
```bash
docker-compose up -d --build
```

## Troubleshooting

1. **Database connection issues**:
    - Ensure your `.env` file has the correct database settings
    - Make sure the container names match the hostnames in your configuration
    - Check that the ports aren't being used by other services

2. **Permission issues**:
    - You may need to adjust the user ID in the `docker-compose.yml` file to match your host user ID
    - Use `id -u` to find your host user ID and update the `uid` argument

3. **Container fails to start**:
    - Check the logs for errors: `docker-compose logs app`
    - Verify all required environment variables are set

## Development Workflow

When working with Docker:

1. Make changes to your code (on your host machine)
2. The changes will be reflected in the container due to volume mapping
3. For some changes (like environment variables or Dockerfile changes), you may need to rebuild
