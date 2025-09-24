# Laravel MySQL Trigger Package

laravel-trigger is a Laravel/Lumen package that allows subscribing to MySQL binlog events like jQuery event handlers, based on the `krowinski/php-mysql-replication` library. This package provides real-time monitoring of MySQL database changes through binary log replication.

**ALWAYS reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.**

## Working Effectively

### Bootstrap and Dependencies
- Install Composer dependencies:
  - `composer install` -- takes 2-3 minutes normally, but can fail due to GitHub API rate limits. NEVER CANCEL. Set timeout to 5+ minutes.
  - If composer install fails with GitHub token requests, this is normal in restricted environments
  - Alternative: `composer install --no-dev` for production dependencies only

### Validation and Testing
- Validate PHP syntax across all files:
  - `find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;` -- takes less than 1 second. Always run this first.
- Run PHPStan analysis (if dependencies are available):
  - `composer analyse` -- takes 30-60 seconds. NEVER CANCEL. Set timeout to 2+ minutes.
  - Direct command: `phpstan analyse --memory-limit 300M -l 0 -c phpstan.neon ./src`
- Run PHP CS Fixer (if available):
  - `composer cs-fix` -- takes 15-30 seconds for dry-run, 30-60 seconds for fixes. NEVER CANCEL. Set timeout to 2+ minutes.
  - Direct command: `php-cs-fixer fix --dry-run --diff --verbose --show-progress=dots`

### Laravel Application Integration
**CRITICAL**: This package requires a Laravel or Lumen application to function properly. It cannot run standalone.

#### Laravel Installation
- `composer require "huangdijia/laravel-trigger:^4.0"`
- `php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"`

#### Lumen Installation  
- `composer require "huangdijia/laravel-trigger:^4.0"`
- Edit `bootstrap/app.php` and add:
  ```php
  $app->register(Huangdijia\Trigger\TriggerServiceProvider::class);
  $app->configure('trigger');
  ```
- `php artisan trigger:install [--force]`

### Configuration Requirements
**CRITICAL**: Requires MySQL server with binary logging enabled and specific user permissions.

#### MySQL Server Configuration
Add to MySQL config file (`my.cnf` or `my.ini`):
```
[mysqld]
server-id        = 1
log_bin          = /var/log/mysql/mysql-bin.log
expire_logs_days = 10
max_binlog_size  = 100M
binlog_row_image = full
binlog-format    = row
```

#### MySQL User Permissions
```sql
GRANT REPLICATION SLAVE, REPLICATION CLIENT ON *.* TO 'user'@'host';
GRANT SELECT ON `dbName`.* TO 'user'@'host';
```

#### Environment Configuration
Add to `.env`:
```env
TRIGGER_HOST=192.168.xxx.xxx
TRIGGER_PORT=3306
TRIGGER_USER=username
TRIGGER_PASSWORD=password
TRIGGER_DATABASES=database1,database2
TRIGGER_TABLES=table1,table2
```

## Core Commands

### Service Management
- Start trigger service: `php artisan trigger:start [-R=replication_name] [--reset]` -- Runs indefinitely, monitoring MySQL binlog. NEVER CANCEL during normal operation.
- Check service status: `php artisan trigger:status [-R=replication_name]` -- takes 1-2 seconds.
- List configured events: `php artisan trigger:list [-R=replication_name] [--database=db] [--table=tbl] [--event=write]` -- takes 1-2 seconds.
- Terminate service: `php artisan trigger:terminate [-R=replication_name] [--reset]` -- takes 1-2 seconds.

### Package Installation
- Install config and routes: `php artisan trigger:install [--force]` -- takes 1-2 seconds.

## Validation Scenarios

**ALWAYS test these scenarios after making changes to the package:**

### Basic Package Validation
1. Verify all PHP files have valid syntax:
   ```bash
   find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;
   ```
2. Check that main classes can be loaded (requires Laravel context):
   - TriggerServiceProvider registration
   - Manager class instantiation
   - Console commands registration

### Laravel Integration Testing
1. Install package in a fresh Laravel application
2. Publish configuration: `php artisan vendor:publish --provider="Huangdijia\Trigger\TriggerServiceProvider"`
3. Configure MySQL credentials in `.env`
4. Test command availability: `php artisan trigger:status`
5. Verify configuration loading without MySQL connection

### Event Registration Testing
1. Create trigger routes in `routes/trigger.php`:
   ```php
   $trigger->on('database.table', 'write', function($event) {
       logger('Write event: ' . json_encode($event));
   });
   ```
2. Test event listing: `php artisan trigger:list`
3. Verify event registration shows in output

## Common Issues and Workarounds

### Composer Installation Issues
- **GitHub API rate limits**: Normal in CI/restricted environments. Document as "composer install may require GitHub token due to API limits"
- **Missing vendor/bin**: Dependencies may install incompletely. Use direct paths like `vendor/phpstan/phpstan/phpstan`
- **SSL/Network issues**: Document as "composer install may fail in restricted network environments"

### MySQL Connection Issues
- **Connection refused**: MySQL must be running and accessible
- **Permission denied**: User must have REPLICATION privileges  
- **Binary logging disabled**: MySQL must have `log_bin` enabled
- Document these as requirements, not bugs to fix

### Runtime Validation
- Package requires MySQL connection for `trigger:start` command
- Commands like `trigger:status`, `trigger:list` work offline but show warnings
- Configuration validation works without database connection

## Project Structure

### Key Directories
```
/home/runner/work/laravel-trigger/laravel-trigger/
├── src/
│   ├── Console/           # Artisan commands
│   ├── Facades/          # Laravel facades  
│   ├── Subscribers/      # Event subscribers
│   ├── Manager.php       # Replication manager
│   ├── Trigger.php       # Core trigger class
│   └── TriggerServiceProvider.php
├── config/trigger.php    # Default configuration
├── routes/trigger.php    # Event routing template
├── .php-cs-fixer.php    # Code style rules
└── phpstan.neon         # Static analysis config
```

### Main Classes
- `TriggerServiceProvider`: Laravel service provider registration
- `Manager`: Manages multiple replication connections
- `Trigger`: Core class handling MySQL binlog monitoring
- `Console/*Command`: Artisan commands for service management

## Development Workflow

### Before Making Changes
1. `find . -name "*.php" -not -path "./vendor/*" -exec php -l {} \;` -- Validate syntax
2. `composer install` (if possible) -- Install dependencies
3. Review relevant console commands in `src/Console/`

### After Making Changes  
1. Validate PHP syntax again
2. `composer analyse` (if available) -- Run static analysis  
3. `composer cs-fix` (if available) -- Fix code style
4. Test in Laravel application context
5. Verify artisan commands still register properly

### CI Validation
The `.github/workflows/tests.yaml` workflow runs:
- PHP CS Fixer in dry-run mode: `./vendor/bin/php-cs-fixer fix --dry-run --diff --verbose --show-progress=dots`
- PHPStan analysis: `composer analyse src`
- Tests multiple PHP versions (8.2, 8.3, 8.4) and Laravel versions (11, 12)

**CRITICAL REMINDER**: NEVER CANCEL long-running operations. Set appropriate timeouts:
- Composer install: 5+ minutes minimum  
- Analysis tools: 2+ minutes minimum
- MySQL operations: May run indefinitely during normal monitoring