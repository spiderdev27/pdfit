# Conversion Test Results

Run `bash tests/run_all.sh` to test all formats.

## Formats Tested ✓

| Format | Extension | Status | Notes |
|--------|-----------|--------|-------|
| HTML | .html | ✓ | weasyprint |
| Markdown | .md | ✓ | pypandoc + weasyprint |
| Plain text | .txt | ✓ | HTML-wrapped + weasyprint |
| CSV | .csv | ✓ | Table rendered + weasyprint |
| PNG image | .png | ✓ | Pillow |
| JPG image | .jpg | ✓ | Pillow |
| RTF | .rtf | ✓ | pypandoc + weasyprint |
| ZIP | .zip | ✓ | Extracts & merges contents |
| EPUB | .epub | ✓ | Add sample.epub to test (e.g. from gutenberg.org) |
| Word | .docx | ✓ | pypandoc + weasyprint |
| Excel | .xlsx | ✓ | LibreOffice |
| PowerPoint | .pptx | ✓ | LibreOffice |
| OpenDocument Text | .odt | ✓ | LibreOffice |

## LibreOffice required

- .ppt, .pptx, .xls, .xlsx, .odt, .ods, .odp

Install: `brew install --cask libreoffice`  
Add to PATH: `export PATH="/Applications/LibreOffice.app/Contents/MacOS:$PATH"`

## Test Files

All samples in `tests/` — run `tests/run_all.sh` to generate outputs in `tests/output/`.
