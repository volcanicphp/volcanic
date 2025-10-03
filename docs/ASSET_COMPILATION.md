# Asset Compilation Guide

## Overview

The Volcanic API Playground uses **Vite** to compile frontend assets (JavaScript and CSS) instead of relying on CDNs. This ensures better performance, version control, and offline capability.

## Tech Stack

-   **Build Tool**: Vite 5.0
-   **JavaScript**: Alpine.js 3.13.0 (no build step required, included via CDN in compiled bundle)
-   **CSS**: Tailwind CSS v4.0.0 (latest beta with simplified configuration)
-   **Icons**: Font Awesome 6.5.0 (Free)

## File Structure

```
resources/
├── js/
│   └── playground.js         # Alpine.js component logic
├── css/
│   └── playground.css         # Tailwind v4 styles
└── dist/                      # Compiled output (committed to repo)
    ├── playground.js              # Compiled JavaScript (47.32 KB, 17.07 KB gzipped)
    ├── playgroundStyles.css       # Compiled CSS (88.41 kB, 27.81 kB gzipped)
    ├── fa-*.{woff2,ttf}          # Font Awesome font files
    └── .vite/
        └── manifest.json          # Vite build manifest

package.json                   # NPM dependencies
vite.config.js                 # Vite configuration
```

### Asset Publishing with Spatie Package Tools

Volcanic uses [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) for package management. When users install the package, they can publish assets using:

```bash
php artisan vendor:publish --tag="volcanic-assets"
```

This copies files from `resources/dist/` to `public/vendor/volcanic/`, making them publicly accessible.

**Key Points**:

-   **Development**: Assets are compiled to `resources/dist/` (committed to repo)
-   **User Installation**: Assets are published to `public/vendor/volcanic/` on user's server
-   **Blade Templates**: Reference assets via `{{ asset('vendor/volcanic/playground.js') }}`
-   **No Build Required**: End users don't need Node.js or npm installed
    vite.config.js # Vite configuration

````

## Development Workflow

### Initial Setup

```bash
# Install dependencies
npm install

# Start development server with hot reload
npm run dev
````

### Building for Production

```bash
# Compile assets
npm run build

# Output goes to resources/dist/
```

### Making Changes

1. **Edit Source Files**:

    - JavaScript: `resources/js/playground.js`
    - CSS: `resources/css/playground.css`

2. **Rebuild Assets**:

    ```bash
    npm run build
    ```

3. **Test Changes**:

    ```bash
    # Run tests
    composer test

    # Start local server
    php artisan serve

    # Visit playground
    open http://localhost:8000/volcanic/playground
    ```

## Tailwind CSS v4 Migration

Volcanic uses **Tailwind CSS v4** (currently in beta), which introduces significant simplifications:

### What Changed

-   **No Configuration Files**: Removed `tailwind.config.js`, `postcss.config.js`, `autoprefixer`
-   **Simplified Import**: Single line in CSS: `@import "tailwindcss"`
-   **Automatic Detection**: Tailwind v4 automatically scans templates and generates required utilities

### CSS File Structure

```css
/* resources/css/playground.css */

/* Tailwind v4 - single import replaces @tailwind directives */
@import "tailwindcss";

/* Font Awesome */
@import "@fortawesome/fontawesome-free/css/all.min.css";

/* Custom scrollbar styles */
.scrollbar-thin::-webkit-scrollbar {
    /* ... */
}
```

### Migration from CDN

**Before** (CDN-based):

```html
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@3/..." rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3/..." defer></script>
<link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/..."
    rel="stylesheet"
/>
```

**After** (Vite-compiled):

```html
<link
    rel="stylesheet"
    href="{{ asset('vendor/volcanic/playgroundStyles.css') }}"
/>
<script src="{{ asset('vendor/volcanic/playground.js') }}" defer></script>
```

## Vite Configuration

```javascript
// vite.config.js
import { defineConfig } from "vite";

export default defineConfig({
    build: {
        rollupOptions: {
            input: {
                playground: "resources/js/playground.js",
                playgroundStyles: "resources/css/playground.css",
            },
            output: {
                entryFileNames: "[name].js",
                assetFileNames: "[name].[ext]",
            },
        },
        outDir: "resources/dist",
        emptyOutDir: true,
    },
});
```

### Key Configuration Decisions

-   **Output Directory**: `resources/dist/` follows [Spatie Package Tools convention](https://github.com/spatie/laravel-package-tools) for publishable assets
-   **File Naming**: `[name].[ext]` removes hashes for predictable asset URLs (package distribution doesn't need cache busting)
-   **Empty Output**: `emptyOutDir: true` ensures clean builds
-   **Asset Publishing**: The `->hasAssets()` method in `VolcanicServiceProvider` automatically registers `resources/dist/` for publishing

## Asset Loading in Laravel

The playground blade template uses Laravel's `asset()` helper:

```blade
<link rel="stylesheet" href="{{ asset('vendor/volcanic/playgroundStyles.css') }}">
<script src="{{ asset('vendor/volcanic/playground.js') }}" defer></script>
```

This automatically resolves to:

-   Local: `http://localhost:8000/volcanic/playgroundStyles.css`
-   Production: `https://example.com/volcanic/playgroundStyles.css`

## Alpine.js Integration

Alpine.js is included in the compiled JavaScript bundle:

```javascript
// resources/js/playground.js
import Alpine from "alpinejs";

window.Alpine = Alpine;
Alpine.start();

window.playground = function () {
    return {
        schema: { routes: [], models: [] },
        // ... component logic
    };
};
```

The blade template initializes the component:

```html
<div x-data="playground()" x-init="init()">
    <!-- Playground UI -->
</div>
```

## Troubleshooting

### Assets Not Loading

1. **Check Build Output**:

    ```bash
    ls -lh resources/dist/
    ```

    Should show `playground.js` and `playgroundStyles.css`

2. **Rebuild Assets**:

    ```bash
    npm run build
    ```

3. **Clear Laravel Cache**:
    ```bash
    php artisan cache:clear
    php artisan view:clear
    ```

### Alpine.js Warnings

**Issue**: "Duplicate key on x-for" warnings

**Solution**: Use indexed keys for lists:

```html
<!-- ✗ WRONG - can cause duplicate keys -->
<template x-for="route in routes" :key="route.uri + route.method">
    <!-- ✓ CORRECT - unique index-based keys -->
    <template
        x-for="(route, routeIndex) in routes"
        :key="'route-' + routeIndex"
    ></template
></template>
```

### Tailwind Classes Not Working

**Issue**: Custom classes not generated

**Solution**: Ensure templates are scanned by Vite:

1. Check `vite.config.js` includes `.blade.php` files (implicit in v4)
2. Rebuild: `npm run build`
3. Verify class exists in `resources/dist/playgroundStyles.css`

### Font Awesome Icons Missing

**Issue**: Icons not rendering

**Solution**: Font files should be in `resources/dist/`:

```bash
ls resources/dist/fa-*.{woff2,ttf}
# Should show: fa-brands-400, fa-regular-400, fa-solid-900, fa-v4compatibility
```

If missing, rebuild: `npm run build`

## Package Distribution

### Included in Git

The **compiled assets are committed** to the repository (unlike typical Laravel apps) because this is a **package**, not an application:

```gitignore
# Frontend Assets
/node_modules
# Note: resources/dist/*.{js,css} are committed (compiled assets for package distribution)
```

### Why Commit Compiled Assets?

1. **No Build Step for Users**: Consumers don't need Node.js/npm installed
2. **Immediate Functionality**: `composer require volcanic/volcanic` works out of the box
3. **Version Control**: Asset versions match package releases

### Publishing Checklist

Before releasing a new version:

```bash
# 1. Update source files
vim resources/js/playground.js
vim resources/css/playground.css

# 2. Rebuild assets
npm run build

# 3. Run tests
composer test:all

# 4. Commit compiled assets
git add resources/dist/
git commit -m "chore: rebuild playground assets"

# 5. Tag release
git tag v1.x.x
git push --tags
```

## Performance Metrics

### Build Output

```
✓ 3 modules transformed in 201ms

resources/dist/playground.js              47.32 kB │ gzip: 17.07 kB
resources/dist/playgroundStyles.css       88.41 kB │ gzip: 27.81 kB
resources/dist/fa-solid-900.woff2        158.22 kB
resources/dist/fa-brands-400.woff2       118.68 kB
resources/dist/fa-regular-400.woff2       25.47 kB
```

### Load Time Comparison

**Before (CDN)**:

-   3 external HTTP requests
-   Unpredictable latency (CDN availability)
-   Browser cache dependent on CDN headers

**After (Vite)**:

-   2 local HTTP requests (JS + CSS)
-   Predictable performance (same server)
-   Full control over cache headers
-   Total: ~65 KB gzipped (JS + CSS)

## Future Improvements

### Potential Optimizations

1. **Code Splitting**: Split Alpine.js from playground logic if playground grows
2. **CSS Purging**: Further reduce CSS size by scanning only playground templates (Tailwind v4 auto-purges)
3. **Asset Versioning**: Add `[hash]` to filenames for cache busting (requires config update)
4. **Source Maps**: Enable for debugging in development:
    ```javascript
    build: {
        sourcemap: process.env.NODE_ENV === 'development',
    }
    ```

### Monitoring

Track bundle sizes over time:

```bash
# After each build
npm run build | grep "resources/dist"
```

Alert if playground.js exceeds **60 KB** or playgroundStyles.css exceeds **100 KB** (uncompressed).

## References

-   [Vite Documentation](https://vitejs.dev/)
-   [Tailwind CSS v4 Beta](https://tailwindcss.com/blog/tailwindcss-v4-beta)
-   [Alpine.js Guide](https://alpinejs.dev/)
-   [Font Awesome Free](https://fontawesome.com/download)
