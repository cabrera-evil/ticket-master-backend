<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->uuid('token')->unique();
            $table->string('cardholder');
            $table->string('last_four', 4);
            $table->string('expiry', 5);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        DB::table('payment_simulations')->truncate();

        Schema::table('payment_simulations', function (Blueprint $table) {
            $table->foreignId('card_id')->after('purchase_id')->constrained()->restrictOnDelete();
            $table->dropColumn(['card_last_four', 'card_expiry']);
        });

        Schema::dropIfExists('clients');
    }

    public function down(): void
    {
        Schema::table('payment_simulations', function (Blueprint $table) {
            $table->string('card_last_four', 4)->after('purchase_id');
            $table->string('card_expiry', 7)->after('card_last_four');
            $table->dropForeign(['card_id']);
            $table->dropColumn('card_id');
        });

        Schema::dropIfExists('cards');
    }
};
