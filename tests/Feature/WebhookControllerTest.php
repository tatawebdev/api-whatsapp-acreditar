<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_handle_webhook_with_hub_challenge()
    {
        $response = $this->get('/api/webhook?hub_challenge=12345'); 

        $response->assertStatus(200);
        $response->assertSee('12345');
    }

    /** @test */
    public function it_can_save_webhook_data()
    {
        Storage::fake('webhooks'); // Fake o sistema de arquivos

        $data = [
            'event' => 'new_message',
            'data' => [
                'id' => '12345',
                'content' => 'Exemplo de conteúdo',
            ],
        ];

        $response = $this->postJson('/api/webhook', $data);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'message' => 'Webhook data saved successfully!']);

    }
}
