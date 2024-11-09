import { initializeApp } from "https://www.gstatic.com/firebasejs/9.0.0/firebase-app.js";
import { getMessaging, getToken, onMessage } from "https://www.gstatic.com/firebasejs/9.0.0/firebase-messaging.js";


// Initialize Firebase in the service worker
const firebaseConfig = {
    apiKey: "AIzaSyBlSfmSaPpJAsDjPeW-UOS35KgIHN2Id4s",
    authDomain: "chat-bot-acreditar.firebaseapp.com",
    projectId: "chat-bot-acreditar",
    storageBucket: "chat-bot-acreditar.firebasestorage.app",
    messagingSenderId: "1086360314150",
    appId: "1:1086360314150:web:10af74f63b6268486ce1e7",
    measurementId: "G-JJSQ3E9G7L"
}

// Inicialização do Firebase com a configuração
const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);


// Função para solicitar permissão de notificação
function requestNotificationPermission() {
    Notification.requestPermission().then(permission => {
        if (permission === 'granted') {

            if ('serviceWorker' in navigator) {
                navigator.serviceWorker.register('/firebase-messaging-sw.js')
                    .then((registration) => {
                        console.log('Service Worker registrado com sucesso:', registration);
                    })
                    .catch((error) => {
                        console.log('Falha ao registrar o Service Worker:', error);
                    });
            }
            // Obter o token do FCM
            getToken(messaging, { vapidKey: 'BK8YUUpSo8bUbMwPgfjAVYXQU0bMas_Gn-VZTphfjlgEq0y0s75Ua8z6AlXfXzsfMTP31rcbZ67bUaOz3lqnwgA' })
                .then(token => {
                    if (token) {
                        // Obtém o token armazenado no localStorage
                        const storedToken = localStorage.getItem('fcm_token');

                        // Verifica se o token obtido é diferente do token armazenado
                        if (token !== storedToken) {
                            const data = {
                                // phone_number: phoneNumber || null,  
                                fcm_token: token
                            };

                            // Chama a função ajaxSimpleRequest para enviar o token ao servidor
                            ajaxSimpleRequest('/phone/token', data, response => {
                                console.log('Resposta do servidor:', response);
                                localStorage.setItem('fcm_token', token);
                            });
                        }
                    } else {
                        console.log('Nenhum token disponível. Solicite permissão para gerar um.');
                    }
                })
                .catch(error => {
                    console.log('Erro ao obter o token FCM:', error);
                });
        } else {
            console.log('Permissão de notificação negada.');
        }
    });
}
// Solicitar permissão de notificação
requestNotificationPermission();

// Listener para mensagens recebidas em primeiro plano
onMessage(messaging, payload => {
    // console.log('Mensagem recebida:', payload);
    chatMessege(payload)
    // const message = payload.data.message;
    // const username = payload.data.username;
    // displayMessage(username, message);
});
