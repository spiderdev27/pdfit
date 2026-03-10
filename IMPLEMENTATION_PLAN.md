# Laravel Universal PDF Converter — Deep Analysis & Implementation Plan

## Executive Summary

You want a **single Laravel package** that converts documents to PDF, with Python doing the heavy lifting — **no microservice, no cloud API, no separate Python backend**. The main constraint: when users run on cloud servers, they don't want to manually install Python or maintain a second stack.

After research and reference checks, there is an **out-of-the-box approach** that addresses this: use **uv** as the bridge. uv can install and manage Python with no prior installation, and your package can hide all of this behind a simple `composer require`.

---

## 1. Core Problem (Refined)

| What You Want | What You Don't Want |
|---------------|---------------------|
| Single Laravel package | Microservice / separate service |
| Python for conversion (DOCX, MD, HTML → PDF) | Manual Python install / second stack |
| Works on user's cloud server | Cloud API / paid service |
| Simple install | Complex setup |

**Main friction:** Users on cloud servers often assume “Python on server = separate Python service.” In reality, Python can run as a **utility** (like `ffmpeg` or `wkhtmltopdf`) — started by PHP, runs, exits. No long-lived service.

---

## 2. Reference Architecture (How Others Solve It)

### 2.1 Laravel Packages Using External Binaries

| Package | External Tool | Behavior |
|---------|---------------|----------|
| Laravel Snappy | wkhtmltopdf | PHP calls binary via `exec()` |
| php-ffmpeg | ffmpeg | Same pattern |
| Spatie Image Optimizer | jpegoptim, optipng | Same pattern |
| Pandoc (ueberdosis) | pandoc | Same pattern |

**Pattern:** Install package → User must install binary → PHP calls binary when needed. No extra “service.”

### 2.2 The uv Approach (Redberry / Laravel Cloud)

From [Redberry’s Laravel + Python article](https://redberry.international/running-python-code-on-laravel-cloud/):

- **uv** is a Python package manager (Rust-based).
- **Installed via Composer `post-install-cmd`** — one curl call.
- **No prior Python install required** — uv can download and manage Python.
- Python runs as subprocess (e.g. `uv run script.py`) — same pattern as `exec("ffmpeg ...")`.

This matches your “single backend, Python as tool” model.

### 2.3 uv Features That Matter

| Feature | Implication |
|---------|-------------|
| `curl \| sh` install | No Python needed to install uv |
| Auto-download Python | No need to install Python on server |
| `uv run script.py --with pkg` | Dependencies installed on demand |
| PEP 723 inline metadata | Script can declare deps in the file |
| Fast (Rust) | Low overhead for per-request runs |

---

## 3. Conversion Dependencies — Reality Check

### 3.1 What Python Can Do (pip packages)

| Format | Library | System Deps? | Quality |
|--------|---------|--------------|---------|
| Markdown → PDF | pypandoc + weasyprint | No* | Good |
| HTML → PDF | weasyprint | No | Very good |
| DOCX → PDF | pypandoc + weasyprint | No* | Good |
| Images → PDF | Pillow | No | Very good |
| EPUB → PDF | ebooklib + weasyprint | No | Good |
| PPTX → PDF | LibreOffice only | Yes (LibreOffice) | Best |
| XLSX → PDF | LibreOffice only | Yes (LibreOffice) | Best |

\* `pypandoc_binary` bundles Pandoc — no system Pandoc install.  
\* weasyprint is pure Python (uses Pango/Cairo, but installable via pip wheels on most platforms).

### 3.2 Tiered Support Model

**Tier 1 — Zero system deps (after `composer install`):**

- MD, HTML, TXT, images → PDF
- DOCX → PDF (via pypandoc_binary + weasyprint)

**Tier 2 — One system dep (LibreOffice):**

- DOCX, PPTX, XLSX → PDF (LibreOffice gives best fidelity)

Your package can:

- Prefer Tier 1 when possible.
- Use LibreOffice for Tier 2 when it exists.
- Fail clearly when a format needs LibreOffice and it’s not installed.

---

## 4. Out-of-the-Box Architecture

### 4.1 High-Level Flow

```
User: composer require veoksha/pdfit
         │
         ▼
Composer post-install
         │
         ├─► Install uv (curl | sh) if not present
         └─► Create .venv in package, install Python deps via uv
         │
         ▼
User: Converter::toPdf('doc.docx')
         │
         ▼
PHP (Symfony Process)
         │
         exec: uv run convert.py input.pdf output.pdf
         │
         ▼
uv
         │
         ├─► Uses existing .venv OR downloads Python + creates env
         ├─► Installs deps (pypandoc_binary, weasyprint, Pillow, etc.) if needed
         └─► Runs Python script
         │
         ▼
Python: convert → exit
         │
         ▼
PDF file created
```

No microservice. One backend (Laravel). Python runs as a short-lived process.

### 4.2 Directory Layout

```
pdfit/
├── src/
│   ├── Converter.php              # Main API
│   ├── ConverterService.php       # Orchestration
│   └── Drivers/
│       ├── UvDriver.php           # Calls uv run
│       └── LibreOfficeDriver.php  # Optional, for Office formats
├── python/
│   ├── convert.py                 # Main conversion script (PEP 723)
│   └── requirements.txt           # Fallback
├── config/
│   └── converter.php
├── composer.json
├── phpunit.xml
└── README.md
```

### 4.3 Python Script (PEP 723 Inline Metadata)

```python
# /// script
# requires-python = ">=3.10"
# dependencies = [
#   "pypandoc-binary",
#   "weasyprint",
#   "Pillow",
# ]
# ///
import sys
from pathlib import Path
# ... conversion logic
```

`uv run convert.py input output` will:

- Use or download Python 3.10+
- Create an env and install those packages
- Run the script

---

## 5. Implementation Phases

### Phase 1: Core Package (Week 1)

1. **Composer package skeleton**
   - Namespace: `Veoksha\LaravelUniversalConverter`
   - Service provider, config publishing
2. **uv integration**
   - `post-install-cmd`: install uv if missing
   - Store uv path (e.g. `~/.local/bin/uv` or `$HOME/.cargo/bin/uv`)
3. **Python script**
   - Support: MD, HTML, TXT, images → PDF
   - Use pypandoc_binary + weasyprint + Pillow
4. **PHP wrapper**
   - `Converter::toPdf($input)` and `Converter::convert($input, 'pdf')`
   - Use `Symfony\Component\Process\Process` to call `uv run`
5. **Health check**
   - `php artisan converter:check` to verify uv, Python, and formats

### Phase 2: Office Support (Week 2)

1. **LibreOffice detection**
   - Check for `libreoffice` / `soffice` in PATH
2. **Office conversions**
   - DOCX, PPTX, XLSX → PDF via LibreOffice headless
3. **Strategy**
   - Use LibreOffice when available; fall back to pypandoc/weasyprint for DOCX when not

### Phase 3: DX and Robustness (Week 3)

1. **Batch**
   - `Converter::batch(['a.docx', 'b.md'])`
2. **Storage**
   - `Converter::fromStorage('docs/file.docx')->toStorage('pdf/file.pdf')`
3. **Queue**
   - `ConvertToPdfJob` for background conversion
4. **Errors**
   - Custom exceptions, clear messages
5. **Tests**
   - Unit tests for PHP, integration tests with real files

---

## 6. Critical Implementation Details

### 6.1 uv Installation in Composer

```json
{
  "scripts": {
    "post-install-cmd": [
      "@php -r \"file_exists(getenv('HOME').'/.local/bin/uv') || passthru('curl -LsSf https://astral.sh/uv/install.sh | sh');\""
    ]
  }
}
```

Or via a small PHP script that checks `which uv` and runs the installer if needed.

### 6.2 Running Python

```php
$process = Process::fromShellCommandline(
    sprintf(
        '%s run %s %s %s',
        $this->uvPath(),  // ~/.local/bin/uv
        base_path('vendor/veoksha/pdfit/python/convert.py'),
        $inputPath,
        $outputPath
    )
);
$process->setTimeout(120);
$process->run();
```

Ensure `PYTHONUNBUFFERED=1` and correct `PATH` if the venv is used.

### 6.3 Cross-Platform and Hosting

| Platform | uv | Python | Notes |
|----------|----|--------|------|
| Linux (VPS, Docker) | Works | uv installs | Typical target |
| macOS | Works | uv installs | Local dev |
| Windows | PowerShell install | uv installs | Supported |
| Shared hosting (no shell) | May fail | May fail | Same as ffmpeg/wkhtmltopdf; document as unsupported |

### 6.4 Vendoring Python Script Path

Use `__DIR__` in the package to resolve the script path:

```php
$scriptPath = dirname(__DIR__, 2) . '/python/convert.py';
```

---

## 7. Differentiation and Positioning

### 7.1 Comparison

| Solution | Pros | Cons |
|----------|------|------|
| CloudConvert API | No deps, robust | Cost, external API |
| Laravel Snappy | Simple | Only HTML → PDF |
| php-file-converters | Flexible | Many system deps |
| **This package** | Auto Python setup, broad formats, local and free | Shared hosting often unsupported |

### 7.2 Positioning

- “Laravel Universal Converter” — one package for common document → PDF conversions
- “Python under the hood, Laravel API on the surface”
- “Zero Python setup — we install and manage it for you”
- Ideal for VPS, Docker, and cloud servers where shell access exists

---

## 8. Risks and Mitigations

| Risk | Mitigation |
|------|------------|
| uv install blocked (firewall) | Document manual uv install; optional `CONVERTER_UV_PATH` |
| weasyprint system libs missing | Document; consider Docker image for complex envs |
| Slow first run (uv + Python + deps) | Accept one-time warmup; optional preload command |
| Shared hosting without exec | Clear docs: “Requires VPS/cloud with exec” |
| Architecture (x86 vs ARM) | uv and wheels generally support both |

---

## 9. Recommended Next Steps

1. Create package skeleton with Composer and PSR-4.
2. Implement uv auto-install in `post-install-cmd`.
3. Build minimal Python script (e.g. HTML → PDF) and verify `uv run` from PHP.
4. Add PHP `Converter` class and `converter:check` command.
5. Extend supported formats (MD, images, DOCX via pypandoc_binary + weasyprint).
6. Add LibreOffice detection and Office support.
7. Add batch, storage, and optional queue integration.
8. Write tests and documentation.
9. Publish to Packagist and GitHub.

---

## 10. References

- [Redberry: Running Python on Laravel Cloud](https://redberry.international/running-python-code-on-laravel-cloud/)
- [uv docs – Python versions](https://docs.astral.sh/uv/concepts/python-versions/)
- [uv – Running scripts](https://docs.astral.sh/uv/guides/scripts/)
- [pypandoc_binary (bundles Pandoc)](https://pypi.org/project/pypandoc-binary/)
- [weasyprint](https://weasyprint.org/)
- [python-build-standalone](https://github.com/indygreg/python-build-standalone)

---

## Summary

Use **uv** to make Python an internal implementation detail of your Laravel package: install it via Composer, run conversion via `uv run`, and keep PHP as the single backend. No microservice, no paid API, no manual Python setup. The main requirement is an environment where Composer and subprocess execution are allowed (typical for VPS, Docker, and most cloud setups).
