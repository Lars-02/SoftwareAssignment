<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('Equipments')) {
            Schema::create('Equipments', function (Blueprint $table) {
                $table->id();
                $table->string('Equipment', 128)->index('Equipment');
                $table->string('Material', 191)->nullable()->index('Material');
                $table->string('MaterialWithoutFet', 255);
                $table->string('Description', 191)->nullable();
                $table->string('IH09Description', 255)->nullable();
                $table->string('Room', 191)->nullable();
                $table->string('Plant', 255)->nullable();
                $table->string('Location', 191)->nullable();
                $table->string('Sloc', 191)->nullable();
                $table->string('SuperEq', 128)->nullable();
                $table->string('ManufactSerialNumber', 255)->nullable();
                $table->string('SerNo', 191)->nullable();
                $table->string('UserStatus', 255);
                $table->string('SystemStatus', 255);
                $table->string('Dimensions', 255)->nullable();
                $table->integer('CleaningCounter_limit')->default(0);
                $table->integer('CleaningCounter_current')->default(0);
                $table->string('ToolCompetence', 255)->nullable();
                $table->date('NextCertDate')->nullable()->default(null);
                $table->date('NextCalDate')->nullable()->default(null);
                $table->date('NextCtrlDate')->nullable()->default(null);
                $table->integer('NEN3140Int')->nullable();
                $table->integer('MaintInt')->nullable();
                $table->date('NextNEN3140Date')->nullable()->default(null);
                $table->integer('CalInt')->nullable();
                $table->date('NextMaintDate')->nullable()->default(null);
                $table->integer('CertInt')->nullable();
                $table->integer('CtrlInt')->nullable();
                $table->date('ExempEndDate')->nullable()->default(null);
                $table->date('Min_CALD_Date')->nullable();
                $table->float('GrossWeight')->nullable();
                $table->string('current_status', 1000);
                $table->timestamp('needed_time')->nullable();
                $table->timestamp('return_time')->nullable();
                $table->string('workcenter', 1000);
                $table->string('material_status', 255)->nullable();
                $table->string('StockType', 255)->nullable();
                $table->string('SpecialStock', 255)->nullable();
                $table->date('CreatedOn')->nullable();
                $table->string('CreatedBy', 255);
                $table->date('ChangedOn')->nullable();
                $table->string('ChangedBy', 255);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Equipments');
    }
};
