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
        Schema::create('Equipments', function (Blueprint $table) {
            $table->string('Equipment', 128);
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
            $table->date('NextCertDate')->nullable();
            $table->date('NextCalDate')->nullable();
            $table->date('NextCtrlDate')->nullable();
            $table->integer('NEN3140Int')->default(0);
            $table->integer('MaintInt')->default(0);
            $table->date('NextNEN3140Date')->nullable();
            $table->integer('CalInt')->default(0);
            $table->date('NextMaintDate')->nullable();
            $table->integer('CertInt')->default(0);
            $table->integer('CtrlInt')->default(0);
            $table->date('ExempEndDate')->nullable();
            $table->date('Min_CALD_Date')->nullable();
            $table->float('GrossWeight')->nullable();
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

            $table->primary('Equipment');
            $table->index('Material');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('Equipments');
    }
};
