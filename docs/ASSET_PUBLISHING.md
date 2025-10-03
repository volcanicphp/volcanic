# Asset Publishing Guide

## Overview

After migrating to Vite-compiled assets, the playground requires users to publish assets to their `public/vendor/volcanic/` directory.

## Publishing Assets

### For End Users (Package Installed via Composer)

After installing the package, run:

```bash
php artisan vendor:publish --tag="volcanic-assets" --force
```

The `--force` flag ensures assets are republished even if they already exist (important after package updates).

### What Gets Published

Files from `vendor/volcanic/volcanic/resources/dist/` are copied to `public/vendor/volcanic/`:

```
public/vendor/volcanic/
├── playground.js              # Alpine.js component
├── playgroundStyles.css       # Compiled Tailwind + Font Awesome
├── fa-solid-900.woff2         # Font Awesome Solid (WOFF2)
├── fa-solid-900.ttf           # Font Awesome Solid (TTF)
├── fa-brands-400.woff2        # Font Awesome Brands (WOFF2)
├── fa-brands-400.ttf          # Font Awesome Brands (TTF)
├── fa-regular-400.woff2       # Font Awesome Regular (WOFF2)
├── fa-regular-400.ttf         # Font Awesome Regular (TTF)
├── fa-v4compatibility.woff2   # Font Awesome v4 Compat (WOFF2)
└── fa-v4compatibility.ttf     # Font Awesome v4 Compat (TTF)
```

## Asset References in Blade

The playground blade template references assets using Laravel's `asset()` helper:

```blade
<link rel="stylesheet" href="{{ asset('vendor/volcanic/playgroundStyles.css') }}">
<script src="{{ asset('vendor/volcanic/playground.js') }}" defer></script>
```

## Font Path Configuration

Fonts are referenced with the base path `/vendor/volcanic/` configured in `vite.config.js`:

```javascript
export default defineConfig({
    base: '/vendor/volcanic/',  // ← Critical for font paths
    build: {
        outDir: "resources/dist",
        // ...
    },
});
```

This ensures Font Awesome CSS contains correct paths:

```css
@font-face {
    font-family: "Font Awesome 6 Free";
    src: url(/vendor/volcanic/fa-solid-900.woff2) format("woff2"),
         url(/vendor/volcanic/fa-solid-900.ttf) format("truetype");
}
```

## Troubleshooting

### Issue: Fonts return 404

**Problem**: Fonts trying to load from `http://localhost/fa-solid-900.ttf` instead of `http://localhost/vendor/volcanic/fa-solid-900.ttf`

**Solution**:
1. Verify `base: '/vendor/volcanic/'` is set in `vite.config.js`
2. Rebuild assets: `npm run build`
3. Republish: `php artisan vendor:publish --tag="volcanic-assets" --force`

### Issue: Alpine.js errors (playground not defined)

**Problem**: `Uncaught ReferenceError: playground is not defined`

**Solution**:
1. Ensure assets are published: `ls public/vendor/volcanic/playground.js`
2. Check browser network tab - JS should load from `/vendor/volcanic/playground.js`
3. Republish if missing: `php artisan vendor:publish --tag="volcanic-assets" --force`

### Issue: Styles not applying

**Problem**: Playground UI looks unstyled

**Solution**:
1. Verify CSS is published: `ls public/vendor/volcanic/playgroundStyles.css`
2. Check browser network tab - CSS should load from `/vendor/volcanic/playgroundStyles.css`
3. Clear browser cache (Ctrl+Shift+R / Cmd+Shift+R)
4. Republish: `php artisan vendor:publish --tag="volcanic-assets" --force`

## Development Workflow

### For Package Maintainers

1. **Edit source files**:
   ```bash
   vim resources/js/playground.js
   vim resources/css/playground.css
   ```

2. **Rebuild assets**:
   ```bash
   npm run build
   ```

3. **Test changes** (in a test Laravel app):
   ```bash
   cd /path/to/test-laravel-app
   php artisan vendor:publish --tag="volcanic-assets" --force
   php artisan serve
   ```

4. **Commit compiled assets**:
   ```bash
   git add resources/dist/
   git commit -m "chore: rebuild playground assets"
   ```

### Automated Publishing

Users can add this to their `composer.json` to auto-publish on install/update:

```json
{
    "scripts": {
        "post-install-cmd": [
            "@php artisan vendor:publish --tag=volcanic-assets --ansi --force"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=volcanic-assets --ansi --force"
        ]
    }
}
```

## Cache Busting

Since we use `[name].[ext]` without hashes, consider these strategies:

### Option 1: Version Query String

Update blade template:

```blade
<link rel="stylesheet" href="{{ asset('vendor/volcanic/playgroundStyles.css') }}?v={{ config('volcanic.version', '1.0.0') }}">
<script src="{{ asset('vendor/volcanic/playground.js') }}?v={{ config('volcanic.version', '1.0.0') }}"></script>
```

### Option 2: Laravel Mix Versioning

If the user's project uses Laravel Mix/Vite:

```php
// In their AppServiceProvider
public function boot()
{
    $this->publishes([
        __DIR__.'/../vendor/volcanic/volcanic/resources/dist' => public_path('vendor/volcanic'),
    ], 'volcanic-assets');
}
```

### Option 3: Content Hash Filenames

Update `vite.config.js` (not recommended for packages):

```javascript
output: {
    entryFileNames: '[name].[hash].js',  // Cache busting via hash
    assetFileNames: '[name].[hash].[ext]'
}
```

**Downside**: Requires manifest.json parsing in blade template.

## CI/CD Integration

### GitHub Actions Example

```yaml
name: Build Assets

on:
  push:
    paths:
      - 'resources/js/**'
      - 'resources/css/**'
      - 'package.json'
      - 'vite.config.js'

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup Node.js
        uses: actions/setup-node@v3
        with:
          node-version: '18'
          
      - name: Install dependencies
        run: npm ci
        
      - name: Build assets
        run: npm run build
        
      - name: Commit changes
        run: |
          git config user.name "GitHub Actions"
          git config user.email "actions@github.com"
          git add resources/dist/
          git diff --quiet && git diff --staged --quiet || git commit -m "chore: rebuild assets"
          git push
```

## Production Checklist

Before releasing a new version:

- [ ] `npm run build` executed
- [ ] `resources/dist/` committed to git
- [ ] Font paths verified: `grep "url(/vendor/volcanic" resources/dist/playgroundStyles.css`
- [ ] JS file size < 60 KB uncompressed
- [ ] CSS file size < 100 KB uncompressed
- [ ] All Font Awesome fonts present in `resources/dist/`
- [ ] Tested in fresh Laravel app with published assets
- [ ] Browser console has no errors
- [ ] Network tab shows 200 for all assets (no 404s)

## References

- Spatie Package Tools: https://github.com/spatie/laravel-package-tools
- Laravel Asset Publishing: https://laravel.com/docs/packages#public-assets
- Vite Base Option: https://vitejs.dev/config/shared-options.html#base
