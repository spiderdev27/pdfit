# Supported Formats Reference

## Summary Table

| Extension | Format | Engine | System Dep |
|-----------|--------|--------|------------|
| .html, .htm | HTML | weasyprint | — |
| .md, .markdown | Markdown | pypandoc + weasyprint | — |
| .txt | Plain text | weasyprint | — |
| .rtf | Rich text | pypandoc | — |
| .csv | CSV | weasyprint (table) | — |
| .png, .jpg, .jpeg, .gif, .bmp, .tiff, .tif, .webp | Images | Pillow | — |
| .docx, .doc | Word | pypandoc / LibreOffice | LibreOffice for .doc |
| .pptx, .ppt | PowerPoint | LibreOffice | ✓ |
| .xlsx, .xls | Excel | LibreOffice | ✓ |
| .odt, .ods, .odp | OpenDocument | LibreOffice | ✓ |
| .epub | E-book | pypandoc / ebooklib | — |
| .zip | Archive | extract + convert + merge | — |

---

## Format Details

### HTML (.html, .htm)

- **Engine:** WeasyPrint
- **Behavior:** Renders HTML/CSS to PDF
- **Limitations:** Some CSS (flexbox, grid) may not be fully supported

### Markdown (.md, .markdown)

- **Engine:** pypandoc → HTML, then weasyprint → PDF
- **Behavior:** Full Markdown support (tables, code, etc.)

### Plain text (.txt)

- **Engine:** Wrapped in `<pre>` HTML, then weasyprint
- **Behavior:** Preserves line breaks and whitespace

### CSV (.csv)

- **Engine:** Parsed as table, rendered to HTML, then weasyprint
- **Behavior:** First row as header, basic table styling

### Images (.png, .jpg, .jpeg, .gif, .bmp, .tiff, .tif, .webp)

- **Engine:** Pillow
- **Behavior:** One image per PDF page; RGBA/P converted to RGB

### DOCX, DOC (.docx, .doc)

- **Engine:** pypandoc + weasyprint (primary); LibreOffice (fallback for .doc)
- **Behavior:** Best quality for DOCX via pypandoc

### PowerPoint, Excel, OpenDocument (.pptx, .xlsx, .odt, etc.)

- **Engine:** LibreOffice headless
- **Behavior:** `soffice --headless --convert-to pdf`
- **Requirement:** LibreOffice must be installed and on PATH

### EPUB (.epub)

- **Engine:** pypandoc (primary); ebooklib (fallback)
- **Behavior:** Well-formed EPUBs work best; some minimal EPUBs may fail

### ZIP (.zip)

- **Engine:** zipfile + per-format engines + pypdf merge
- **Behavior:**
  - Extracts supported files (images, HTML, MD, TXT, DOCX, PDF)
  - Converts each to PDF
  - Merges into one PDF in sorted (by name) order
  - If nothing convertible: PDF listing of ZIP contents
