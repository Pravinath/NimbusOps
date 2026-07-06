<?php

namespace App\Providers;

use App\Models\Complaint;
use App\Modules\Complaint\Policies\ComplaintPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Modules\AIClassification\Contracts\AIClassificationProvider;
use App\Modules\AIClassification\Providers\MockAIProvider;
use App\Models\WorkOrder;
use App\Modules\WorkOrder\Policies\WorkOrderPolicy;


class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            AIClassificationProvider::class,
            MockAIProvider::class
        );
    }

    public function boot(): void
    {
        Gate::policy(Complaint::class, ComplaintPolicy::class);
        Gate::policy(WorkOrder::class, WorkOrderPolicy::class);
    }
}