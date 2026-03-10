<?php

namespace Veoksha\LaravelUniversalConverter;

class Scripts
{
    public static function installUv(): void
    {
        $uvPath = (PHP_OS_FAMILY === 'Windows')
            ? getenv('USERPROFILE') . '\\.local\\bin\\uv.exe'
            : getenv('HOME') . '/.local/bin/uv';

        if (file_exists($uvPath) && is_executable($uvPath)) {
            return;
        }

        if (! empty(shell_exec('which uv 2>/dev/null'))) {
            return;
        }

        echo "Installing uv (Python runner for converter)...\n";

        $cmd = PHP_OS_FAMILY === 'Windows'
            ? 'powershell -ExecutionPolicy ByPass -c "irm https://astral.sh/uv/install.ps1 | iex"'
            : 'curl -LsSf https://astral.sh/uv/install.sh | sh';

        passthru($cmd, $code);

        if ($code !== 0) {
            echo "Warning: uv install may have failed. Install manually: curl -LsSf https://astral.sh/uv/install.sh | sh\n";
        }
    }
}
