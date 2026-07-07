<?php

namespace App\Modules\SLA\Commands;

use App\Modules\SLA\Services\SlaService;
use Illuminate\Console\Command;

class CheckSlaBreaches extends Command
{
    protected $signature = 'sla:check';

    protected $description = 'Detect and escalate breached complaint SLAs';

    public function __construct(
        private SlaService $slaService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $count = $this->slaService->detectBreaches();

        $this->info("SLA check completed. {$count} breach(es) detected.");

        return self::SUCCESS;
    }
}
