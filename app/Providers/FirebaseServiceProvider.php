<?php

namespace App\Providers;

use App\Models\AboutApp;
use App\Models\Admin\ContactSuperAdmin;
use App\Models\Admin\RateLms;
use App\Models\Admin\TermAndCondition;
use App\Models\PrivacyPolicy;
use App\Models\SuperAdmin\CreditQuery;
use App\Models\TermOfUse;
use App\Models\WebsiteContact;
use App\Models\WebsiteDemo;
use App\Services\ActivityNotifier;
use Illuminate\Support\ServiceProvider;
use Kreait\Firebase\Factory;

class FirebaseServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register()
    {
        $this->app->singleton('firebase', function () {
            $factory = (new Factory)
                ->withServiceAccount(config('services.firebase.credentials'))
                ->withDatabaseUri(config('services.firebase.database_url'));

            return $factory;
        });
    }

    /**
     * Bootstrap services.
     *
     * Super-admin activity notifications: when any of these records is created
     * (from any panel), push a notification to all super-admin users.
     */
    public function boot(): void
    {
        AboutApp::created(fn() => ActivityNotifier::toSuperAdmins(
            'About App updated', 'The About App content has been updated.', ['type' => 'about_app']
        ));

        PrivacyPolicy::created(fn() => ActivityNotifier::toSuperAdmins(
            'Privacy Policy updated', 'The Privacy Policy has been updated.', ['type' => 'privacy_policy']
        ));

        TermAndCondition::created(fn() => ActivityNotifier::toSuperAdmins(
            'Terms & Conditions updated', 'The Terms & Conditions have been updated.', ['type' => 'terms_condition']
        ));

        TermOfUse::created(fn() => ActivityNotifier::toSuperAdmins(
            'Terms of Use updated', 'The Terms of Use have been updated.', ['type' => 'terms_of_use']
        ));

        RateLms::created(fn($rating) => ActivityNotifier::toSuperAdmins(
            'New school rating', 'A school has submitted a rating.', ['type' => 'rating', 'id' => $rating->id]
        ));

        WebsiteDemo::created(fn($demo) => ActivityNotifier::toSuperAdmins(
            'New demo enquiry', 'A new demo enquiry has been received.', ['type' => 'enquiry', 'id' => $demo->id]
        ));

        WebsiteContact::created(fn($contact) => ActivityNotifier::toSuperAdmins(
            'New enquiry', 'A new contact enquiry has been received.', ['type' => 'enquiry', 'id' => $contact->id]
        ));

        ContactSuperAdmin::created(fn($query) => ActivityNotifier::toSuperAdmins(
            'New support query', 'A new support query has been received.', ['type' => 'support', 'id' => $query->id]
        ));

        CreditQuery::created(fn($credit) => ActivityNotifier::toSuperAdmins(
            'New credit request', 'A school has requested credit.', ['type' => 'credit', 'id' => $credit->id]
        ));
    }
}
