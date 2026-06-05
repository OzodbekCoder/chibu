<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Deploy extends Command
{
    protected $signature   = 'app:deploy';
    protected $description = 'Run all post-deploy tasks (migrate, cache, storage link)';

    public function handle(): int
    {
        $steps = [
            'migrate'        => ['--force' => true],
            'storage:link'   => [],
            'config:clear'   => [],
            'route:clear'    => [],
            'view:clear'     => [],
            'cache:clear'    => [],
            'config:cache'   => [],
            'route:cache'    => [],
            'view:cache'     => [],
        ];

        foreach ($steps as $command => $args) {
            $this->line("→ {$command}");
            try {
                $this->call($command, $args);
            } catch (\Throwable $e) {
                $this->warn("   skip ({$command}): " . $e->getMessage());
            }
        }

        $this->info('✅ Deploy tasks done');
        return self::SUCCESS;
    }
}
