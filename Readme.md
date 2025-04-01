# Unified Banking Application

A modern banking application that allows users to manage their financial accounts, make payments, and track transactions in one unified interface.

## Features

- **User Authentication**: Secure login and registration
- **Dashboard**: Overview of financial status with charts and statistics
- **Bank Accounts**: Manage multiple bank accounts
- **Payment Methods**: Add and manage credit cards and mobile financial services
- **Bill Payments**: Pay utility bills and other services
- **Scheduled Payments**: Set up recurring and future-dated payments
- **Transaction History**: Track all financial activities
- **User Profile**: Manage personal information and account preferences

## Technology Stack

- **Backend**: Laravel 10
- **Frontend**: Vue 3 with Vite
- **CSS Framework**: Tailwind CSS 4
- **Database**: PostgreSQL
- **Authentication**: Laravel Sanctum

## Installation

### Prerequisites

- PHP 8.1+
- Composer
- Node.js 16+
- NPM or Yarn
- PostgreSQL

### Backend Setup

1. Clone the repository:
```bash
git clone https://github.com/shakib04/unified-payment-system.git
cd unified-payment-system
```

2. Install PHP dependencies:
```bash
composer install
```

3. Create and configure environment file:
```bash
cp .env.example .env
```

4. Configure your database connection in the `.env` file:
```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=unified_banking
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password
```

5. Generate application key:
```bash
php artisan key:generate
```

6. Run database migrations and seed the data:
```bash
php artisan migrate --seed
```

7. Create a symbolic link for file uploads:
```bash
php artisan storage:link
```

### Frontend Setup

1. Install JavaScript dependencies:
```bash
npm install
```

2. Update PostCSS configuration in `postcss.config.js`:
```js
export default {
  plugins: {
    '@tailwindcss/postcss': {},
    autoprefixer: {},
  }
}
```

3. Configure Tailwind CSS in `tailwind.config.js`:
```js
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
```

## Running the Application

### Development Mode

1. Start the Laravel development server:
```bash
php artisan serve
```

2. In a separate terminal, start the Vite development server:
```bash
npm run dev
```

3. Access the application in your browser at `http://localhost:8000`

### Production Deployment

1. Build the frontend assets:
```bash
npm run build
```

2. Configure your web server (Apache/Nginx) to serve the application from the public directory.

## API Routes

The application provides the following API routes:

- **Authentication**: `/api/login`, `/api/register`, `/api/logout`
- **User Profile**: `/api/profile`, `/api/profile/password`, `/api/profile/photo`
- **Bank Accounts**: `/api/bank-accounts`
- **Payment Methods**: `/api/payment-methods`
- **Bills**: `/api/bills`, `/api/bills/pay`
- **Scheduled Payments**: `/api/scheduled-payments`
- **Transactions**: `/api/transactions`

## Project Structure

```
unified-banking/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── API/
│   ├── Models/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── js/
│   │   ├── components/
│   │   ├── App.vue
│   │   └── app.js
│   └── css/
├── routes/
│   └── api.php
└── public/
```

## Testing

Run the test suite with:

```bash
php artisan test
```

For frontend tests:

```bash
npm run test
```

## Troubleshooting

### Common Issues

1. **Tailwind CSS not working**: Make sure you've installed `@tailwindcss/postcss` and configured your PostCSS and Tailwind configs correctly.

2. **Vue components not loading**: Check browser console for errors. Ensure the component is properly imported and registered.

3. **API errors**: Verify that you're authenticated and have the correct permissions. Check Laravel logs at `storage/logs/laravel.log`.

4. **Toast notifications not working**: Ensure Vue3-toastify is properly installed and configured in your app.js file.

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Acknowledgements

- Laravel Team
- Vue Team
- Tailwind CSS Team
- All open-source contributors whose libraries made this project possible
