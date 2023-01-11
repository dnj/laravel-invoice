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
        Schema::create('invoice_payments', function (Blueprint $table) {
			$floatScale = $this->getFloatScale();
            $table->id();
            $table->foreignId('invoice_id');
            $table->foreignId('transaction_id')->nullable();
            $table->timestamps();
            $table->string('method');
            $table->decimal('amount', 10 + $floatScale, $floatScale);
            $table->string('status');
            $table->json('meta')->nullable();

            $table->foreign('Invoice_id')
                ->references('id')
                ->on('invoices');
			$table->foreign('transaction_id')
				->references('id')
				->on('transactions');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_payments');
    }

};
