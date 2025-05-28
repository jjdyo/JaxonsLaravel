# PageController Documentation

## Overview
The `PageController` is responsible for rendering the main pages of the application. It handles basic page requests and returns the appropriate views without complex business logic.

## Location
`app\Http\Controllers\PageController.php`

## Namespace
`App\Http\Controllers`

## Dependencies
The controller relies on the following imports:
- `Illuminate\Http\Request` - For handling HTTP requests

## Methods

### `home()`
Displays the home page of the application.

**Parameters:** None  
**Returns:** View (`pages.home`)  
**Example Usage:**
```php
// In routes/web.php
Route::get('/', [PageController::class, 'home'])->name('home');
```

### `about()`
Displays the about page of the application.

**Parameters:** None  
**Returns:** View (`pages.about`)  
**Example Usage:**
```php
// In routes/web.php
Route::get('/about', [PageController::class, 'about'])->name('about');
```

### `contact()`
Displays the contact page of the application. This route is protected by authentication and permission middleware.

**Parameters:** None  
**Returns:** View (`pages.contact`)  
**Middleware:** 
- `auth` - Requires user to be authenticated
- `permission:view contact page url` - Requires user to have the 'view contact page url' permission

**Example Usage:**
```php
// In routes/web.php
Route::middleware(['auth', 'permission:view contact page url'])->group(function () {
    Route::get('/contact', [PageController::class, 'contact'])->name('contact');
});
```

### `profile()`
Displays the user profile page. This route is protected by authentication middleware.

**Parameters:** None  
**Returns:** View (`pages.profile`)  
**Middleware:** 
- `auth` - Requires user to be authenticated

**Example Usage:**
```php
// In routes/web.php
Route::get('/profile', [PageController::class, 'profile'])->middleware('auth')->name('profile');
```

## Views Used
- `pages.home` - Home page view
- `pages.about` - About page view
- `pages.contact` - Contact page view
- `pages.profile` - User profile view
