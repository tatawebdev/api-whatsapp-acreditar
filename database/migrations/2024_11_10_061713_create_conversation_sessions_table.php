<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('conversation_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->string('from');
            $table->dateTime('session_start');
            $table->dateTime('session_end');

            $table->string('whatsapp_conversation_id', 255)->nullable();
            $table->string('whatsapp_origin_type', 50)->nullable();
            $table->unsignedBigInteger('whatsapp_expiration_timestamp')->nullable();



            $table->boolean('whatsapp_billable')->nullable(); 
            $table->string('whatsapp_pricing_model', 50)->nullable();
            $table->string('whatsapp_pricing_category', 50)->nullable(); 

            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('conversation_sessions');
    }
};
