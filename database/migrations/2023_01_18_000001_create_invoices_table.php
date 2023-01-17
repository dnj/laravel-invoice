<?php

 use dnj\Invoice\ModelHelpers;
 use Illuminate\Database\Migrations\Migration;
 use Illuminate\Database\Schema\Blueprint;
 use Illuminate\Support\Facades\Schema;

 return new class() extends Migration {
     use ModelHelpers;

     public function up(): void
     {
         Schema::create('invoices', function (Blueprint $table) {
             $floatScale = $this->getFloatScale();
             $table->id();
             $table->string('title', 255);
             $table->foreignId('user_id');
             $table->foreignId('currency_id')->constrained($this->getCurrencyTable());
             $table->timestamps();
             $table->decimal('amount', 10 + $floatScale, $floatScale)->nullable();
             $table->json('meta')->nullable();
             $table->dateTime('paid_at')->nullable();
             $table->enum('status',['paid','unpaid'])->default('unpaid');
			 
             $userTable = $this->getUserTable();
             if ($userTable) {
                 $table->foreign('user_id')
                     ->references('id')
                     ->on($userTable);
             } else {
                 $table->index('user_id');
             }
         });
     }

     public function down(): void
     {
         Schema::dropIfExists('invoices');
     }
 };
