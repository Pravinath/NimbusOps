<?php

namespace App\Providers;

use App\Models\Complaint;
use App\Modules\Complaint\Policies\ComplaintPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Complaint::class, ComplaintPolicy::class);
    }
}