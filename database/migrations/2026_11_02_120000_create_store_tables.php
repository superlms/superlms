<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Super Admin → Store: the products SuperLMS sells to schools, and the
     * invoices (with an agreement) it sends them (App\Livewire\SuperAdmin\Store).
     * An invoice line keeps its own title and price, so a product edited or
     * deleted later leaves the invoices already made as they were.
     */
    public function up(): void
    {
        if (!Schema::hasTable('store_products')) {
            Schema::create('store_products', function (Blueprint $table) {
                $table->id();
                $table->string('title');
                $table->string('category', 100)->nullable();
                $table->string('sku', 100)->nullable();
                $table->decimal('price', 12, 2);
                $table->decimal('mrp', 12, 2)->nullable();
                $table->decimal('gst_rate', 5, 2)->default(0);
                $table->string('unit', 30)->nullable();
                $table->integer('stock')->nullable();
                $table->text('description')->nullable();
                $table->json('specifications')->nullable();   // [{label, value}]
                $table->json('images')->nullable();           // [url, …] — the first is the cover
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'category']);
            });
        }

        if (!Schema::hasTable('store_invoices')) {
            Schema::create('store_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('number', 50)->unique();
                $table->unsignedBigInteger('organization_id');
                $table->date('invoice_date');
                $table->date('due_date')->nullable();
                $table->string('status', 20)->default('draft');   // draft | sent | paid | cancelled
                $table->json('bill_to')->nullable();              // the school as it was when made
                $table->decimal('subtotal', 12, 2)->default(0);
                $table->decimal('tax_total', 12, 2)->default(0);
                $table->decimal('discount', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->text('notes')->nullable();
                $table->string('agreement_title')->nullable();
                $table->longText('agreement_body')->nullable();
                $table->string('agreement_file_url', 1000)->nullable();
                $table->string('agreement_file_name')->nullable();
                $table->timestamp('agreement_signed_at')->nullable();
                $table->timestamp('sent_at')->nullable();
                $table->string('sent_to', 1000)->nullable();
                $table->date('paid_at')->nullable();
                $table->string('payment_mode', 50)->nullable();
                $table->string('payment_reference')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'status']);
                $table->index('invoice_date');
            });
        }

        if (!Schema::hasTable('store_invoice_items')) {
            Schema::create('store_invoice_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('store_invoice_id');
                $table->unsignedBigInteger('store_product_id')->nullable();
                $table->string('title');
                $table->text('description')->nullable();
                $table->decimal('quantity', 10, 2)->default(1);
                $table->string('unit', 30)->nullable();
                $table->decimal('unit_price', 12, 2)->default(0);
                $table->decimal('gst_rate', 5, 2)->default(0);
                $table->decimal('amount', 12, 2)->default(0);   // quantity × unit price
                $table->decimal('tax', 12, 2)->default(0);
                $table->decimal('total', 12, 2)->default(0);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('store_invoice_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_invoice_items');
        Schema::dropIfExists('store_invoices');
        Schema::dropIfExists('store_products');
    }
};
