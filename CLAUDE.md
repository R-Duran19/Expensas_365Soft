# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Expensas 365Soft is a Laravel + Vue 3 application for managing condominium and property expenses. The system handles water meter readings, expense calculations, billing periods, and payment processing for residential and commercial properties.

## Development Commands

### Backend (Laravel)
- `composer install` - Install PHP dependencies
- `php artisan serve` - Start development server
- `php artisan migrate` - Run database migrations
- `php artisan tinker` - Open Laravel REPL
- `php artisan test` - Run PHP tests (uses Pest)
- `composer dev` - Run server + queue + Vite in parallel
- `composer dev:ssr` - Run with SSR enabled
- `php artisan pail` - View logs in real-time

### Frontend (Vue 3 + Vite)
- `npm install` - Install Node.js dependencies
- `npm run dev` - Start Vite development server
- `npm run build` - Build for production
- `npm run build:ssr` - Build with SSR
- `npm run lint` - Run ESLint
- `npm run format` - Format code with Prettier

### Testing
- `composer test` - Run full test suite
- Individual tests can be run with Pest syntax

## Architecture

### Backend Structure
- **Models**: Core business entities (Expensa, Propiedad, Medidor, Lectura, etc.)
- **Controllers**: Handle HTTP requests for different modules
- **Services**: Complex business logic (e.g., CalculoExpensasService)
- **Middleware**: Role-based access control and request handling
- **Routes**: Organized in separate files by module (expensas.php, propiedades.php, etc.)

### Frontend Structure
- **Vue 3 + Composition API**: Modern reactive frontend
- **Inertia.js**: SPA-like experience without separate API
- **TypeScript**: Type-safe JavaScript
- **UI Components**: Custom component library with Reka UI
- **Pages**: Organized by feature (Accesos, ExpensePeriods, etc.)
- **Tailwind CSS**: Utility-first styling

### Key Business Logic
- **Expense Calculation**: CalculoExpensasService handles water bill calculations based on meter readings
- **Property Management**: Properties can have individual or shared meters
- **Billing Periods**: Monthly billing cycles with factor-based calculations
- **Payment Processing**: Multi-step payment allocation and cash transaction management

### Database Architecture
- **Properties**: Core real estate entities
- **Meters**: Water consumption tracking (individual/shared)
- **Readings**: Monthly meter consumption data
- **Billing Periods**: Monthly expense calculation cycles
- **Expenses**: Generated bills for each property
- **Payments**: Transaction and allocation tracking

### Authentication & Authorization
- Laravel Fortify for authentication
- Role-based access control with middleware
- Two-factor authentication support
- User management and settings

### Development Environment
- Uses Laragon for local development
- Database migrations for schema management
- Seeders for initial data
- Error logging with Laravel Pail

## Common Development Patterns

### Route Organization
Routes are split into separate files by module:
- Main routes in `routes/web.php`
- Feature-specific routes (expensas.php, propiedades.php, etc.)
- Admin-only routes protected by role middleware

### Vue Component Structure
- Reusable UI components in `resources/js/components/ui/`
- Feature components in `resources/js/pages/ModuleName/`
- TypeScript interfaces for type safety
- Inertia.js for data passing between backend and frontend

### Database Relationships
- Properties have owners (Propietario) and tenants (Inquilino)
- Meters belong to properties or groups
- Readings are recorded monthly per meter
- Expenses are generated per property per billing period
- Payments are allocated to specific expenses

## Important Notes

- The system handles both individual and shared water meters
- Expense calculations use different factors for residential vs commercial properties
- All monetary values are stored in Bolivianos (BS)
- The application uses Spanish terminology throughout
- Role-based access is enforced at multiple levels