# LMS Manager

A Laravel-based client management system for managing Fly.io instances with built-in authentication, 2FA support, and role-based access control.

## Features

- **Authentication & Security**
  - Email/password authentication
  - Two-factor authentication (2FA) support
  - Role-based access control (Admin/Manager)
  - Login tracking and activity logging

- **Client Management**
  - Full CRUD operations for clients
  - Contact information management
  - User assignment to clients
  - Status tracking (active/inactive)

- **Fly.io Integration**
  - API wrapper for Fly.io operations
  - Automatic instance synchronization
  - Instance status monitoring
  - Manual sync capabilities

- **Dashboard & Reporting**
  - Overview statistics
  - Recent activities tracking
  - Client instance monitoring
  - Report generation system

## Tech Stack

- **Backend**: Laravel 11
- **Frontend**: Vue 3 with Options API
- **UI Framework**: Quasar
- **Build Tool**: Vite
- **SPA Bridge**: Inertia.js
- **Database**: MySQL
- **Queue**: Laravel Jobs

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Copy `.env.example` to `.env` and configure:
   - Database credentials
   - Fly.io API token and organization ID

4. Generate application key:
   ```bash
   php artisan key:generate
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Build frontend assets:
   ```bash
   npm run dev
   ```

## Environment Configuration

Key environment variables:

```env
# Fly.io Configuration
FLY_API_TOKEN=your-fly-api-token
FLY_ORG_ID=your-default-org-id
```

## Scheduled Jobs

The system includes scheduled jobs for:
- Fly.io instance synchronization (every 30 minutes)
- Login log cleanup (daily)
- Activity log cleanup (weekly)

To run the scheduler:
```bash
php artisan schedule:work
```

## Queue Processing

For background job processing:
```bash
php artisan queue:work
```

## Available Commands

- `php artisan fly:sync` - Manually sync Fly.io instances
- `php artisan fly:sync --client=ID` - Sync specific client

## User Roles

- **Admin**: Full system access, can manage all clients and users
- **Manager**: Limited access to assigned clients only

## Development

Frontend components use:
- Vue Options API
- 4-space indentation
- Quasar UI components

## Security Features

- CSRF protection
- XSS protection via Vue
- SQL injection protection via Eloquent ORM
- 2FA for enhanced security
- Activity logging for audit trails