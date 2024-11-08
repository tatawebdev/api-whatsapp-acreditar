<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


class CreatePhoneTokenFcmTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('phone_token_fcm', function (Blueprint $table) {
            $table->id(); // ID da tabela
            $table->string('phone_number')->nullable(); // Número de telefone (pode ser nulo)
            $table->string('fcm_token'); // Token FCM
            $table->timestamps(); // timestamps: created_at, updated_at

            // Index para otimizar consultas por telefone ou token
            $table->index('phone_number');
            $table->index('fcm_token');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('phone_token_fcm');
    }
}
