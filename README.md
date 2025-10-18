# Pixel Starter

Pixel Starter is a minimal Laravel 12 + Inertia + Vue 3 template for quickly prototyping modern dashboards. The UI showcase page includes the Tailwind theme, cards, tables, filters, and utility classes you can reuse when bootstrapping new projects.

## Stack
- Laravel 12 (PHP 8.2+)
- Inertia.js with Vue 3 + TypeScript
- Tailwind CSS 4 with glassmorphism accents
- Ziggy route helper & Vue Toastify for notifications

## Quickstart

```bash
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate
composer run dev    # runs php artisan serve & npm run dev in parallel
```

Visit `http://localhost:8000` to view the component showcase.

## What you get
- Single route (`/`) powered by Inertia rendering `resources/js/pages/showcase.vue`
- Tailwind theme tokens (`resources/css/app.css`) with glass + gradient utilities
- Reusable Vue components under `resources/js/components`
- Simplified Laravel backend: default `User` model, Sanctum still installed for API-ready auth

## Next steps
1. Replace the showcase page with your own feature screens
2. Scaffold auth/teams/whatever your project needs
3. Reintroduce APIs, jobs, or queues as required

## Scripts
- `composer run dev` — start Laravel + Vite together
- `npm run dev` — Vite hot reload only
- `npm run build` — production assets

## License
MIT
