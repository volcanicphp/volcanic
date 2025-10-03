# Volcanic API Playground - React + TypeScript + shadcn/ui

This playground is built with modern web technologies for a superior developer experience.

## Tech Stack

-   **React 18.2** - Modern React with hooks
-   **TypeScript** - Type-safe development
-   **shadcn/ui** - High-quality, accessible UI components built on Radix UI
-   **Tailwind CSS v4** - Utility-first CSS framework
-   **Vite 5** - Lightning-fast build tool
-   **Lucide React** - Beautiful icon library
-   **react-json-view-lite** - Syntax-highlighted JSON viewer

## Project Structure

```
resources/
├── js/
│   ├── components/
│   │   ├── Playground.tsx          # Main playground component
│   │   └── ui/                     # shadcn/ui components
│   │       ├── button.tsx
│   │       ├── input.tsx
│   │       ├── label.tsx
│   │       ├── textarea.tsx
│   │       ├── tabs.tsx
│   │       ├── select.tsx
│   │       ├── scroll-area.tsx
│   │       └── separator.tsx
│   ├── lib/
│   │   └── utils.ts                # Utility functions (cn helper)
│   └── playground.tsx              # Entry point
├── css/
│   └── playground.css              # Global styles + Tailwind imports
└── dist/                           # Build output (published to public/vendor/volcanic/)
    ├── playground.js
    └── playgroundStyles.css
```

## Development

### Prerequisites

```bash
npm install
```

### Building Assets

```bash
# Development build
npm run dev

# Production build
npm run build
```

Assets are built to `resources/dist/` and then published to `public/vendor/volcanic/` via Laravel's asset publishing system.

### Adding shadcn/ui Components

```bash
# Add a specific component
npx shadcn@latest add [component-name]

# Example: add a card component
npx shadcn@latest add card
```

Components will be added to `resources/js/components/ui/` as `.tsx` files.

### TypeScript Configuration

-   `tsconfig.json` - Main TypeScript config for the app
-   `tsconfig.node.json` - TypeScript config for Vite config file
-   Path alias `@/*` maps to `resources/js/*` for cleaner imports

### Component Development

All components use TypeScript for type safety:

```typescript
interface Props {
    value: string;
    onChange: (value: string) => void;
}

export function MyComponent({ value, onChange }: Props) {
    return <Input value={value} onChange={(e) => onChange(e.target.value)} />;
}
```

### Styling

-   Uses Tailwind CSS v4 (no config file needed)
-   shadcn/ui components use standard Tailwind classes
-   Custom styles in `resources/css/playground.css`
-   `@source` directive tells Tailwind which files to scan

## Publishing Assets

After building, publish assets to the public directory:

```bash
php artisan vendor:publish --tag="volcanic-assets" --force
```

## Features

-   ✅ Full TypeScript type safety
-   ✅ Accessible components (WCAG compliant via Radix UI)
-   ✅ Syntax-highlighted JSON responses
-   ✅ Real-time request building
-   ✅ Route and model discovery
-   ✅ Authentication support (Bearer/Basic)
-   ✅ Multiple request body types (JSON/Form)
-   ✅ Responsive design
-   ✅ Dark mode ready (via Tailwind)

## Troubleshooting

### Build Errors

If you see TypeScript errors during build:

```bash
# Check TypeScript directly
npx tsc --noEmit
```

### Missing Components

If shadcn components are missing:

```bash
# Reinstall all components
npx shadcn@latest add button input label textarea separator tabs select scroll-area
```

### CSS Not Applied

Make sure to rebuild and republish:

```bash
npm run build
php artisan vendor:publish --tag="volcanic-assets" --force
```

## Learn More

-   [React Documentation](https://react.dev)
-   [TypeScript Handbook](https://www.typescriptlang.org/docs/)
-   [shadcn/ui Documentation](https://ui.shadcn.com)
-   [Tailwind CSS v4](https://tailwindcss.com)
-   [Vite Documentation](https://vitejs.dev)
