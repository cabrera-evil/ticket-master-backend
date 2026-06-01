<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('slug')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('offers', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
            $table->string('image_url')->nullable()->after('description');
            $table->boolean('is_featured')->default(false)->after('image_url')->index();
            $table->unsignedInteger('featured_sort_order')->default(0)->after('is_featured');

            $table->index(['category_id', 'status']);
            $table->index(['is_featured', 'featured_sort_order']);
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropIndex(['category_id', 'status']);
            $table->dropIndex(['is_featured', 'featured_sort_order']);
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn(['image_url', 'is_featured', 'featured_sort_order']);
        });

        Schema::dropIfExists('categories');
    }
};
