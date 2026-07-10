<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $originalSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS mode')->mode;

        // MariaDB 10.2+ folded NO_ZERO_DATE into strict mode, which would otherwise
        // reject the spec's '0000-00-00' column defaults at CREATE TABLE time.
        DB::statement("SET SESSION sql_mode = ''");

        try {
            $this->createTable();
        } finally {
            DB::statement("SET SESSION sql_mode = '{$originalSqlMode}'");
        }
    }

    private function createTable(): void
    {
        Schema::create('Equipments', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->charset = 'utf8mb4';
            $table->collation = 'utf8mb4_unicode_ci';

            $table->string('Equipment', 128)->primary();
            $table->string('Material', 191)->nullable();
            $table->string('MaterialWithoutFet', 255);
            $table->string('Description', 191)->nullable();
            $table->string('IH09Description', 255)->nullable();
            $table->string('Room', 191)->nullable();
            $table->string('Plant', 255)->nullable();
            $table->string('Location', 191)->nullable();
            $table->string('Sloc', 191)->nullable();
            $table->string('SuperEq', 128)->nullable();
            $table->string('ManufactSerialNumber', 255);
            $table->string('SerNo', 191)->nullable();
            $table->string('UserStatus', 255);
            $table->string('SystemStatus', 255);
            $table->string('Dimensions', 255)->nullable();
            $table->integer('CleaningCounter_limit')->default(0);
            $table->integer('CleaningCounter_current')->default(0);
            $table->string('ToolCompetence', 255);
            $table->date('NextCertDate')->default('0000-00-00');
            $table->date('NextCalDate')->default('0000-00-00');
            $table->date('NextCtrlDate')->default('0000-00-00');
            $table->integer('NEN3140Int');
            $table->integer('MaintInt');
            $table->date('NextNEN3140Date')->default('0000-00-00');
            $table->integer('CalInt');
            $table->date('NextMaintDate')->default('0000-00-00');
            $table->integer('CertInt');
            $table->integer('CtrlInt');
            $table->date('ExempEndDate')->default('0000-00-00');
            $table->date('Min_CALD_Date')->nullable();
            $table->float('GrossWeight', 24)->nullable();
            $table->string('current_status', 1000);
            $table->timestamp('needed_time')->nullable();
            $table->timestamp('return_time')->nullable();
            $table->string('workcenter', 1000);
            $table->string('material_status', 255);
            $table->string('StockType', 255)->nullable();
            $table->string('SpecialStock', 255)->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->string('CreatedBy', 255);
            $table->timestamp('ChangedOn')->nullable();
            $table->string('ChangedBy', 255);

            $table->index('Material', 'Material');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('Equipments');
    }
};
