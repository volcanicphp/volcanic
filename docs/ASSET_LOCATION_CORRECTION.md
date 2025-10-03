# Asset Location Correction

## Issue

Initial implementation placed compiled assets in `public/volcanic/`, which doesn't follow Laravel package conventions.

## Correction

According to [Spatie Laravel Package Tools](https://github.com/spatie/laravel-package-tools) best practices, compiled assets for packages should be located in `resources/dist/`.

## Changes Made

### 1. Vite Configuration (`vite.config.js`)

**Before**:

```javascript
build: {
    outDir: 'public/volcanic',
    // ...
}
```

**After**:

```javascript
build: {
    outDir: 'resources/dist',
    // ...
}
```

### 2. Blade Template (`resources/views/playground.blade.php`)

**Before**:

```html
<link rel="stylesheet" href="{{ asset('volcanic/playgroundStyles.css') }}" />
<script src="{{ asset('volcanic/playground.js') }}" defer></script>
```

**After**:

```html
<link
  rel="stylesheet"
  href="{{ asset('vendor/volcanic/playgroundStyles.css') }}"
/>
<script src="{{ asset('vendor/volcanic/playground.js') }}" defer></script>
```

### 3. Directory Structure

**Before**:

```
public/volcanic/           ← Wrong location
├── playground.js
├── playgroundStyles.css
└── ...
```

**After**:

```
resources/dist/            ← Correct location
├── playground.js
├── playgroundStyles.css
└── ...
```

### 4. Updated Documentation

- `docs/ASSET_COMPILATION.md` - Updated all paths and added Spatie publishing explanation
- `docs/CDN_TO_VITE_MIGRATION.md` - Corrected build output locations
- `.gitignore` - Updated comment to reference `resources/dist`

## How It Works

### Development (Package Maintainer)

1. **Edit source files**: `resources/js/playground.js`, `resources/css/playground.css`
2. **Compile assets**: `npm run build`
3. **Output location**: `resources/dist/` (committed to git)
4. **Commit compiled assets**: `git add resources/dist/`

### Production (Package User)

1. **Install package**: `composer require volcanic/volcanic`
2. **Publish assets** (automatic with `->hasAssets()` in service provider):
   ```bash
   php artisan vendor:publish --tag="volcanic-assets"
   ```
3. **Published to**: `public/vendor/volcanic/` on user's server
4. **Assets loaded from**: `{{ asset('vendor/volcanic/playground.js') }}`

## Spatie Package Tools Integration

In `VolcanicServiceProvider::configurePackage()`:

```php
$package
    ->name('volcanic')
    ->hasConfigFile()
    ->hasViews()
    ->hasAssets()  // ← This tells Spatie to publish resources/dist/* to public/vendor/volcanic/
    ->hasCommand(VolcanicCommand::class);
```

### Asset Publishing Behavior

When `->hasAssets()` is called:

- **Source**: `resources/dist/*` (in package)
- **Destination**: `public/vendor/volcanic/*` (on user's server)
- **Tag**: `volcanic-assets`
- **Command**: `php artisan vendor:publish --tag="volcanic-assets"`

## Benefits of This Approach

1. **Convention Compliance**: Follows Laravel package standards
2. **Separation of Concerns**: `resources/` for package code/assets, `public/` for user's published files
3. **No Conflicts**: `vendor/` namespace prevents asset name collisions
4. **Clear Ownership**: `public/vendor/volcanic/` clearly indicates these are published package assets
5. **Version Control**: `resources/dist/` is committed, user's `public/vendor/` can be gitignored

## Testing

All 149 tests continue to pass with the new asset location:

```bash
composer test
# ✓ 149 passed (447 assertions)
```

## Verification

After rebuild:

```bash
ls -lh resources/dist/
# playground.js              47.32 KB
# playgroundStyles.css       88.41 KB
# fa-*.{woff2,ttf}          Font files
```

Blade template correctly references:

```html
{{ asset('vendor/volcanic/playgroundStyles.css') }} {{
asset('vendor/volcanic/playground.js') }}
```

## References

- [Spatie Package Tools Documentation](https://github.com/spatie/laravel-package-tools)
- [Laravel Package Asset Publishing](https://laravel.com/docs/packages#public-assets)
- Volcanic Configuration: `src/VolcanicServiceProvider.php`
