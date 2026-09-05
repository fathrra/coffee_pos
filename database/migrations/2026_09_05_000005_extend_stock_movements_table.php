<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->foreignId('product_id')->nullable()->change();
            $table->foreignId('ingredient_id')->nullable()->after('product_id')->constrained('ingredients')->nullOnDelete();
            $table->decimal('quantity', 14, 3)->change();
            $table->decimal('before_stock', 14, 3)->nullable()->after('quantity');
            $table->decimal('after_stock', 14, 3)->nullable()->after('before_stock');
            $table->string('reference_type')->nullable()->after('type');
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type');
            $table->string('reason')->nullable()->after('description');
            $table->enum('type', ['in', 'out', 'sale', 'adjustment'])->default('in')->change();
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['ingredient_id']);
            $table->dropColumn(['ingredient_id', 'before_stock', 'after_stock', 'reference_type', 'reference_id', 'reason']);
            $table->foreignId('product_id')->nullable(false)->change();
            $table->integer('quantity')->change();
            $table->enum('type', ['in', 'out'])->default('in')->change();
        });
    }
};