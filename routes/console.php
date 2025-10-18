<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('project:rename {name}', function (string $name) {
    $updateValue = function (string $path, string $key, string $value): void {
        if (! File::exists($path)) {
            return;
        }

        $contents = File::get($path);
        $escapedValue = addcslashes($value, "\"\\\n\r");
        $line = sprintf('%s="%s"', $key, $escapedValue);

        $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

        if (preg_match($pattern, $contents)) {
            $contents = (string) preg_replace($pattern, $line, $contents);
        } else {
            $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
        }

        File::put($path, $contents);
    };

    $envFiles = [
        base_path('.env'),
        base_path('.env.example'),
    ];

    foreach ($envFiles as $file) {
        $updateValue($file, 'APP_NAME', $name);
        $updateValue($file, 'VITE_APP_NAME', $name);
    }

    $this->info("Project renamed to '{$name}'.");
})->purpose('Update the project name used across environment files.');
