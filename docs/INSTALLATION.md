# Installation Guide

## Quick start

```bash
composer require veoksha/pdfit
```

That’s it for formats like DOCX, HTML, images, Markdown, EPUB, ZIP.

---

## Step-by-step

### 1. Require the package

```bash
composer require veoksha/pdfit
```

### 2. uv (Python runner)

The package tries to install **uv** on `composer install` / `composer update`. If that fails:

```bash
# macOS / Linux
curl -LsSf https://astral.sh/uv/install.sh | sh

# Add to PATH
echo 'export PATH="$HOME/.local/bin:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

### 3. LibreOffice (optional, for Office formats)

Needed for **PPTX, XLSX, ODT, ODS, ODP**.

#### macOS (Homebrew)

```bash
brew install --cask libreoffice
echo 'export PATH="/Applications/LibreOffice.app/Contents/MacOS:$PATH"' >> ~/.zshrc
source ~/.zshrc
```

#### Ubuntu / Debian

```bash
sudo apt update
sudo apt install libreoffice
```

#### CentOS / RHEL

```bash
sudo yum install libreoffice
```

#### Docker

```dockerfile
RUN apt-get update && apt-get install -y libreoffice
```

### 4. Verify

```bash
php artisan converter:check
```

Expected: `uv: ✓`, `Python script: ✓`, `All checks passed`.

---

## Server deployment

### VPS / cloud (Ubuntu)

1. Install the package as above
2. Install LibreOffice if you need Office formats:
   ```bash
   sudo apt install libreoffice
   ```
3. Add LibreOffice to PATH in `.bashrc` or `.profile` if needed:
   ```bash
   export PATH="/usr/bin:$PATH"
   ```
   (On apt, `soffice` is usually in `/usr/bin` already)

### Docker

```dockerfile
FROM php:8.2-fpm

# ... your PHP setup ...

RUN apt-get update && apt-get install -y \
    libreoffice \
    curl \
    unzip

# Install Composer, then:
RUN composer require veoksha/pdfit
```

### Shared hosting

Conversion may not work if:

- `exec()` / `shell_exec()` is disabled
- You can’t install uv or LibreOffice

Use a VPS, cloud VM, or container for reliable conversion.
