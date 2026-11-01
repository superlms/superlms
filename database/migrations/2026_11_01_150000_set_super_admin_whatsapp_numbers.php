<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The main super admin's sign-in code goes on WhatsApp to its mobile and
     * its alternative mobile beside the email (App\Livewire\SuperAdmin\Login).
     * These are the two numbers. Sub super admins keep their own numbers.
     */
    public function up(): void
    {
        $numbers = ['mobile_number' => '8864985914'];

        if (Schema::hasColumn('users', 'alternative_mobile')) {
            $numbers['alternative_mobile'] = '9368388050';
        }

        DB::table('users')->where('role', 'super-admin')->update($numbers);
    }

    public function down(): void
    {
        // The numbers they had before are not kept; nothing to put back.
    }
};
