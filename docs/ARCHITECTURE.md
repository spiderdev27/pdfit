# Architecture

## Overview

Laravel Universal Converter uses a **bridge pattern**: PHP invokes a Python script via the command line. Python handles all conversion; PHP only orchestrates and returns the result.

```
┌─────────────────────────────────────────────────────────────────┐
│  Laravel Application (PHP)                                       │
│                                                                  │
│  Converter::toPdf('file.docx')                                   │
│       │                                                          │
│       └──► Process::run(['uv', 'run', 'convert.py', ...])        │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  uv (Rust binary, ~15MB)                                         │
│  - Manages Python runtimes                                       │
│  - Installs pip packages on demand                               │
│  - Runs: python convert.py input output                          │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│  Python convert.py                                               │
│  - Reads extension, selects engine                               │
│  - Calls weasyprint / pypandoc / Pillow / LibreOffice            │
│  - Writes PDF, exits                                             │
└─────────────────────────────────────────────────────────────────┘
```

---

## Components

### 1. Converter (PHP)

**Location:** `src/Converter.php`

- Exposes `toPdf()`, `convert()`, `batch()`
- Resolves uv path (config or auto-detect)
- Builds command: `uv run --with pkg1 --with pkg2 convert.py in out`
- Runs `Symfony\Component\Process\Process`
- Throws `ConversionException` on failure

### 2. convert.py (Python)

**Location:** `python/convert.py`

- CLI: `convert.py <input> <output>`
- Dispatches by extension:
  - Images → Pillow
  - HTML/MD/TXT/CSV → weasyprint (with pypandoc for MD)
  - DOCX/RTF → pypandoc + weasyprint
  - EPUB → pypandoc (primary) or ebooklib
  - ZIP → extract, convert each, merge with pypdf
  - Office (PPTX, XLSX, ODT, etc.) → LibreOffice headless

### 3. uv

- Installed by Composer `post-install` (Scripts::installUv)
- Path: `~/.local/bin/uv` (Unix) or `~/.cargo/bin/uv`
- `uv run` downloads Python if needed and installs `--with` packages into a cached env
- No global Python or venv setup required

### 4. LibreOffice

- System binary: `soffice` or `libreoffice`
- Called via `subprocess.run(['soffice', '--headless', '--convert-to', 'pdf', ...])`
- Used only for PPTX, XLSX, ODT, ODS, ODP
- Must be installed by the user

---

## Data Flow

1. User calls `Converter::toPdf($inputPath, $outputPath)`
2. PHP checks file exists, normalizes paths
3. PHP spawns: `uv run --with pypandoc-binary --with weasyprint ... python/convert.py /abs/input /abs/output`
4. uv ensures Python + deps exist, runs script
5. Python detects extension, chooses engine, writes PDF
6. Process exits with code 0
7. PHP returns `$outputPath`

---

## Dependency Chain

```
Composer
  └── veoksha/pdfit
        ├── illuminate/support
        ├── symfony/process
        └── post-install: curl | sh → uv

uv (at runtime)
  └── Python 3.10+ (downloaded if missing)
        └── pip packages (installed on first run):
              ├── pypandoc-binary  (includes Pandoc)
              ├── weasyprint
              ├── Pillow
              ├── pypdf
              ├── ebooklib
              └── beautifulsoup4

LibreOffice (optional, system)
  └── soffice / libreoffice
```

---

## Security Considerations

- `exec()` / `Process` runs user-controlled paths; validate/sanitize input paths in your app
- Conversion runs in a subprocess; timeout limits runaways
- No network calls from the converter itself (except uv downloading Python/packages on first run)
- Temp files created by Python are cleaned up
