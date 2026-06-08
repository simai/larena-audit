<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('larena_install_events', static function (Blueprint $table): void {
            $table->id();
            $table->string('event_key')->unique();
            $table->string('source_package');
            $table->string('category');
            $table->string('event_type');
            $table->string('actor');
            $table->string('subject');
            $table->string('severity');
            $table->string('retention_class');
            $table->string('correlation_id');
            $table->string('launch_record_id');
            $table->string('target_step');
            $table->string('result_status');
            $table->string('evidence_path')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('larena_install_events');
    }
};
