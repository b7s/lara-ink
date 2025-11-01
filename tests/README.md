# LaraInk Tests

This directory contains all tests for the LaraInk package.

## Running Tests

### Run all tests
```bash
./vendor/bin/pest
```

### Run only unit tests
```bash
./vendor/bin/pest --group=unit
```

### Run only feature tests
```bash
./vendor/bin/pest --group=feature
```

### Run with coverage
```bash
./vendor/bin/pest --coverage
```

## Test Structure

- **Unit/**: Tests for individual services and classes
- **Feature/**: Tests for integration and full workflows

## Writing Tests

Use Pest v4 syntax:
- `test('description', function () { ... });`
- `expect($value)->toBe($expected);`
- `expect(fn() => ...)->toThrow(Exception::class);`

Use `beforeEach()` for setup:
```php
beforeEach(function () {
    // Setup code
});
```

## Mocking

For tests that require Laravel functions, mock them in `beforeEach()`:
```php
beforeEach(function () {
    if (!function_exists('ink_config')) {
        function ink_config(string $key, mixed $default = null): mixed
        {
            return match($key) {
                'cache.ttl' => 300,
                default => $default,
            };
        }
    }
});
```
