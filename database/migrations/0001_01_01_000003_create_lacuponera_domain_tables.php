<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('dui')->unique();
            $table->date('birth_date');
            $table->timestamps();
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('nit')->unique();
            $table->string('address');
            $table->string('phone', 30);
            $table->string('email')->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->decimal('commission_percentage', 5, 2)->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
        });

        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->decimal('regular_price', 10, 2);
            $table->decimal('offer_price', 10, 2);
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->timestamp('redeemable_until');
            $table->unsignedInteger('coupon_limit')->nullable();
            $table->text('description');
            $table->string('status', 20)->default('available')->index();
            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['status', 'starts_at', 'ends_at']);
        });

        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->default('completed')->index();
            $table->decimal('total_amount', 10, 2);
            $table->timestamp('purchased_at');
            $table->timestamps();

            $table->index(['client_id', 'purchased_at']);
        });

        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->constrained()->cascadeOnDelete();
            $table->foreignId('offer_id')->constrained()->restrictOnDelete();
            $table->decimal('unit_price', 10, 2);
            $table->timestamps();

            $table->index(['offer_id', 'purchase_id']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('invoice_number', 50)->unique();
            $table->timestamp('issued_at');
            $table->decimal('total_amount', 10, 2);
            $table->timestamps();
        });

        Schema::create('coupon_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_detail_id')->unique()->constrained()->cascadeOnDelete();
            $table->uuid('code')->unique();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('company_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('approved_by')->constrained('users')->restrictOnDelete();
            $table->string('action', 20);
            $table->decimal('commission_percentage', 5, 2)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['company_id', 'action']);
        });

        Schema::create('payment_simulations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('card_last_four', 4);
            $table->string('card_expiry', 7);
            $table->timestamp('simulated_at');
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_simulations');
        Schema::dropIfExists('company_approvals');
        Schema::dropIfExists('coupon_codes');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('purchase_details');
        Schema::dropIfExists('purchases');
        Schema::dropIfExists('offers');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('clients');
    }
};
