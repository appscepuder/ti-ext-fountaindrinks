<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per issued drink code. The Spire logs token_id and the time on every pour, so
        // its log can be matched to the order here.
        Schema::create('cepuder_fountaindrinks_codes', function(Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('order_id')->index();
            $table->unsignedBigInteger('order_menu_id')->nullable();
            $table->unsignedBigInteger('menu_id')->nullable();
            $table->string('label');
            $table->string('size', 8);
            $table->boolean('sugar')->default(false);
            $table->boolean('flavors')->default(false);
            $table->string('token_id', 24)->unique();
            $table->unsignedInteger('issued_at');
            $table->text('payload');
            $table->string('secret_id', 2);
            $table->unsignedBigInteger('location_id')->nullable();
            $table->timestamps();
        });

        // Drink settings on menu items: a size makes the item a fountain drink
        Schema::table('menus', function(Blueprint $table): void {
            $table->string('spire_size', 8)->nullable();
            $table->boolean('spire_sugar')->default(false);
            $table->boolean('spire_flavors')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cepuder_fountaindrinks_codes');
        Schema::table('menus', function(Blueprint $table): void {
            $table->dropColumn(['spire_size', 'spire_sugar', 'spire_flavors']);
        });
    }
};
