<!DOCTYPE html>
<html class="h-full" lang="{{ str_replace('_', '-', app()->getLocale()) }}">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="app-name" content="{{ config('app.name') }}">
    @routes
    @vite('resources/js/app.ts')
    @inertiaHead
  </head>
  <body class="antialiased min-h-screen transition duration-200 bg-gradient-to-br dark:bg-none dark:bg-image dark:bg-grey-900 from-blue-50 via-purple-50/80 to-teal-50/60 relative h-full">
    @inertia
  </body>
</html>