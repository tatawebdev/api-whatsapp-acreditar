<!-- resources/views/chat.blade.php -->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat WhatsApp</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css">
</head>
<body>

<div class="container mt-4">
    <h2>Chat WhatsApp</h2>
    <div id="chat-box" class="border p-3 mb-3" style="height: 400px; overflow-y: scroll;">
        <!-- Mensagens serão exibidas aqui -->
    </div>
    <div class="input-group">
        <input type="text" id="message-input" class="form-control" placeholder="Digite sua mensagem">
        <button class="btn btn-primary" onclick="sendMessage()">Enviar</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function loadMessages() {
        $.get('/api/conversations', function(data) {
            $('#chat-box').empty();
            data.forEach(conversation => {
                conversation.messages.forEach(message => {
                    $('#chat-box').append(
                        `<div><strong>${message.sent_by_user ? 'Você' : conversation.contact_name}:</strong> ${message.content}</div>`
                    );
                });
            });
        });
    }

    function sendMessage() {
        let message = $('#message-input').val();
        if (message.trim() === '') return;

        $.post('/api/send-message', {
            from: '12345', // ID da conversa do WhatsApp, ajustável conforme necessário
            contact_name: 'Contato', // Nome do contato
            content: message,
            sent_by_user: 1
            
        }, function(response) {
            $('#message-input').val('');
            loadMessages();
        });
    }

    $(document).ready(function() {
        loadMessages();

        // Recarregar mensagens a cada 5 segundos
        // setInterval(loadMessages, 5000);
    });
</script>

</body>
</html>
