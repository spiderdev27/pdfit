<?php

namespace Veoksha\LaravelUniversalConverter\Console;

use Illuminate\Console\Command;
use Veoksha\LaravelUniversalConverter\Converter;

class ConverterCheckCommand extends Command
{
    protected $signature = 'converter:check';

    protected $description = 'Check if the converter dependencies (uv, Python) are available';

    public function handle(Converter $converter): int
    {
        $this->info('Checking converter dependencies...');

        // Check uv
        $uvPath = getenv('HOME') . '/.local/bin/uv';
        if (PHP_OS_FAMILY === 'Windows') {
            $uvPath = getenv('USERPROFILE') . '\\.local\\bin\\uv.exe';
        }
        $uvFound = file_exists($uvPath) || ! empty(shell_exec('which uv 2>/dev/null'));

        if ($uvFound) {
            $this->info('  uv: ✓');
        } else {
            $this->error('  uv: ✗ not found. Run: curl -LsSf https://astral.sh/uv/install.sh | sh');
            $this->newLine();
            return self::FAILURE;
        }

        // Check Python script exists
        $scriptPath = dirname(__DIR__, 2) . '/python/convert.py';
        if (file_exists($scriptPath)) {
            $this->info('  Python script: ✓');
        } else {
            $this->error('  Python script: ✗ not found');
            return self::FAILURE;
        }

        $this->info('  All checks passed. You can use Converter::toPdf()');
        $this->newLine();

        return self::SUCCESS;
    }
}
