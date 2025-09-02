<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocsController extends Controller
{
    /**
     * Display the documentation index page
     *
     * @return \Illuminate\View\View The documentation index view with documents hierarchy and readme content
     */
    public function index(): \Illuminate\View\View
    {
        $docsPath = base_path('docs');
        $documents = $this->getDocumentsHierarchy();

        // Path canonicalization and validation for README.md
        $realDocsPath = realpath($docsPath);
        $readmePath = realpath($docsPath . '/README.md');

        // Ensure README.md exists and is within the docs directory
        if (!$readmePath || !$realDocsPath || strpos($readmePath, $realDocsPath) !== 0 || !File::exists($readmePath)) {
            abort(404);
        }

        return view('docs.index', [
            'documents' => $documents,
            'readme' => $this->parseMarkdown(File::get($readmePath))
        ]);
    }

    /**
     * Display a specific documentation page
     *
     * @param string $filename The documentation file to display (can include directory path)
     * @return \Illuminate\View\View The documentation page view with parsed content
     */
    public function show($filename): \Illuminate\View\View
    {
        $docsPath = base_path('docs');

        // URL decode the filename to handle spaces and special characters
        $filename = urldecode($filename);

        // Reject any input containing path traversal sequences
        if (strpos($filename, '..') !== false) {
            abort(404);
        }

        // Check if the filename contains a directory path
        if (strpos($filename, '/') !== false) {
            $parts = explode('/', $filename);
            $actualFilename = end($parts);
            $filePath = $docsPath . '/' . $filename . '.md';
            $title = Str::title(str_replace('-', ' ', $actualFilename));
        } else {
            $filePath = $docsPath . '/' . $filename . '.md';
            $title = Str::title(str_replace('-', ' ', $filename));
        }

        // Path canonicalization and boundary check
        $realDocsPath = realpath($docsPath);
        $requestedPath = realpath($filePath);

        // Ensure the requested file exists and is within the docs directory
        if (!$requestedPath || !$realDocsPath || strpos($requestedPath, $realDocsPath) !== 0 || !File::exists($requestedPath)) {
            abort(404);
        }

        $content = File::get($requestedPath);
        $htmlContent = $this->parseMarkdown($content);

        // Get all documents for the sidebar
        $documents = $this->getDocumentsHierarchy();

        return view('docs.show', [
            'title' => $title,
            'content' => $htmlContent,
            'documents' => $documents,
            'currentFilename' => $filename
        ]);
    }

    /**
     * Parse markdown content to HTML
     *
     * @param string|null $content The markdown content to parse
     * @return string The parsed HTML content
     */
    private function parseMarkdown(?string $content): string
    {
        // If content is null, return empty string
        if ($content === null) {
            return '';
        }

        // Fix internal links before parsing markdown
        $content = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+\.md)\)/',
            function ($matches) {
                $linkText = $matches[1];
                $linkUrl = $matches[2];

                // Remove .md extension
                $linkUrl = str_replace('.md', '', $linkUrl);

                // Generate the correct route URL
                // No need to URL encode here as the route() helper will handle it
                $routeUrl = route('docs.show', ['filename' => $linkUrl]);

                return "[$linkText]($routeUrl)";
            },
            $content
        );

        // Using Laravel's built-in Str::markdown method
        return Str::markdown($content);
    }

    /**
     * Get documents organized in a hierarchical structure
     *
     * @return array<int, array<string, mixed>> An array of documents organized by directories and files
     */
    private function getDocumentsHierarchy(): array
    {
        $docsPath = base_path('docs');
        $realDocsPath = realpath($docsPath);
        $hierarchy = [];

        // Ensure docs directory exists
        if (!$realDocsPath || !is_dir($realDocsPath)) {
            return $hierarchy;
        }

        // Get all directories in the docs folder
        $directories = File::directories($docsPath);

        // First, add files from the root docs directory
        $rootFiles = File::files($docsPath);
        foreach ($rootFiles as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $title = Str::title(str_replace('-', ' ', $filename));

                // Skip README as it's special
                if ($filename !== 'README') {
                    $hierarchy[] = [
                        'filename' => $filename,
                        'title' => $title,
                        'path' => '/docs/' . urlencode($filename),
                        'type' => 'file'
                    ];
                }
            }
        }

        // Then, add directories and their files
        foreach ($directories as $directory) {
            $dirName = basename($directory);
            $dirTitle = Str::title(str_replace('-', ' ', $dirName));

            $dirFiles = File::files($directory);
            $children = [];

            foreach ($dirFiles as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
                    $filename = pathinfo($file, PATHINFO_FILENAME);
                    $title = Str::title(str_replace('-', ' ', $filename));

                    $children[] = [
                        'filename' => $dirName . '/' . $filename,
                        'title' => $title,
                        'path' => '/docs/' . urlencode($dirName) . '/' . urlencode($filename),
                        'type' => 'file'
                    ];
                }
            }

            // Sort children alphabetically
            usort($children, function($a, $b) {
                return $a['title'] <=> $b['title'];
            });

            // Only add directory if it has markdown files
            if (count($children) > 0) {
                $hierarchy[] = [
                    'title' => $dirTitle,
                    'type' => 'directory',
                    'children' => $children
                ];
            }
        }

        // Sort root items alphabetically by title, but keep directories at the top
        usort($hierarchy, function($a, $b) {
            // If both are the same type, sort by title
            if ($a['type'] === $b['type']) {
                return $a['title'] <=> $b['title'];
            }

            // Otherwise, directories come first
            return $a['type'] === 'directory' ? -1 : 1;
        });

        return $hierarchy;
    }
}
