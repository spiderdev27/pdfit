<?php

/**
 * Install uv (Python package manager) if not present.
 * uv enables running Python without system Python install.
 */
$packageRoot = dirname(__DIR__);

// Check if uv is already available
$uvPath = getenv('HOME') . '/.local/bin/uv';
$uvPathAlt = getenv('HOME') . '/.cargo/bin/uv';

if (PHP_OS_FAMILY === 'Windows') {
    $uvPath = getenv('USERPROFILE') . '\\.local\\bin\\uv.exe';
    $uvPathAlt = getenv('USERPROFILE') . '\\.cargo\\bin\\uv.exe';
}

$uvExists = (file_exists($uvPath) && is_executable($uvPath))
    || (file_exists($uvPathAlt) && is_executable($uvPathAlt))
    || !empty(shell_exec('which uv 2>/dev/null'));

if ($uvExists) {
    exit(0);
}

echo "Installing uv (Python runner)...\n";

if (PHP_OS_FAMILY === 'Windows') {
    $cmd = 'powershell -ExecutionPolicy ByPass -c "irm https://astral.sh/uv/install.ps1 | iex"';
} else {
    $cmd = 'curl -LsSf https://astral.sh/uv/install.sh | sh';
}

$output = [];
exec($cmd . ' 2>&1', $output, $code);

if ($code !== 0) {
    echo "Warning: Could not install uv automatically. You may need to install it manually:\n";
    echo "  curl -LsSf https://astral.sh/uv/install.sh | sh\n";
    echo "Then ensure ~/.local/bin is in your PATH.\n";
}

exit(0);
