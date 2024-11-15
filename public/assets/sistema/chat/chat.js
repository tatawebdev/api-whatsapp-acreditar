let conversationOne = {};
const loadConversations = () => {
    const url = "/chat/conversations";
    const data = {};

    ajaxSimpleRequest(url, data, (response) => {

        response.forEach(conversation => {

            // Supondo que o status venha da resposta
            let status = conversation.status || 'online';  // Se não tiver status, assume 'online'
            let avatar = conversation.avatar || 'assets/media/avatars/avatar7.jpg';  // Se não tiver avatar, usa o padrão

            const conversationEnd = new Date(conversation.session_end);
            const currentTime = new Date();

            let statusClass = conversationEnd > currentTime ? 'bg-success' : 'bg-danger';


            let conversationItem = `
                <li data-id="${conversation.id}" data-contact_name="${conversation.contact_name}" data-from="${conversation.from}" data-session_end="${conversation.session_end}" >
                    <a class="d-flex py-2" href="javascript:void(0)">
                        <div class="flex-shrink-0 mx-3 overlay-container">
                            <img class="img-avatar img-avatar48" src="${avatar}" alt="">
                            <span data-session_end="${conversation.session_end}" class="overlay-item item item-tiny item-circle border border-2 border-white ${statusClass}"></span>
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

            const session_end = $(this).data('session_end');

            if (session_end == "null" || session_end == null) {
                alertify.error(`Erro: Não foi possível obter o horário de término desta sessão. {session_end: ${session_end}}`);
                return;
            }




            conversationOne = {
                id: $(this).data('id'),
                contact_name: $(this).data('contact_name'),
                from: $(this).data('from'),
                session_end,
            };

            loadConversationDetails();
        });
    });
};

function inicializarChat(conversation) {
    console.log(conversationOne)
    const chatMessages = document.querySelector('.js-chat-messages');
    const chatSendMessages = document.querySelector('input.js-chat-input');
    const conversationHeader = document.querySelector('.block-title');

    if (chatMessages && chatSendMessages && conversationHeader) {
        // Define os atributos de ID da conversa nos elementos de chat
        chatMessages.setAttribute('data-chat-id', conversation.id);
        chatSendMessages.setAttribute('data-target-chat-id', conversation.id);

        // Limpa as mensagens e reseta o scroll
        chatMessages.innerHTML = "";
        chatMessages.scrollTop = 0;

        // Chama a função de upload de imagem com o ID da conversa
        handleImageUpload(conversation.id);

        // Define o cabeçalho da conversa com nome e telefone do contato
        const contactName = conversation.contact_name || 'Nome do Contato'; // Valor padrão
        const contactPhone = conversation.from || 'Telefone não disponível'; // Valor padrão

        const conversationEnd = new Date(conversation.session_end);
        const currentTime = new Date();


        const formattedEndDate = formatCustomDate(conversationEnd); // Aqui usamos a função personalizada para a data
        const formattedEndTime = conversationEnd.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

        // Exibir a mensagem


        conversationHeader.innerHTML = `
            <img class="img-avatar img-avatar32" src="${conversation.avatar || 'assets/media/avatars/avatar7.jpg'}" alt="">
            <a class="fs-sm fw-semibold ms-2" href="javascript:void(0)">
                ${contactName} - ${contactPhone}
            </a>
            <p id="session_current">
                Sessão até: ${formattedEndDate} às ${formattedEndTime}.
            <p>
            `;


        if (conversationEnd <= currentTime) {
            document.getElementById('chat-form-container').style.display = 'none';
        } else {
            document.getElementById('chat-form-container').style.display = 'flex';
        }


    } else {
        console.error("Elemento de mensagens, input de chat ou cabeçalho não encontrado.");
    }
}
function formatCustomDate(date) {
    const today = new Date();
    const yesterday = new Date(today);
    yesterday.setDate(today.getDate() - 1);
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);

    // Verifica se a data é hoje, ontem ou amanhã
    if (date.toDateString() === today.toDateString()) {
        return 'Hoje';
    } else if (date.toDateString() === yesterday.toDateString()) {
        return 'Ontem';
    } else if (date.toDateString() === tomorrow.toDateString()) {
        return 'Amanhã';
    } else {
        return date.toLocaleDateString('pt-BR');
    }
}
const loadConversationDetails = () => {
    const detailsUrl = `/chat/conversations/${conversationOne.id}`;
    const requestData = { id: conversationOne.id };

    // Chamada AJAX
    ajaxSimpleRequest(detailsUrl, requestData, (response) => {
        console.log('Detalhes da conversa:', JSON.stringify(response));

        inicializarChat(conversationOne);

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
                        Chat.addImage(
                            {
                                chatId: conversationOne.id,
                                imageUrl: 'images/200x200.png',
                                position,
                                messageData: message
                            }
                        );

                        break;
                    default:

                        console.log(message)

                        Chat.addMessage({
                            chatId: conversationOne.id,
                            messageText: message.content,
                            position,
                            messageData: message
                        });

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

            let from = conversationOne.from;
            let contact_name = conversationOne.contact_name;
        
            ajaxFile('/chat/send/image', file, { chatId, from, contact_name }, function (response) {
                // Suponha que a resposta seja um objeto com a URL da imagem
                console.log(response);

                if (response && response.imageUrl) {
                    const imageUrl = response.imageUrl;
                    const position = "self"; // Posição como 'self' para o envio do usuário

                    // Exibe o chatId para depuração
                    console.log(chatId);

                    // Adiciona a imagem ao chat
                    Chat.addImage({
                        chatId,
                        imageUrl,
                        position,
                    });
                }
            });
        }
    });
}

const enviarMensagemWhatsApp = () => {

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
console.log(response)
        messageData = response

        Chat.addMessage({
            chatId: conversationOne.id,
            messageText: content,
            position: 'self',
            messageData
        });

    });
}

// Exemplo de chamada da função, passando o chatId como parâmetro
$('#uploadImageButton').on('click', function () {
    $('#imageUpload').click();
});




// Evento que executa a função assim que o DOM estiver carregado
$(document).ready(function () {
    loadConversations();
    // function getNewMessage() {
    //     const request = indexedDB.open('chatDatabase', 1);

    //     request.onsuccess = function (event) {
    //         const db = event.target.result;
    //         const transaction = db.transaction('messages', 'readonly');
    //         const store = transaction.objectStore('messages');

    //         const getRequest = store.get('newMessage');

    //         getRequest.onsuccess = function () {
    //             const message = getRequest.result ? getRequest.result.value : null;
    //             if (message) {
    //                 alert('Mensagem recuperada: ' + message);
    //             }
    //         };
    //     };
    // }

    // // Chamar a função para verificar por novas mensagens assim que o app abrir
    // window.onload = function () {
    //     getNewMessage();
    // };

});


function chatMessege(payload) {
    if (payload?.data?.session_end)
        checkSessionData(payload.data.session_end)


    if (payload?.data?.type == 'status') {
        chatStatusMessege(payload)
    } else {
        chatNewMessege(payload)
    }
}
function chatStatusMessege(payload) {
    const { message_id, status } = payload?.data;

    const validStatusOrder = ['sent', 'delivered', 'read'];

    // Pegando o status atual exibido na página
    const currentStatus = document.getElementById('status_' + message_id)?.innerHTML;

    // Verificando a sequência do novo status com o status atual
    const currentIndex = currentStatus ? validStatusOrder.indexOf(currentStatus) : -1;
    const newIndex = validStatusOrder.indexOf(status);


    // Se o novo status for inválido em relação ao status atual (não segue a sequência), não atualize
    if (newIndex !== -1 && (currentIndex === -1 || newIndex > currentIndex) || status == 'failed') {
        document.getElementById('status_' + message_id).innerHTML = status;
    } else {
        console.log(currentStatus, status);
        console.log('Tentativa de transição inválida de status. Status não alterado.');
    }
}
function chatNewMessege(payload) {
    const isSelf = !payload.data?.sent_by_user;
    const position = isSelf ? 'self' : '';




    switch (payload.data?.type) {
        case 'image':
            if (payload.data?.type_status == 'new') {
                console.log(payload, 'image_log_new')
                Chat.addImage(
                    {
                        chatId: conversationOne.id,
                        imageUrl: 'images/200x200.png',
                        position,
                        messageData: payload.data
                    }
                );
            } else if (payload.data?.type_status == 'src') {
                console.log(
                    document.getElementById('img_' + payload.data?.message_id))
                document.getElementById('img_' + payload.data?.message_id).src = payload.data?.file_src;


            }

            break;
        default:

            Chat.addMessage({
                chatId: payload.data.chat_conversation_id,
                messageText: payload.data.content,
                position
            });


            break;
    }


}

function checkSessionData(session_end) {
    const conversationEnd = new Date(session_end);
    const currentTime = new Date();

    const formattedEndDate = formatCustomDate(conversationEnd);
    const formattedEndTime = conversationEnd.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });

    if (conversationEnd <= currentTime) {
        // Ajusta a mensagem se o horário já passou
        document.getElementById("session_current").innerHTML = `Sessão já encerrada em ${formattedEndDate} às ${formattedEndTime}.`;
    } else {
        // Caso a sessão ainda esteja ativa
        document.getElementById("session_current").innerHTML = `Sessão até: ${formattedEndDate} às ${formattedEndTime}.`;
    }
}
function checkLocalStorage() {
    const message = localStorage.getItem('newMessage');
    if (message) {
        console.log("Nova mensagem:", message);

        // Opcional: remova o item após o console para evitar múltiplas execuções
        localStorage.removeItem('newMessage');
    }

}
conversationOne = {
    id: "12345",
    contact_name: "João da Silva",
    from: "(11) 98765-4321",
    avatar: "https://example.com/avatar.jpg"
};


function updateSessionStatus() {
    const currentTime = new Date();

    $('span[data-session_end]').each(function () {
        const sessionEnd = new Date($(this).data('session_end'));
        console.log(sessionEnd)
        const statusClass = sessionEnd > currentTime ? 'bg-success' : 'bg-danger';
        $(this).removeClass('bg-success bg-danger').addClass(statusClass);
    });
}
setInterval(updateSessionStatus, 1000 * 60);
