<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('category')->default('General');
            $table->string('question');
            $table->text('answer')->nullable();
            $table->timestamps();
        });

        // Seed the existing default FAQs so the public page is not empty.
        $now      = now();
        $defaults = [
            ['General',         'What is SUPERLMS?', 'SUPERLMS is an affordable, all-in-one Learning Management System for schools. It covers admissions, attendance, timetable, exams, fees, study material and parent communication — all from one platform, with apps for admins, teachers, students and parents.'],
            ['Pricing',         'How much does it cost?', 'Pricing is simple and transparent, designed to be affordable for schools of every size. Visit our Pricing page or request a demo and we will share a plan tailored to your student count.'],
            ['Product',         'Is there a mobile app?', 'Yes. SUPERLMS has dedicated mobile apps for Android and iOS, so admins, teachers, students and parents can stay connected from anywhere.'],
            ['Fees',            'Can parents pay fees online?', 'Absolutely. Parents can pay fees securely online and receive instant digital receipts, while your accounts team gets automatic reconciliation and dues tracking.'],
            ['Onboarding',      'How long does it take to set up?', 'Most schools go live within a few days. Our onboarding team helps you import your data, configure classes and fees, and trains your staff so the transition is smooth.'],
            ['Security',        "Is my school's data secure?", 'Yes. Your data is stored securely, access is role-based, and payments are processed through trusted, secure gateways. Your information is never shared without your consent.'],
            ['Support',         'Do you provide training and support?', 'Of course. We provide hands-on onboarding, staff training, and ongoing support over call, chat and WhatsApp so you are never left on your own.'],
            ['Getting Started', 'How do I get started?', 'Simply request a free demo or contact us. We will walk you through the platform and help you choose the right plan.'],
        ];

        $rows = [];
        foreach ($defaults as $d) {
            $rows[] = [
                'category'   => $d[0],
                'question'   => $d[1],
                'answer'     => $d[2],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('faqs')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
