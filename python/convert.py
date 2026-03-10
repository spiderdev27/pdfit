#!/usr/bin/env python3
"""
Universal document to PDF converter.
Supports: docx, doc, pptx, ppt, xlsx, xls, images, html, md, txt, rtf, epub, csv, zip, and more.
"""
import argparse
import csv
import os
import subprocess
import sys
import tempfile
import zipfile
from pathlib import Path


def main():
    parser = argparse.ArgumentParser(description='Convert document to PDF')
    parser.add_argument('input', help='Input file path')
    parser.add_argument('output', help='Output PDF path')
    args = parser.parse_args()

    input_path = Path(args.input).resolve()
    output_path = Path(args.output).resolve()

    if not input_path.exists():
        print(f"Error: Input file not found: {input_path}", file=sys.stderr)
        sys.exit(1)

    ext = input_path.suffix.lower()
    output_path.parent.mkdir(parents=True, exist_ok=True)

    try:
        if ext in IMAGE_EXTENSIONS:
            convert_image(input_path, output_path)
        elif ext in ['.html', '.htm']:
            convert_html(input_path, output_path)
        elif ext in ['.md', '.markdown']:
            convert_markdown(input_path, output_path)
        elif ext == '.txt':
            convert_txt(input_path, output_path)
        elif ext == '.csv':
            convert_csv(input_path, output_path)
        elif ext == '.zip':
            convert_zip(input_path, output_path)
        elif ext in ['.docx', '.doc', '.rtf']:
            convert_document(input_path, output_path, ext)
        elif ext in ['.pptx', '.ppt', '.xlsx', '.xls', '.odt', '.ods', '.odp']:
            convert_office(input_path, output_path)
        elif ext == '.epub':
            convert_epub(input_path, output_path)
        else:
            # Try pypandoc for other text formats, then LibreOffice
            if try_pypandoc(input_path, output_path):
                pass
            elif try_libreoffice(input_path, output_path):
                pass
            else:
                print(f"Error: Unsupported format: {ext}", file=sys.stderr)
                sys.exit(1)
    except Exception as e:
        print(f"Error: {e}", file=sys.stderr)
        sys.exit(1)


IMAGE_EXTENSIONS = {'.png', '.jpg', '.jpeg', '.gif', '.bmp', '.tiff', '.tif', '.webp'}


def convert_image(input_path: Path, output_path: Path) -> None:
    from PIL import Image
    img = Image.open(input_path)
    if img.mode in ('RGBA', 'P'):
        img = img.convert('RGB')
    img.save(output_path, 'PDF', resolution=100.0)


def convert_html(input_path: Path, output_path: Path) -> None:
    from weasyprint import HTML
    html = HTML(filename=str(input_path))
    html.write_pdf(output_path)


def convert_markdown(input_path: Path, output_path: Path) -> None:
    import pypandoc
    md_content = input_path.read_text(encoding='utf-8', errors='replace')
    html_content = pypandoc.convert_text(md_content, 'html', format='md')
    with tempfile.NamedTemporaryFile(mode='w', suffix='.html', delete=False, encoding='utf-8') as f:
        f.write(html_content)
        tmp_html = f.name
    try:
        from weasyprint import HTML
        HTML(filename=tmp_html).write_pdf(output_path)
    finally:
        os.unlink(tmp_html)


def convert_txt(input_path: Path, output_path: Path) -> None:
    content = input_path.read_text(encoding='utf-8', errors='replace')
    html = f'''<!DOCTYPE html><html><head><meta charset="utf-8"></head>
    <body><pre style="font-family: sans-serif; white-space: pre-wrap;">{_escape(content)}</pre></body></html>'''
    with tempfile.NamedTemporaryFile(mode='w', suffix='.html', delete=False, encoding='utf-8') as f:
        f.write(html)
        tmp_html = f.name
    try:
        from weasyprint import HTML
        HTML(filename=tmp_html).write_pdf(output_path)
    finally:
        os.unlink(tmp_html)


def convert_csv(input_path: Path, output_path: Path) -> None:
    rows = list(csv.reader(open(input_path, encoding='utf-8', errors='replace')))
    trs = ''.join(
        '<tr>' + ''.join(f'<td>{_escape(cell)}</td>' for cell in row) + '</tr>'
        for row in rows
    )
    html = f'''<!DOCTYPE html><html><head><meta charset="utf-8">
    <style>table {{ border-collapse: collapse; }} td, th {{ border: 1px solid #333; padding: 6px; }}</style>
    </head><body><table>{trs}</table></body></html>'''
    with tempfile.NamedTemporaryFile(mode='w', suffix='.html', delete=False, encoding='utf-8') as f:
        f.write(html)
        tmp_html = f.name
    try:
        from weasyprint import HTML
        HTML(filename=tmp_html).write_pdf(output_path)
    finally:
        os.unlink(tmp_html)


def convert_zip(input_path: Path, output_path: Path) -> None:
    from pypdf import PdfWriter
    merger = PdfWriter()
    appended = 0
    with zipfile.ZipFile(input_path, 'r') as zf:
        with tempfile.TemporaryDirectory() as tmp:
            for name in sorted(zf.namelist()):
                if name.endswith('/') or name.startswith('__MACOSX'):
                    continue
                ext = Path(name).suffix.lower()
                if ext not in IMAGE_EXTENSIONS and ext not in ['.html', '.htm', '.md', '.txt', '.docx', '.doc', '.pdf']:
                    continue
                zf.extract(name, tmp)
                extracted = Path(tmp) / name
                if not extracted.is_file():
                    continue
                try:
                    pdf_path = Path(tmp) / f"converted_{appended}.pdf"
                    if ext == '.pdf':
                        merger.append(str(extracted))
                        appended += 1
                    elif ext in IMAGE_EXTENSIONS:
                        convert_image(extracted, pdf_path)
                        merger.append(str(pdf_path))
                        appended += 1
                    elif ext in ['.html', '.htm']:
                        convert_html(extracted, pdf_path)
                        merger.append(str(pdf_path))
                        appended += 1
                    elif ext in ['.md', '.markdown']:
                        convert_markdown(extracted, pdf_path)
                        merger.append(str(pdf_path))
                        appended += 1
                    elif ext == '.txt':
                        convert_txt(extracted, pdf_path)
                        merger.append(str(pdf_path))
                        appended += 1
                    elif ext in ['.docx', '.doc']:
                        if try_pypandoc(extracted, pdf_path) or try_libreoffice(extracted, pdf_path):
                            merger.append(str(pdf_path))
                            appended += 1
                except Exception:
                    pass
    if appended == 0:
        # No convertibles found - create a contents listing
        with tempfile.NamedTemporaryFile(mode='w', suffix='.html', delete=False, encoding='utf-8') as f:
            with zipfile.ZipFile(input_path, 'r') as zf:
                names = [n for n in zf.namelist() if not n.endswith('/')]
            html = f'<!DOCTYPE html><html><head><meta charset="utf-8"></head><body><h1>ZIP Contents</h1><ul>' + ''.join(f'<li>{_escape(n)}</li>' for n in names) + '</ul></body></html>'
            f.write(html)
            tmp_html = f.name
        try:
            from weasyprint import HTML
            HTML(filename=tmp_html).write_pdf(output_path)
        finally:
            os.unlink(tmp_html)
    else:
        merger.write(output_path)
        merger.close()


def convert_document(input_path: Path, output_path: Path, ext: str) -> None:
    if not try_pypandoc(input_path, output_path):
        if not try_libreoffice(input_path, output_path):
            raise RuntimeError(f"Could not convert {ext}. Install LibreOffice or ensure pypandoc supports this format.")


def convert_office(input_path: Path, output_path: Path) -> None:
    if not try_libreoffice(input_path, output_path):
        raise RuntimeError("LibreOffice is required for this format. Install: sudo apt install libreoffice")


def convert_epub(input_path: Path, output_path: Path) -> None:
    # Try pypandoc first (simplest, works for most EPUBs)
    if try_pypandoc(input_path, output_path):
        return
    # Fallback: ebooklib extraction
    import ebooklib
    from ebooklib import epub
    from bs4 import BeautifulSoup
    book = epub.read_epub(str(input_path))
    html_parts = []
    items = []
    for item in book.get_items():
        try:
            if item and item.get_type() == ebooklib.ITEM_DOCUMENT:
                items.append(item)
        except (AttributeError, TypeError):
            pass
    for item in items:
        try:
            content = item.get_content() if hasattr(item, 'get_content') else None
            if content:
                soup = BeautifulSoup(content, 'html.parser')
                html_parts.append(str(soup))
        except Exception:
            continue
    if not html_parts:
        raise RuntimeError("Could not extract content from EPUB")
    html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body>' + ''.join(html_parts) + '</body></html>'
    with tempfile.NamedTemporaryFile(mode='w', suffix='.html', delete=False, encoding='utf-8') as f:
        f.write(html)
        tmp_html = f.name
    try:
        from weasyprint import HTML
        HTML(filename=tmp_html).write_pdf(output_path)
    finally:
        os.unlink(tmp_html)


def try_pypandoc(input_path: Path, output_path: Path) -> bool:
    try:
        import pypandoc
        # Use weasyprint as PDF engine (no LaTeX required)
        pypandoc.convert_file(
            str(input_path), 'pdf', outputfile=str(output_path),
            extra_args=['--pdf-engine=weasyprint']
        )
        return output_path.exists()
    except Exception:
        return False


def try_libreoffice(input_path: Path, output_path: Path) -> bool:
    for cmd in ['libreoffice', 'soffice']:
        try:
            out_dir = str(output_path.parent)
            result = subprocess.run(
                [cmd, '--headless', '--convert-to', 'pdf', str(input_path), '--outdir', out_dir],
                capture_output=True,
                text=True,
                timeout=60
            )
            if result.returncode == 0:
                expected = output_path.parent / (input_path.stem + '.pdf')
                if expected.exists():
                    if expected != output_path:
                        expected.rename(output_path)
                    return True
        except (FileNotFoundError, subprocess.TimeoutExpired):
            continue
    return False


def _escape(s: str) -> str:
    return (s.replace('&', '&amp;').replace('<', '&lt;').replace('>', '&gt;').replace('"', '&quot;'))


if __name__ == '__main__':
    main()
