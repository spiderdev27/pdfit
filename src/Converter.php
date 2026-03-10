<?php

namespace Veoksha\LaravelUniversalConverter;

use Symfony\Component\Process\Process;

class Converter
{
    /**
     * Get the Converter instance from the container (for static calls).
     */
    public static function getInstance(): self
    {
        return app(self::class);
    }

    public function __construct(
        protected ?string $uvPath,
        protected string $pythonScriptPath,
        protected int $timeout = 120
    ) {}

    /**
     * Convert any supported file to PDF (static helper).
     */
    public static function toPdf(string $inputPath, ?string $outputPath = null): string
    {
        return self::getInstance()->convertToPdf($inputPath, $outputPath);
    }

    /**
     * Convert any supported file to PDF.
     *
     * @param  string  $inputPath  Path to input file
     * @param  string|null  $outputPath  Path for output PDF (default: same dir, .pdf extension)
     * @return string Path to created PDF
     *
     * @throws \Veoksha\LaravelUniversalConverter\ConversionException
     */
    public function convertToPdf(string $inputPath, ?string $outputPath = null): string
    {
        $inputPath = realpath($inputPath) ?: $inputPath;

        if (!file_exists($inputPath)) {
            throw ConversionException::fileNotFound($inputPath);
        }

        if ($outputPath === null) {
            $outputPath = preg_replace('/\.[^.]+$/', '.pdf', $inputPath);
        }

        $outputPath = $outputPath ?: preg_replace('/\.[^.]+$/', '.pdf', $inputPath);

        $process = $this->createProcess($inputPath, $outputPath);
        $process->run();

        if (!$process->isSuccessful()) {
            throw ConversionException::failed(
                $inputPath,
                $process->getErrorOutput() ?: $process->getOutput()
            );
        }

        return $outputPath;
    }

    /**
     * Alias for toPdf. Convert file to PDF.
     */
    public function convert(string $inputPath, string $format = 'pdf', ?string $outputPath = null): string
    {
        if (strtolower($format) !== 'pdf') {
            throw ConversionException::unsupportedFormat($format);
        }

        return $this->convertToPdf($inputPath, $outputPath);
    }

    /**
     * Batch convert multiple files to PDF.
     *
     * @param  array<string>  $inputPaths
     * @return array<string> Paths to created PDFs
     */
    public function batch(array $inputPaths): array
    {
        $results = [];

        foreach ($inputPaths as $inputPath) {
            $results[] = $this->convertToPdf($inputPath);
        }

        return $results;
    }

    protected function createProcess(string $inputPath, string $outputPath): Process
    {
        $uv = $this->resolveUvPath();
        $script = $this->pythonScriptPath;

        $command = [
            $uv,
            'run',
            '--with', 'pypandoc-binary',
            '--with', 'weasyprint',
            '--with', 'Pillow',
            '--with', 'pypdf',
            '--with', 'ebooklib',
            '--with', 'beautifulsoup4',
            $script,
            $inputPath,
            $outputPath,
        ];

        $process = new Process($command);
        $process->setTimeout($this->timeout);

        $env = array_merge(is_array(getenv()) ? getenv() : [], [
            'PYTHONUNBUFFERED' => '1',
        ]);

        // macOS: Help WeasyPrint find Homebrew's Pango/Cairo/GLib libraries (libgobject, etc.)
        if (PHP_OS_FAMILY === 'Darwin') {
            $brewLibs = array_filter(['/opt/homebrew/lib', '/usr/local/lib'], 'is_dir');
            if ($brewLibs) {
                $dyld = $env['DYLD_LIBRARY_PATH'] ?? '';
                $prepend = implode(':', $brewLibs);
                $env['DYLD_LIBRARY_PATH'] = $dyld ? $prepend . ':' . $dyld : $prepend;
            }
        }

        $process->setEnv($env);

        return $process;
    }

    protected function resolveUvPath(): string
    {
        if ($this->uvPath && file_exists($this->uvPath)) {
            return $this->uvPath;
        }

        $paths = [
            getenv('HOME') . '/.local/bin/uv',
            getenv('HOME') . '/.cargo/bin/uv',
        ];

        if (PHP_OS_FAMILY === 'Windows') {
            $paths = [
                getenv('USERPROFILE') . '\\.local\\bin\\uv.exe',
                getenv('USERPROFILE') . '\\.cargo\\bin\\uv.exe',
            ];
        }

        foreach ($paths as $path) {
            if ($path && file_exists($path) && is_executable($path)) {
                return $path;
            }
        }

        return 'uv';
    }
}
