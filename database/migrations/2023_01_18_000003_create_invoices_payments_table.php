<?php

 use dnj\Invoice\ModelHelpers;
 use Illuminate\Database\Migrations\Migration;
 use Illuminate\Database\Schema\Blueprint;
 use Illuminate\Support\Facades\Schema;

 return new class() extends Migration {
     use ModelHelpers;

     public function up(): void
     {
         Schema::create('invoices_payments', function (Blueprint $table) {
             $floatScale = $this->getFloatScale();
             $table->id();
             $table->foreignId('invoice_id')->constrained('invoices')->cascadeOnDelete();
             $table->foreignId('transaction_id')->nullable()->constrained('transactions');
             $table->timestamps();
             $table->string('method');
             $table->decimal('amount', 10 + $floatScale, $floatScale);
             $table->enum('status',['approved','pending','rejected'])->default('pending');
             $table->json('meta')->nullable();
         });
     }

     public function down(): void
	 
     {
         Schema::dropIfExists('invoice_payments');
     }
 };
