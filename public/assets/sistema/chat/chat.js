let conversationOne = {};
const loadConversations = () => {
    const url = "/chat/conversations";
    const data = {};

    ajaxSimpleRequest(url, data, (response) => {

        response.forEach(conversation => {

            // Supondo que o status venha da resposta
            let status = conversation.status || 'online';  // Se não tiver status, assume 'online'
            let avatar = conversation.avatar || 'assets/media/avatars/avatar7.jpg';  // Se não tiver avatar, usa o padrão

            let statusClass = status == 'online' ? 'bg-success' :
                status == 'busy' ? 'bg-danger' :
                    status == 'away' ? 'bg-warning' :
                        'bg-muted';

            let conversationItem = `
                <li data-id="${conversation.id}" data-contact_name="${conversation.contact_name}" data-from="${conversation.from}">
                    <a class="d-flex py-2" href="javascript:void(0)">
                        <div class="flex-shrink-0 mx-3 overlay-container">
                            <img class="img-avatar img-avatar48" src="${avatar}" alt="">
                            <span class="overlay-item item item-tiny item-circle border border-2 border-white ${statusClass}"></span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold">${conversation.contact_name}</div>
                            <div class="fw-normal fs-sm text-muted">${conversation.from}</div>
                        </div>
                    </a>
                </li>
            `;

            // Adicionar à categoria apropriada
            $(`#${status}-conversations ul`).append(conversationItem);
        });

        // Adicionar o evento de clique para cada item de conversa
        $('#online-conversations ul li, #busy-conversations ul li, #away-conversations ul li, #offline-conversations ul li').on('click', function () {
            conversationOne = {
                id: $(this).data('id'),
                contact_name: $(this).data('contact_name'),
                from: $(this).data('from'),
            };

            loadConversationDetails();
        });
    });
};


const loadConversationDetails = () => {
    const detailsUrl = `/chat/conversations/${conversationOne.id}`;
    const requestData = { id: conversationOne.id };

    // Chamada AJAX
    ajaxSimpleRequest(detailsUrl, requestData, (response) => {
        console.log('Detalhes da conversa:', JSON.stringify(response));





        const chatMessages = document.querySelector('.js-chat-messages');
        const chatSendMessages = document.querySelector('input.js-chat-input');
        const conversationHeader = document.querySelector('.block-title');  // Título da conversa

        if (chatMessages) {
            chatMessages.setAttribute('data-chat-id', conversationOne.id);
            chatSendMessages.setAttribute('data-target-chat-id', conversationOne.id);

            chatMessages.innerHTML = ""; // Limpa as mensagens
            chatMessages.scrollTop = 0;
        } else {
            console.error("Elemento de mensagens não encontrado.");
        }

        handleImageUpload(conversationOne.id);

        if (conversationHeader) {
            const contactName = conversationOne.contact_name || 'Nome do Contato'; // Se o nome não for encontrado, use um valor padrão
            const contactPhone = conversationOne.from || 'Telefone não disponível'; // Se o telefone não for encontrado, use um valor padrão

            conversationHeader.innerHTML = `
                <img class="img-avatar img-avatar32" src="${response.avatar || 'assets/media/avatars/avatar7.jpg'}" alt="">
                <a class="fs-sm fw-semibold ms-2" href="javascript:void(0)">
                    ${contactName} - ${contactPhone}
                </a>
            `;
        }
        // Verifique se há mensagens
        if (response && response.data && response.data.length > 0) {
            let lastHeader = '';
            const today = new Date().toISOString().split('T')[0];  // Data atual no formato YYYY-MM-DD

            // Função para formatar a data para o formato "26 de outubro de 2022"
            const formatDate = (dateString) => {
                const date = new Date(dateString);
                const options = { day: '2-digit', month: 'long', year: 'numeric' };
                return new Intl.DateTimeFormat('pt-BR', options).format(date);
            };



            response.data.forEach((message) => {
                const isSelf = !message.sent_by_user;
                const position = isSelf ? 'self' : '';

                const messageDate = new Date(message.created_at).toISOString().split('T')[0];

                // Verifica se o cabeçalho deve ser atualizado com base na data da mensagem
                let headerText = '';
                if (messageDate === today) {
                    headerText = 'Hoje';
                } else if (messageDate === new Date(new Date().setDate(new Date().getDate() - 1)).toISOString().split('T')[0]) {
                    headerText = 'Ontem';
                } else {
                    headerText = formatDate(message.created_at);  // Formata a data para "26 de outubro de 2022"
                }

                if (lastHeader !== headerText) {
                    Chat.addHeader(conversationOne.id, headerText, position);  // Adiciona cabeçalho
                    lastHeader = headerText;
                }

                // Verifica o tipo de mensagem e adiciona ao chat
                switch (message.type) {
                    case 'image':
                        Chat.addImage(conversationOne.id, 'images/200x200.png', position);
                        Chat.addMessage(conversationOne.id, `Imagem: ${message.content}`, position); // Adiciona imagem
                        break;
                    default:
                        Chat.addMessage(conversationOne.id, message.content, position); // Adiciona mensagem de texto
                        break;
                }
            });
        } else {
            console.log('Nenhuma mensagem encontrada');
            Chat.addHeader(conversationOne.id, 'Sem mensagens para exibir.');
        }
    });
};

const handleImageUpload = (chatId) => {

    $('#imageUpload').off('change').on('change', function (event) {
        const file = event.target.files[0];

        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                const imageUrl = e.target.result; // URL da imagem carregada
                const position = "self"; // Definir posição como 'self' para o envio do usuário
                console.log(chatId); // Exibe o chatId para depuração
                // Adiciona a imagem no chat
                Chat.addImage(chatId, imageUrl, position);
            };
            reader.readAsDataURL(file); // Lê o arquivo da imagem
        }
    });
}

const enviarMensagemWhatsApp = () => {
    console.log("aqui");

    // Verifica se a conversa tem um valor válido para 'from'
    if (!conversationOne.from || !conversationOne.contact_name) {
        alertify.error('Faltando informações da conversa (from ou contact_name)');
        return; // Interrompe a execução se faltar algum dado importante
    }

    // Coleta os valores necessários
    let from = conversationOne.from;
    let contact_name = conversationOne.contact_name;
    let content = $('#chat-mensagem').val();

    // Verifica se o conteúdo da mensagem está vazio
    if (!content) {
        alertify.error('A mensagem não pode estar vazia');
        return; // Interrompe a execução se a mensagem estiver vazia
    }

    // Chama a função ajaxSimpleRequest para enviar os dados
    ajaxSimpleRequest('/chat/send', { from, contact_name, content }, function (response) {
        console.log('Mensagem enviada com sucesso');
    });
}

// Exemplo de chamada da função, passando o chatId como parâmetro
$('#uploadImageButton').on('click', function () {
    $('#imageUpload').click();
});




// Evento que executa a função assim que o DOM estiver carregado
$(document).ready(function () {
    loadConversations();
});
