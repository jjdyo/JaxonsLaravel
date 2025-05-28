<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DocsController extends Controller
{
    /**
     * Display the documentation index page
     */
    public function index()
    {
        $docsPath = base_path('docs');
        $documents = $this->getDocumentsHierarchy();

        return view('docs.index', [
            'documents' => $documents,
            'readme' => $this->parseMarkdown(File::get($docsPath . '/README.md'))
        ]);
    }

    /**
     * Display a specific documentation page
     */
    public function show($filename)
    {
        $docsPath = base_path('docs');

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

        if (!File::exists($filePath)) {
            abort(404);
        }

        $content = File::get($filePath);
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
     */
    private function parseMarkdown($content)
    {
        // Fix internal links before parsing markdown
        $content = preg_replace_callback(
            '/\[([^\]]+)\]\(([^)]+\.md)\)/',
            function ($matches) {
                $linkText = $matches[1];
                $linkUrl = $matches[2];

                // Remove .md extension
                $linkUrl = str_replace('.md', '', $linkUrl);

                // Generate the correct route URL
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
     */
    private function getDocumentsHierarchy()
    {
        $docsPath = base_path('docs');
        $hierarchy = [];

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
                        'path' => '/docs/' . $filename,
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
                        'path' => '/docs/' . $dirName . '/' . $filename,
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
