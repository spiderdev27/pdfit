# API Reference

## Converter class

### Static methods

#### `Converter::toPdf(string $inputPath, ?string $outputPath = null): string`

Convert a file to PDF.

**Parameters:**
- `$inputPath` — Full path to the input file
- `$outputPath` — Optional. Full path for the output PDF. If omitted, uses same directory and `.pdf` extension

**Returns:** Path to the created PDF

**Throws:** `Veoksha\LaravelUniversalConverter\ConversionException`

**Example:**
```php
$pdf = Converter::toPdf('docs/report.docx');
// Returns: docs/report.pdf

$pdf = Converter::toPdf('input.pptx', 'outputs/presentation.pdf');
// Returns: outputs/presentation.pdf
```

---

### Instance methods

#### `toPdf(string $inputPath, ?string $outputPath = null): string`

Same as the static method. Use when injecting `Converter` via the container.

#### `convert(string $inputPath, string $format = 'pdf', ?string $outputPath = null): string`

Converts to the given format. Currently only `'pdf'` is supported. Throws if `$format` is not `'pdf'`.

#### `batch(array $inputPaths): array`

Convert multiple files to PDF.

**Parameters:**
- `$inputPaths` — Array of input file paths

**Returns:** Array of output PDF paths (same order)

**Example:**
```php
$pdfs = $converter->batch([
    'file1.docx',
    'file2.png',
    'file3.html',
]);
// Returns: ['file1.pdf', 'file2.pdf', 'file3.pdf']
```

---

## ConversionException

**Namespace:** `Veoksha\LaravelUniversalConverter`

**Static factories:**
- `ConversionException::fileNotFound(string $path)` — Input file does not exist
- `ConversionException::failed(string $inputPath, string $message)` — Conversion failed
- `ConversionException::unsupportedFormat(string $format)` — Output format not supported (e.g. not `'pdf'`)

---

## Artisan commands

### `converter:check`

Verifies that uv and the Python script are available.

```bash
php artisan converter:check
```
