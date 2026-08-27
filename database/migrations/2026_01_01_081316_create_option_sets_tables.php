<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('option_sets', function (Blueprint $table) {
        $table->id();
        $table->string('shop_id');
        $table->string('name');
        $table->json('product_ids')->nullable(); 
        $table->boolean('status')->default(1);
        $table->timestamps();
    });

    // Options (Actual Fields)
    Schema::create('options', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('option_set_id');
        $table->string('type');
        $table->string('label');
        $table->string('values')->nullable();
        $table->boolean('required')->default(0);
        $table->integer('position')->default(0);
        $table->timestamps();

        $table->foreign('option_set_id')->references('id')->on('option_sets')->onDelete('cascade');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('option_sets_tables');
    }
};
