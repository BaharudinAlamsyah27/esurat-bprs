<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cooperation_agreements', function (Blueprint $table): void {
            $table->id();
            $table->string('bss_pks_number', 120);
            $table->string('partner_pks_number', 120)->nullable();
            $table->string('partner_npwp', 50)->nullable();
            $table->date('agreement_date');
            $table->string('partner_name', 255);
            $table->string('subject', 255);
            $table->string('pic_name', 255);
            $table->text('note')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('agreement_date');
            $table->index('bss_pks_number');
            $table->index('partner_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cooperation_agreements');
    }
};
