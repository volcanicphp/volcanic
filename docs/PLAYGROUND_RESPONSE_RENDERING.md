# Playground Response Rendering

## Overview

The Volcanic API Playground now supports intelligent rendering of different response types with specialized UI components for each content type.

## Features

### 1. All Routes Display

The playground schema now shows **all application routes**, not just API routes:

- **API routes** (`/api/*`)
- **Web routes** (`/`)
- **Admin routes** (`/admin/*`)
- **Dashboard routes** (`/dashboard/*`)
- Internal Laravel routes are automatically excluded (\_ignition, sanctum, \_debugbar, telescope, horizon, nova)

Each route displays a colored prefix badge (except for "web" routes) to help distinguish different route groups.

### 2. HTML Response Rendering

When an endpoint returns HTML content (e.g., a Laravel view), the playground renders it with device preview options:

#### Device Sizes

- **Mobile**: 375px width
- **Tablet**: 768px width
- **Desktop**: 100% width (responsive)

#### Features

- Real-time iframe preview of HTML content
- Smooth transitions between device sizes
- Device size indicator in preview header
- "View Raw HTML" button to see the source code
- Sandbox attributes for security

#### UI Components

```tsx
// Device size selector with icons
<Button variant={deviceSize === "mobile" ? "default" : "outline"}>
  <Smartphone className="h-4 w-4 mr-1" />
  Mobile
</Button>
```

### 3. JSON Response Rendering

JSON responses are automatically detected and rendered with:

- **Syntax highlighting** using `react-json-view-lite`
- **Collapsible sections** for nested objects
- **Color-coded types** (strings, numbers, booleans, null)
- **Pretty formatting** with proper indentation

The playground attempts to parse text responses as JSON for better display.

### 4. Plain Text Rendering

Plain text responses (non-HTML, non-JSON) are displayed with:

- Monospace font for code-like content
- Proper whitespace preservation (`whitespace-pre-wrap`)
- Gray background for contrast
- Clean, readable formatting

## Technical Implementation

### Content Type Detection

The `sendRequest()` function automatically detects response types:

```typescript
const contentType = res.headers.get("content-type") || ""
const isJson = contentType.includes("application/json")
const isHtml = contentType.includes("text/html")
const isText = contentType.includes("text/") && !isHtml
```

### Response Interface

```typescript
interface ResponseData {
  status: number
  statusText: string
  data: any
  headers: Record<string, string>
  time: number
  contentType: string
  isHtml: boolean
  isJson: boolean
  isText: boolean
}
```

### Conditional Rendering

The `renderResponseBody()` function handles all three content types:

1. **HTML**: Renders in iframe with device preview
2. **JSON**: Uses JsonView component
3. **Text**: Tries JSON parsing first, falls back to plain text

### Route Prefix Detection

Backend service categorizes routes automatically:

```php
private function getRoutePrefix(string $uri): string
{
    if (str_starts_with($uri, 'api/')) {
        return 'api';
    }
    if (str_starts_with($uri, 'admin/')) {
        return 'admin';
    }
    if (str_starts_with($uri, 'dashboard/')) {
        return 'dashboard';
    }
    return 'web';
}
```

## Response Tabs

The playground provides multiple tabs for exploring responses:

- **Body**: Smart rendering based on content type
- **Headers**: All response headers in key-value format
- **Raw HTML** (HTML responses only): Source code view

## Usage Examples

### Testing a View Endpoint

```http
GET /dashboard/stats
```

The response will render in an iframe with device size options, allowing you to test responsive layouts.

### Testing a JSON API

```http
GET /api/products
```

The response will display as colorized, collapsible JSON with syntax highlighting.

### Testing Plain Text

```http
GET /api/export/csv
```

Plain text responses maintain formatting and whitespace for readability.

## Browser Compatibility

- Iframe rendering requires modern browsers with sandbox support
- Device preview uses CSS transitions (graceful degradation)
- JSON viewer works in all modern browsers

## Security Considerations

- Iframes use `sandbox="allow-same-origin allow-scripts"` attribute
- Responses are rendered in isolated context
- No external resources loaded without explicit user action

## Performance

- Lazy rendering: Response body only renders when visible
- Efficient JSON parsing with try-catch
- Minimal re-renders with React hooks
- Build size: ~290KB JS (92KB gzipped)

## Testing

All response rendering features are covered by integration tests:

```bash
composer test
# 149 tests, 451 assertions
```

The test suite verifies:

- All routes are included in schema (API, web, admin)
- Route prefix detection works correctly
- Internal routes are excluded
- Schema structure matches expected format
