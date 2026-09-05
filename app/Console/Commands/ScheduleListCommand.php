<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;

class ScheduleListCommand extends Command
{
    protected $signature = 'scf:schedule:list {--json : Output machine-readable JSON}';

    protected $description = 'List registered SCF scheduled operations';

    public function handle(): int
    {
        $events = app(Schedule::class)->events();

        $rows = collect($events)->map(function ($event) {
            return [
                'name' => $event->description ?? $event->mutexName(),
                'expression' => $event->expression,
                'command' => $event->command ?? $event->description ?? 'job',
                'without_overlapping' => $event->withoutOverlapping,
            ];
        })->values()->all();

        if ($this->option('json')) {
            $this->line((string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info(__('scf.release.schedule_list_heading'));
        $this->line(__('scf.release.schedule_cron_hint'));

        if ($rows === []) {
            $this->warn(__('scf.release.schedule_empty'));

            return self::SUCCESS;
        }

        $this->table(
            [__('scf.release.schedule_name'), __('scf.release.schedule_expression'), __('scf.release.schedule_command')],
            collect($rows)->map(fn ($row) => [$row['name'], $row['expression'], $row['command']])->all(),
        );

        return self::SUCCESS;
    }
}
