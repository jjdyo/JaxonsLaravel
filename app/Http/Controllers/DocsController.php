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
        $files = File::files($docsPath);

        $documents = [];
        foreach ($files as $file) {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'md') {
                $filename = pathinfo($file, PATHINFO_FILENAME);
                $title = Str::title(str_replace('-', ' ', $filename));

                // Skip README as it's special
                if ($filename !== 'README') {
                    $documents[] = [
                        'filename' => $filename,
                        'title' => $title,
                        'path' => '/docs/' . $filename,
                    ];
                }
            }
        }

        // Sort documents alphabetically by title
        usort($documents, function($a, $b) {
            return $a['title'] <=> $b['title'];
        });

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
        $filePath = $docsPath . '/' . $filename . '.md';

        if (!File::exists($filePath)) {
            abort(404);
        }

        $content = File::get($filePath);
        $htmlContent = $this->parseMarkdown($content);
        $title = Str::title(str_replace('-', ' ', $filename));

        return view('docs.show', [
            'title' => $title,
            'content' => $htmlContent
        ]);
    }

    /**
     * Parse markdown content to HTML
     */
    private function parseMarkdown($content)
    {
        // Using Laravel's built-in Str::markdown method
        return Str::markdown($content);
    }
}
