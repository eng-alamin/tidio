<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Site\AuthController;
use App\Http\Controllers\Site\LeadController;
use App\Http\Controllers\Site\NewsletterController;
use App\Http\Controllers\Site\StaticPageController;
use App\Http\Controllers\Site\BlogController;

// Public marketing website. Loaded from the bottom of routes/web.php:
//     require __DIR__.'/site.php';

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => view('site.login'))->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.attempt');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:10,1')->name('register.attempt');
});
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/blog', [BlogController::class, 'index'])->name('site.blog');
Route::get('/blog/category/{category}', [BlogController::class, 'category'])->name('site.blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('site.blog.show');

Route::get('/contact-sales', [LeadController::class, 'showContactSales'])->name('site.contact-sales');
Route::post('/contact-sales', [LeadController::class, 'storeContactSales'])->middleware('throttle:5,1')->name('site.contact-sales.store');

Route::get('/contact', [LeadController::class, 'showContact'])->name('site.contact');
Route::post('/contact', [LeadController::class, 'storeContact'])->middleware('throttle:5,1')->name('site.contact.store');

Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:5,1')->name('site.newsletter.store');
Route::get('/newsletter/unsubscribe/{subscriber}', [NewsletterController::class, 'unsubscribe'])
    ->middleware('signed')->whereNumber('subscriber')->name('site.newsletter.unsubscribe');

// Pure-static pages (features, solutions, industries, legal ...). whereIn keeps this from
// swallowing /app, /admin, /widget, /login etc.
Route::get('/{page}', StaticPageController::class)
    ->whereIn('page', config('site_pages.static'))
    ->name('site.page');
