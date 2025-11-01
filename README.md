# Bob

A Laravel React starter kit application.

## Stack

- **Backend**: Laravel 12
- **Frontend**: React 19 with TypeScript
- **UI**: Inertia.js with Tailwind CSS
- **Authentication**: Laravel Fortify

## Setup

1. Install dependencies:
   ```bash
   composer install
   npm install
   ```

2. Configure environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. Setup database:
   ```bash
   php artisan migrate --force
   ```

4. Build assets:
   ```bash
   npm run build
   ```

Or use the setup script:
```bash
composer run setup
```

## Development

Run the development server:
```bash
composer run dev
```

This will start:
- Laravel server on `http://localhost:8000`
- Queue worker
- Vite dev server

## Testing

Run tests:
```bash
composer test
```

