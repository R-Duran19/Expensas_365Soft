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
- `npm run format:check` - Check formatting without modifying files

### Testing
- `composer test` - Run full test suite (includes config:clear)
- Individual tests can be run with Pest syntax
- Testing uses in-memory SQLite database (see phpunit.xml)
- Test environment uses array drivers for session/cache

## Architecture

### Backend Structure
- **Models**: Core business entities (Expensa, Propiedad, Medidor, Lectura, etc.)
- **Controllers**: Handle HTTP requests for different modules
- **Services**: Complex business logic (e.g., CalculoExpensasService)
- **Middleware**: Role-based access control and request handling
- **Routes**: Organized in separate files by module (expensas.php, propiedades.php, etc.)

### Frontend Structure
- **Vue 3 + Composition API**: Modern reactive frontend with TypeScript
- **Inertia.js**: SPA-like experience without separate API
- **UI Libraries**: Reka UI + PrimeVue components
- **Icons**: Lucide Vue Next
- **Styling**: Tailwind CSS v4 with tw-animate-css for animations
- **Pages**: Organized by feature (Accesos, ExpensePeriods, etc.)
- **Build Tools**: Vite with Laravel Vite Plugin and Wayfinder

### Key Business Logic
- **Expense Calculation**: CalculoExpensasService handles water bill calculations based on meter readings
- **Payment Processing**: PaymentAllocationService handles multi-step payment allocation
- **Property Management**: Properties can have individual or shared meters
- **Billing Periods**: Monthly billing cycles with factor-based calculations
- **Transaction Management**: CashTransaction handling with multiple transaction types

### Database Architecture
- **Properties**: Core real estate entities (Propietario/Inquilino relationships)
- **Meters**: Water consumption tracking (individual/shared) with grouping support
- **Readings**: Monthly meter consumption data (Lectura model)
- **Billing Periods**: Monthly expense calculation cycles
- **Expenses**: Generated bills for each property (Expensa model)
- **Payments**: Transaction and allocation tracking (Pago/Payment models)
- **Invoicing**: Factura and FacturaMedidorPrincipal for billing
- **Property Types**: Apartments, parking spaces, storage units, offices, commercial spaces

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
- Feature-specific routes: accesos.php, expensas.php, facturas.php, facturas-medidores-principales.php, grupos-medidores.php, lecturas.php, medidores.php, payments.php, periodos.php, propiedades.php, property-expenses.php, expense-periods.php
- Authentication routes in `routes/auth.php`
- Settings routes in `routes/settings.php`
- Admin-only routes protected by role middleware

### Vue Component Structure
- Reusable UI components in `resources/js/components/ui/`
- Feature components in `resources/js/pages/ModuleName/`
- TypeScript interfaces for type safety
- Inertia.js for data passing between backend and frontend

### Database Relationships
- Properties have owners (Propietario) and tenants (Inquilino)
- Meters belong to properties or groups (GruposMedidores)
- Readings (Lectura) are recorded monthly per meter
- Expenses (Expensa) are generated per property per billing period
- Payments (Pago/Payment) are allocated to specific expenses
- Invoices (Factura) are generated for billing periods and main meters

### Development Environment Setup
- Uses Laragon for local development
- Database migrations and seeders for initial setup
- Spanish localization (APP_LOCALE=es)
- Testing environment uses in-memory SQLite database
- Real-time logging with Laravel Pail
- Queue processing enabled in development mode

## Important Notes

- The system handles both individual and shared water meters
- Expense calculations use different factors for residential vs commercial properties
- All monetary values are stored in Bolivianos (BS)
- The application uses Spanish terminology throughout
- Role-based access is enforced at multiple levels
- SSR (Server-Side Rendering) support available for production
- Property types include residential apartments, parking, storage, offices, and commercial spaces