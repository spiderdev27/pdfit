# Conversion Test Results

All formats tested on macOS. Run `bash tests/run_all.sh` to reproduce.

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
| EPUB | .epub | ✓ | pypandoc + weasyprint |
| Word | .docx | ✓ | pypandoc + weasyprint |
| Excel | .xlsx | ✓ | LibreOffice |
| PowerPoint | .pptx | ✓ | LibreOffice |
| OpenDocument Text | .odt | ✓ | LibreOffice |

## LibreOffice required

- .ppt, .pptx, .xls, .xlsx, .odt, .ods, .odp

Install: `brew install --cask libreoffice`  
Add to PATH: `export PATH="/Applications/LibreOffice.app/Contents/MacOS:$PATH"`

## Test Files

- `sample.html`, `sample.md`, `sample.txt`, `sample.csv` - created
- `sample.png`, `sample.jpg` - created
- `sample.rtf` - created
- `sample.zip` - contains html, txt, png
- `gutenberg.epub` - Pride and Prejudice (Gutenberg)
- `Accounts Payable Specialist Resume Template_One-Column Resum.docx` - user's file
