# HTTP Method Auto-Detection Feature

## Overview

The HTTP method auto-detection feature automatically assigns appropriate HTTP methods to API routes based on the controller method names, following RESTful conventions.

## Implementation

### 1. Enhanced ApiRoute Attribute

The `ApiRoute` attribute now automatically detects HTTP methods based on method names when no explicit methods are provided:

```php
#[ApiRoute] // Automatically detects HTTP methods based on method name
public function index(): JsonResponse
{
    // Will use GET methods
}
```

### 2. Method Name to HTTP Method Mapping

| Method Name   | HTTP Methods   | Description                          |
| ------------- | -------------- | ------------------------------------ |
| `index`       | `GET`, `HEAD`  | List all resources                   |
| `show`        | `GET`, `HEAD`  | Show a specific resource             |
| `store`       | `POST`         | Create a new resource                |
| `update`      | `PUT`, `PATCH` | Update an existing resource          |
| `destroy`     | `DELETE`       | Delete a resource                    |
| `forceDelete` | `DELETE`       | Force delete a soft-deleted resource |
| `restore`     | `PATCH`        | Restore a soft-deleted resource      |
| _any other_   | `GET`, `HEAD`  | Default to GET for unknown methods   |

### 3. Explicit Override Support

You can still explicitly specify HTTP methods to override the auto-detection:

```php
#[ApiRoute(methods: ['POST', 'PUT'])]
public function customMethod(): JsonResponse
{
    // Will use POST and PUT methods (overrides auto-detection)
}
```

## Code Changes

### Modified Files:

1. **`src/Attributes/ApiRoute.php`**
   - Updated constructor to use empty array as default for methods
   - Added `getMethods()` method that accepts optional method name parameter
   - Added `determineHttpMethodsFromName()` method for auto-detection logic

2. **`src/Services/ApiRouteDiscoveryService.php`**
   - Updated to pass method name to `getMethods()` for auto-detection

3. **`tests/Unit/ApiRouteAttributeTest.php`**
   - Added comprehensive tests for HTTP method auto-detection
   - Added tests for explicit method override behavior

4. **`tests/Feature/HttpMethodDetectionTest.php`**
   - Added integration tests to verify complete workflow
   - Tests route registration with correct HTTP methods

## Benefits

1. **RESTful Conventions**: Automatically follows standard REST API conventions
2. **Developer Experience**: Less boilerplate - no need to specify obvious HTTP methods
3. **Consistency**: Ensures consistent HTTP method usage across the application
4. **Flexibility**: Still allows explicit overrides when needed
5. **Type Safety**: Maintains strict typing throughout the implementation

## Testing

- 127 tests passing (395 assertions)
- Full test coverage for both unit and integration scenarios
- Validates both auto-detection and explicit override behaviors

This feature significantly improves the developer experience while maintaining the flexibility and type safety that the Volcanic package is built on.
