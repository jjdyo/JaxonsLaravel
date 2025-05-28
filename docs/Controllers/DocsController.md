# DocsController Documentation

## Overview
The `DocsController` is responsible for managing the documentation system of the application. It handles rendering markdown documentation files, organizing them in a hierarchical structure, and providing navigation between documentation pages.

## Location
`app\Http\Controllers\DocsController.php`

## Namespace
`App\Http\Controllers`

## Dependencies
The controller relies on the following imports:
- `Illuminate\Http\Request` - For handling HTTP requests
- `Illuminate\Support\Facades\File` - For file system operations
- `Illuminate\Support\Str` - For string manipulation and markdown parsing

## Methods

### `index()`
Displays the documentation index page, showing the README content and a hierarchical navigation sidebar.

**Parameters:** None  
**Returns:** View (`docs.index`)  
**Example Usage:**
```php
// In routes/web.php
Route::get('/docs', [DocsController::class, 'index'])->name('docs.index');
```

### `show($filename)`
Displays a specific documentation page based on the filename parameter.

**Parameters:**
- `$filename` (string): The name of the documentation file to display (without the .md extension)

**Returns:** View (`docs.show`)  
**Features:**
- Supports files in subdirectories (e.g., 'Controllers/AuthController')
- Handles 404 errors for non-existent files
- Provides a hierarchical navigation sidebar

**Example Usage:**
```php
// In routes/web.php
Route::get('/docs/{filename}', [DocsController::class, 'show'])->name('docs.show')->where('filename', '.*');
```

### `parseMarkdown($content)`
Parses markdown content to HTML, transforming internal links to use the correct route URLs.

**Parameters:**
- `$content` (string): The markdown content to parse

**Returns:** string (HTML content)  
**Features:**
- Transforms internal links that end with .md to use the correct route URLs
- Uses Laravel's built-in Str::markdown method for markdown parsing

### `getDocumentsHierarchy()`
Builds a hierarchical structure of documentation files organized by directory.

**Parameters:** None  
**Returns:** array (Hierarchical structure of documentation files)  
**Features:**
- Organizes files by directory
- Sorts directories first, then files alphabetically
- Skips README.md as it's displayed on the index page
- Only includes directories that contain markdown files

## Views Used
- `docs.index` - Documentation index page view
- `docs.show` - Documentation page view

## Route Constraints
The route for showing documentation pages uses a special constraint to allow forward slashes in the filename parameter:

```php
Route::get('/docs/{filename}', [DocsController::class, 'show'])->name('docs.show')->where('filename', '.*');
```

The `where('filename', '.*')` constraint allows any character (including forward slashes) in the filename parameter, which is necessary for supporting files in subdirectories.

## Related Documentation
- [Routes Documentation](../Routes.md) - Documentation for application routes
- [Views Documentation](../Views.md) - Documentation for views used by the controller
