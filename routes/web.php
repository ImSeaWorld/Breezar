<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $packageLock = json_decode(file_get_contents(base_path('package-lock.json')));
    $composerLock = json_decode(file_get_contents(base_path('composer.lock')));

    $getPackageVer = fn($packageName) => $packageLock->dependencies->{$packageName}->version;
    $getComposerVer = fn($packageName) => collect($composerLock->packages)->where('name', $packageName)->first()->version;

    $props = [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'versions' => [
            'laravel' => Application::VERSION,
            'inertia' => [
                'client' => $getPackageVer('@inertiajs/vue3'),
                'server' => $getComposerVer('inertiajs/inertia-laravel'),
            ],
            'vue' => [
                'client' => $getPackageVer('vue'),
            ],
            'quasar' => [
                'client' => [
                    'quasar' => $getPackageVer('quasar'),
                    'extras' => $getPackageVer('@quasar/extras'),
                    'vite-plugin' => $getPackageVer('@quasar/vite-plugin'),
                ],
            ],
            'vite' => [
                'client' => [
                    'vite' => $getPackageVer('vite'),
                ],
            ],
            'php' => PHP_VERSION,
        ],
    ];

    return Inertia::render('Welcome', $props);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__ . '/auth.php';
