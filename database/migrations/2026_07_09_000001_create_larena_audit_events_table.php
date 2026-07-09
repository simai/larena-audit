<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('larena_audit_events', static function (Blueprint $table): void {
            $table->id();
            $table->string('source_package');
            $table->string('category');
            $table->string('event_type');
            $table->string('actor');
            $table->string('subject');
            $table->string('severity', 32);
            $table->string('retention_class', 32);
            $table->string('correlation_id');
            $table->timestamp('occurred_at');
            $table->json('payload');
            $table->timestamp('created_at');
            $table->index(['category', 'event_type'], 'larena_audit_category_type_index');
            $table->index('correlation_id', 'larena_audit_correlation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('larena_audit_events');
    }
};
