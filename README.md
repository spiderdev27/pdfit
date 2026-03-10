# Pdfit

> Make it PDF. A Laravel package that converts **any document** to PDF using Python under the hood. Supports DOCX, images, HTML, Markdown, Excel, PowerPoint, EPUB, ZIP, and 20+ other formats—with no microservice, no cloud API, and no manual Python setup.

---

## What Is This?

Laravel/PHP is weak at document conversion. Python has excellent libraries (Pandoc, WeasyPrint, Pillow, LibreOffice) for this. This package bridges them:

- **You** write Laravel code
- **The package** runs Python via [uv](https://github.com/astral-sh/uv) (auto-installed)
- **The result** is a PDF

Everything runs locally on your server. No external API, no extra backend service.

---

## Supported Formats

| Category | Formats | Engine |
|----------|---------|--------|
| **Office** | docx, doc, pptx, ppt, xlsx, xls, odt, ods, odp | LibreOffice / pypandoc |
| **Web & Text** | html, htm, md, markdown, txt, rtf, csv | weasyprint / pypandoc |
| **Images** | png, jpg, jpeg, gif, bmp, tiff, tif, webp | Pillow |
| **Other** | epub, zip | pypandoc / ebooklib / merge |

### ZIP Files

A ZIP is treated as a bundle: the package extracts supported files inside, converts each to PDF, and merges them into one PDF. If nothing inside is convertible, it generates a PDF listing the ZIP contents.

---

## Requirements

| Requirement | Notes |
|-------------|-------|
| **PHP** 8.1+ | |
| **Laravel** 10+ | |
| **uv** | Auto-installed by Composer (no action needed) |
| **LibreOffice** | Required for PPTX, XLSX, ODT, ODS, ODP. See [Installation](#install-libreoffice-optional) |

---

## Installation

### 1. Install the package

```bash
composer require veoksha/pdfit
```

This will:
- Install the package
- Attempt to install **uv** (a fast Python runner) if not present
- uv then manages Python and conversion libraries

### 2. Install LibreOffice (for Office formats)

Required only if you need to convert PPTX, XLSX, ODT, ODS, or ODP.

**macOS:**
```bash
brew install --cask libreoffice
echo 'export PATH="/Applications/LibreOffice.app/Contents/MacOS:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

**Ubuntu/Debian:**
```bash
sudo apt install libreoffice
```

**Docker:**
```dockerfile
RUN apt-get update && apt-get install -y libreoffice
```

---

## Usage

### Basic conversion

```php
use Veoksha\LaravelUniversalConverter\Converter;

// Convert any file to PDF (output: same directory, .pdf extension)
$pdfPath = Converter::toPdf('path/to/document.docx');

// Specify output path
Converter::toPdf('input.pptx', 'storage/app/output/report.pdf');
```

### Dependency injection

```php
use Veoksha\LaravelUniversalConverter\Converter;

class ExportController extends Controller
{
    public function export(Converter $converter)
    {
        $pdf = $converter->toPdf(storage_path('uploads/report.xlsx'));
        return response()->download($pdf);
    }
}
```

### Batch conversion

```php
$pdfPaths = Converter::batch([
    'file1.docx',
    'file2.png',
    'file3.html',
]);
// Returns: ['file1.pdf', 'file2.pdf', 'file3.pdf']
```

### With Laravel Storage

```php
use Illuminate\Support\Facades\Storage;

$inputPath = Storage::path('docs/report.docx');
$outputPath = storage_path('app/pdf/report.pdf');

Converter::toPdf($inputPath, $outputPath);

return Storage::download('pdf/report.pdf');
```

---

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=converter-config
```

**`config/converter.php`:**

| Option | Default | Description |
|--------|---------|-------------|
| `uv_path` | `null` | Path to uv binary. `null` = auto-detect |
| `timeout` | `120` | Max seconds per conversion |

**Environment variables:**
```env
CONVERTER_UV_PATH=/custom/path/to/uv
CONVERTER_TIMEOUT=180
```

---

## Health check

```bash
php artisan converter:check
```

Checks that uv and the Python script are available. Use this after deploy or when troubleshooting.

---

## How It Works

### Architecture

```
Laravel (PHP)
     │
     ▼
Converter::toPdf($file)
     │
     ▼
Symfony Process runs:
  uv run convert.py input.pdf output.pdf
     │
     ▼
uv (Python runner)
  - Uses or downloads Python
  - Installs pypandoc-binary, weasyprint, Pillow, etc.
  - Runs the conversion script
     │
     ▼
Python convert.py
  - Detects file extension
  - Chooses engine (weasyprint, pypandoc, Pillow, LibreOffice)
  - Writes output PDF
     │
     ▼
Process exits, PHP gets path to PDF
```

### Conversion engines

| Engine | Formats | System dependency |
|--------|---------|-------------------|
| **weasyprint** | html, md, txt, csv | None (pure Python) |
| **pypandoc** | docx, rtf, epub | None (bundles Pandoc) |
| **Pillow** | png, jpg, gif, etc. | None |
| **LibreOffice** | pptx, xlsx, odt, ods, odp | Must be installed |

### Why uv?

- No prior Python install needed
- Installs Python and deps on demand
- Fast (Rust-based)
- Single `uv run` call; no virtualenv management

---

## File structure

```
pdfit/
├── config/
│   └── converter.php
├── python/
│   └── convert.py          # Conversion logic
├── scripts/
│   └── install-uv.php
├── src/
│   ├── ConversionException.php
│   ├── Converter.php
│   ├── ConverterServiceProvider.php
│   ├── Scripts.php
│   └── Console/
│       └── ConverterCheckCommand.php
├── tests/
│   ├── run_all.sh          # Format tests
│   └── output/             # Generated PDFs
├── composer.json
└── README.md
```

---

## Troubleshooting

### "uv not found"
Install manually:
```bash
curl -LsSf https://astral.sh/uv/install.sh | sh
```
Ensure `~/.local/bin` is in your PATH.

### "LibreOffice not found" (for PPTX/XLSX/ODT)
Install LibreOffice and add it to PATH (see [Installation](#2-install-libreoffice-optional)).

### Conversion fails silently
- Check `php artisan converter:check`
- Ensure `exec()` is allowed (shared hosting may block it)
- Increase `config/converter.php` timeout for large files

### Shared hosting
Most shared hosts restrict `exec()` and installing binaries. Use a VPS, cloud VM, or Docker for reliable conversions.

---

## License

MIT
