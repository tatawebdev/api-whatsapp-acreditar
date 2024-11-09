<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFilesTable extends Migration
{
    public function up()
    {
        Schema::create('files', function (Blueprint $table) {
            // Definindo o ID da tabela
            $table->id();

            // Definindo o relacionamento com a tabela messages
            $table->unsignedBigInteger('message_id')->nullable(); // O relacionamento pode ser nulo

            // Campos relacionados ao arquivo
            $table->string('file_url')->nullable(); // file_url pode ser nulo
            $table->string('file_mime_type')->nullable(); // file_mime_type pode ser nulo
            $table->string('file_sha256')->nullable(); // sha256 pode ser nulo
            $table->integer('file_size')->nullable(); // file_size pode ser nulo
            $table->string('file_id')->nullable(); // file_id pode ser nulo
            $table->string('file_src')->nullable(); // file_src pode ser nulo

            // Timestamps
            $table->timestamps();

            // Chave estrangeira para associar à tabela de mensagens
            $table->foreign('message_id')->references('id')->on('messages')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('files');
    }
}
