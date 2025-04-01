@echo off
:: Unified Banking Application Setup Script for Windows
TITLE Unified Banking Application Setup

echo ===============================================
echo    Unified Banking Application Setup Script
echo ===============================================
echo.

echo Installing PHP dependencies...
call composer install
if %ERRORLEVEL% NEQ 0 (
    echo Failed to install PHP dependencies. Make sure Composer is installed.
    pause
    exit /b
)

echo Installing JavaScript dependencies...
call npm install
if %ERRORLEVEL% NEQ 0 (
    echo Failed to install JavaScript dependencies. Make sure Node.js is installed.
    pause
    exit /b
)

:: Check if .env file exists, if not, create it from .env.example
if not exist .env (
    echo Creating .env file from .env.example...
    copy .env.example .env

    :: Prompt for database details
    echo Please enter your PostgreSQL database details:
    set /p dbname=Database name:
    set /p dbuser=Database username:
    set /p dbpass=Database password:

    :: Update .env file (using temp file since Windows lacks sed)
    powershell -Command "(Get-Content .env) -replace 'DB_CONNECTION=.*', 'DB_CONNECTION=pgsql' | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'DB_PORT=.*', 'DB_PORT=5432' | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'DB_DATABASE=.*', ('DB_DATABASE=' + '%dbname%') | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'DB_USERNAME=.*', ('DB_USERNAME=' + '%dbuser%') | Set-Content .env"
    powershell -Command "(Get-Content .env) -replace 'DB_PASSWORD=.*', ('DB_PASSWORD=' + '%dbpass%') | Set-Content .env"
) else (
    echo .env file already exists. Skipping...
)

echo Generating application key...
call php artisan key:generate

echo Running database migrations...
call php artisan migrate

echo Seeding the database...
call php artisan db:seed

echo Creating storage link...
call php artisan storage:link

echo Building frontend assets...
call npm run build

echo.
echo ===============================================
echo    Application is now ready!
echo    To start the server, run: php artisan serve
echo    Then visit: http://localhost:8000
echo ===============================================
echo.

set /p start_server=Do you want to start the server now? (Y/N):
if /i "%start_server%"=="Y" (
    echo Starting the server...
    call php artisan serve
) else (
    echo Setup completed! You can start the server later with 'php artisan serve'
    pause
)
