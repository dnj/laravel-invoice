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
        Schema::create('invoices_payments', function (Blueprint $table) {
			$floatScale = $this->getFloatScale();
            $table->id();
            $table->foreignId('invoice_id');
            $table->foreignId('transaction_id')->nullable();
            $table->timestamps();
            $table->string('method');
            $table->decimal('amount', 10 + $floatScale, $floatScale);
            $table->enum('status',['approve','pending','rejected']);
            $table->json('meta')->nullable();

            $table->foreign('Invoice_id')
                ->references('id')
                ->on('invoices');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices_payments');
    }

};
