<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWebhookDataTable extends Migration
{
    public function up()
    {
        Schema::create('webhook_data', function (Blueprint $table) {
            $table->id();
            $table->string('event_type');
            $table->string('celular');
            $table->string('conversation_id');
            $table->string('api_phone_id');
            $table->string('api_phone_number');
            $table->string('status');
            $table->string('status_id');
            $table->json('conversation');
            $table->json('json'); // Armazena dados JSON
            $table->timestamps(); // Adiciona created_at e updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('webhook_data');
    }
}
