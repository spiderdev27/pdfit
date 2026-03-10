<?php

namespace Veoksha\LaravelUniversalConverter;

use Exception;

class ConversionException extends Exception
{
    public static function fileNotFound(string $path): self
    {
        return new self("Input file not found: {$path}");
    }

    public static function failed(string $inputPath, string $message): self
    {
        return new self("Conversion failed for {$inputPath}: {$message}");
    }

    public static function unsupportedFormat(string $format): self
    {
        return new self("Unsupported output format: {$format}. Only PDF is supported.");
    }
}
