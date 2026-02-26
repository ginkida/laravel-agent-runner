# Contributing

Thank you for considering contributing to Laravel Agent Runner! This guide will help you get started.

## Development Setup

1. Fork and clone the repository:

```bash
git clone https://github.com/your-username/laravel-agent-runner.git
cd laravel-agent-runner
```

2. Install dependencies:

```bash
composer install
```

## Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) with the `laravel` preset.

```bash
# Check code style
vendor/bin/pint --test

# Fix code style
vendor/bin/pint
```

## Tests

Tests are written with [PHPUnit](https://phpunit.de/) and [Orchestra Testbench](https://packages.tools/testbench/).

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit --testsuite=Unit
vendor/bin/phpunit --testsuite=Feature
```

## Static Analysis

This project uses [Larastan](https://github.com/larastan/larastan) (PHPStan for Laravel) at level 5.

```bash
vendor/bin/phpstan analyse
```

## Pull Request Guidelines

1. Create a feature branch from `main`
2. Make your changes
3. Ensure all checks pass:
   ```bash
   vendor/bin/pint --test
   vendor/bin/phpunit
   vendor/bin/phpstan analyse
   ```
4. Write or update tests as needed
5. Submit a pull request to `main`

### PR Checklist

- [ ] Code style passes (`pint --test`)
- [ ] All tests pass (`phpunit`)
- [ ] Static analysis passes (`phpstan analyse`)
- [ ] New features include tests
- [ ] CHANGELOG.md updated (if applicable)
