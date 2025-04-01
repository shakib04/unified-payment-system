#!/bin/bash
# Unified Banking Application Setup Script for Linux/Mac

# Print colorful messages
GREEN='\033[0;32m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}===============================================${NC}"
echo -e "${BLUE}    Unified Banking Application Setup Script    ${NC}"
echo -e "${BLUE}===============================================${NC}"

echo -e "${GREEN}Installing PHP dependencies...${NC}"
if ! composer install; then
    echo -e "${RED}Failed to install PHP dependencies. Make sure Composer is installed.${NC}"
    exit 1
fi

echo -e "${GREEN}Installing JavaScript dependencies...${NC}"
if ! npm install; then
    echo -e "${RED}Failed to install JavaScript dependencies. Make sure Node.js is installed.${NC}"
    exit 1
fi

# Check if .env file exists, if not, create it from .env.example
if [ ! -f ".env" ]; then
    echo -e "${GREEN}Creating .env file from .env.example...${NC}"
    cp .env.example .env

    # Prompt user to update database credentials
    echo -e "${BLUE}Please enter your PostgreSQL database details:${NC}"
    read -p "Database name: " dbname
    read -p "Database username: " dbuser
    read -p "Database password: " dbpass

    # Update .env file
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" .env
    sed -i "s/DB_PORT=.*/DB_PORT=5432/" .env
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=$dbname/" .env
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=$dbuser/" .env
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=$dbpass/" .env
else
    echo -e "${GREEN}.env file already exists. Skipping...${NC}"
fi

echo -e "${GREEN}Generating application key...${NC}"
php artisan key:generate

echo -e "${GREEN}Running database migrations...${NC}"
php artisan migrate

echo -e "${GREEN}Seeding the database...${NC}"
php artisan db:seed

echo -e "${GREEN}Creating storage link...${NC}"
php artisan storage:link

echo -e "${GREEN}Building frontend assets...${NC}"
npm run build

echo -e "${GREEN}Starting the server...${NC}"
echo -e "${BLUE}===============================================${NC}"
echo -e "${BLUE}    Application is now running!    ${NC}"
echo -e "${BLUE}    Visit: http://localhost:8000    ${NC}"
echo -e "${BLUE}    Press Ctrl+C to stop    ${NC}"
echo -e "${BLUE}===============================================${NC}"

php artisan serve
