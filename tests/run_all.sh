#!/bin/bash
# Test all format conversions
set -e
cd "$(dirname "$0")/.."
PATH="$HOME/.local/bin:$PATH"
UV="uv run --with pypandoc-binary --with weasyprint --with Pillow --with pypdf --with ebooklib --with beautifulsoup4"
SCRIPT="python/convert.py"
OUT="tests/output"

mkdir -p "$OUT"

test_convert() {
  local ext="$1"
  local file="$2"
  local out="$OUT/result_${ext}.pdf"
  echo -n "  $ext... "
  if $UV $SCRIPT "$file" "$out" 2>/dev/null && [ -f "$out" ]; then
    echo "✓ ($(ls -lh "$out" | awk '{print $5}'))"
    return 0
  else
    echo "✗ FAILED"
    return 1
  fi
}

echo "Testing conversions..."
test_convert html tests/sample.html
test_convert md tests/sample.md
test_convert txt tests/sample.txt
test_convert csv tests/sample.csv
test_convert png tests/sample.png
test_convert jpg tests/sample.jpg
test_convert rtf tests/sample.rtf
test_convert zip tests/sample.zip
test_convert epub tests/gutenberg.epub
test_convert docx "Accounts Payable Specialist Resume Template_One-Column Resum.docx"
test_convert xlsx tests/sample.xlsx
test_convert pptx tests/sample.pptx
test_convert odt tests/sample.odt
echo ""
echo "All conversions completed. Outputs in tests/output/"
