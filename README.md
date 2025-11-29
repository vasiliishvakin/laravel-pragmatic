# Laravel Pragmatic Toolkit

[![Tests](https://github.com/vasiliishvakin/laravel-pragmatic/actions/workflows/tests.yml/badge.svg)](https://github.com/vasiliishvakin/laravel-pragmatic/actions/workflows/tests.yml)
[![Code Style](https://github.com/vasiliishvakin/laravel-pragmatic/actions/workflows/code-style.yml/badge.svg)](https://github.com/vasiliishvakin/laravel-pragmatic/actions/workflows/code-style.yml)
[![Latest Stable Version](https://poser.pugx.org/vasiliishvakin/laravel-pragmatic/v/stable)](https://packagist.org/packages/vasiliishvakin/laravel-pragmatic)
[![Total Downloads](https://poser.pugx.org/vasiliishvakin/laravel-pragmatic/downloads)](https://packagist.org/packages/vasiliishvakin/laravel-pragmatic)
[![License](https://poser.pugx.org/vasiliishvakin/laravel-pragmatic/license)](https://packagist.org/packages/vasiliishvakin/laravel-pragmatic)

Laravel Pragmatic Toolkit - A **lightweight CQRS** implementation for Laravel, providing source-agnostic operations, State Machines, and additional utilities.

## Philosophy

**Events as side-effects. Pragmatic over dogmatic.**

This is a lightweight, pragmatic approach to CQRS - not a full CQRS implementation. The toolkit focuses on practical solutions over architectural dogma.

## Features

- **Query** - Reading from any source (DB, API, AI, files)
- **Command** - Writing to any source (DB, API, files, queues)
- **Action** - Orchestrating Query/Command with business logic
- **State Machine** - State management for domain models
- **Lightweight DTOs** - Automatic property mapping with reflection
- **Middleware Support** - Global and custom middleware for operations

## Requirements

- PHP 8.3+
- Laravel 12+

## Installation

Install the package via Composer:

```bash
composer require vasiliishvakin/laravel-pragmatic
```

The service provider will be automatically registered via Laravel's package discovery.

Optionally, publish the configuration file:

```bash
php artisan vendor:publish --tag=laravel-pragmatic-config
```

## Development

### Setup

Clone the repository and install dependencies:

```bash
git clone https://github.com/vasiliishvakin/laravel-pragmatic.git
cd laravel-pragmatic
composer install
```

### Running Tests

The package uses Pest for testing with Orchestra Testbench:

```bash
composer test
# or
vendor/bin/pest

# With coverage
composer test:coverage
```

### Code Style

The package uses Laravel Pint for code styling:

```bash
# Fix code style
composer lint

# Check code style without fixing
composer lint:test
```

### Using the Playground

A full Laravel application is included in the `playground/` directory for development and manual testing:

```bash
cd playground
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan serve
```

The playground application has the package already configured via path repository and will automatically load the latest local changes.

## Architecture

### Core Concepts

The toolkit is built around three operation types:

1. **Query** - Read operations from any source
2. **Command** - Write operations to any source
3. **Action** - Business logic orchestrating queries and commands

### Buses

- **QueryBus** - Execute queries: `QueryBus::execute()`
- **CommandBus** - Execute commands: `CommandBus::execute()`

### Design Principles

- **SRP** - One class = one operation
- **Explicit buses** - Always use `QueryBus::execute()` / `CommandBus::execute()`
- **Fluent API** - Flexible configuration before execution
- **Composition** - Pipeline for complex scenarios
- **Laravel-way** - Don't fight the framework
- **Pragmatic** - Functionality over dogma

### What NOT to Use

- **Repositories** - Covered by Query pattern
- **Services** - Covered by Action pattern
- **Interfaces everywhere** - Considered excessive
- **DTOs everywhere** - Only when mixing sources/data or when logic requires it

## License

Licensed under the Apache License 2.0. See LICENSE file for details.

## Credits

Created by [Vasilii Shvakin](mailto:vasilii.shvakin@gmail.com)
