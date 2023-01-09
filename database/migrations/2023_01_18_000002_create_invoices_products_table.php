<?php
 use dnj\Invoice\ModelHelpers;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    use ModelHelpers;

    public function up(): void
    {
        Schema::create('invoices_products', function (Blueprint $table) {
			$floatScale = $this->getFloatScale();
            $table->id();
            $table->string('title', 255);
            $table->foreignId('Invoice_id');
            $table->timestamps();
            $table->decimal('price', 10 + $floatScale, $floatScale);
            $table->decimal('discount', 10 + $floatScale, $floatScale);
            $table->decimal('total_amount', 10 + $floatScale, $floatScale);
            $table->integer('count');
            $table->json('meta')->nullable();
            $table->json('distribution_plan')->nullable();
            $table->json('distribution')->nullable();
            $table->longText('description')->nullable();

            $table->foreign('Invoice_id')
                ->references('id')
                ->on('invoices');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices_products');
    }

};
