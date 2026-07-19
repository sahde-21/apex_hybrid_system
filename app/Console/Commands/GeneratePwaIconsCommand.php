<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GeneratePwaIconsCommand extends Command
{
    protected $signature = 'pwa:generate-icons';

    protected $description = 'Generate SCF PWA icons and splash screens';

    public function handle(): int
    {
        $script = base_path('scripts/generate-pwa-icons.php');

        if (! is_file($script)) {
            $this->error('Missing scripts/generate-pwa-icons.php');

            return self::FAILURE;
        }

        passthru(escapeshellarg(PHP_BINARY).' '.escapeshellarg($script), $exit);

        if ($exit !== 0) {
            $this->error('Icon generation failed.');

            return self::FAILURE;
        }

        $this->info('PWA icons and splash screens generated.');

        return self::SUCCESS;
    }
}
