<?php

namespace App\Providers;

use App\Models\Classification;
use App\Models\Disposition;
use App\Models\IncomingLetter;
use App\Models\Institution;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use App\Policies\ClassificationPolicy;
use App\Policies\DispositionPolicy;
use App\Policies\IncomingLetterPolicy;
use App\Policies\InstitutionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(fn (): Password => Password::min(10)->mixedCase()->numbers());
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Classification::class, ClassificationPolicy::class);
        Gate::policy(Disposition::class, DispositionPolicy::class);
        Gate::policy(Institution::class, InstitutionPolicy::class);
        Gate::policy(Activity::class, ActivityLogPolicy::class);
        Gate::policy(IncomingLetter::class, IncomingLetterPolicy::class);

        Event::listen(Login::class, function (Login $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();

            activity('authentication')
                ->causedBy($event->user)
                ->event('login')
                ->withProperties(['ip' => request()->ip()])
                ->log(__('m1.audit_login'));
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            activity('authentication')
                ->causedBy($event->user)
                ->event('logout')
                ->withProperties(['ip' => request()->ip()])
                ->log(__('m1.audit_logout'));
        });

        Event::listen(Failed::class, function (Failed $event): void {
            activity('authentication')
                ->event('failed_login')
                ->withProperties([
                    'identity' => Arr::get($event->credentials, 'email') ?? Arr::get($event->credentials, 'username'),
                    'ip' => request()->ip(),
                ])
                ->log(__('m1.audit_failed_login'));
        });
    }
}
