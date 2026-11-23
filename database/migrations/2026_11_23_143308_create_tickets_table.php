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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Human-friendly reference such as TCK-20261123-AB12CD
            $table->string('ticket_number')->unique();
            $table->string('title');
            $table->text('description');
            // App\Enums\TicketStatus values
            $table->string('status')->default('open')->index();
            // App\Enums\TicketPriority values
            $table->string('priority')->default('medium')->index();
            // The user who raised the ticket (its author)
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            // The user working on the ticket, if any
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
