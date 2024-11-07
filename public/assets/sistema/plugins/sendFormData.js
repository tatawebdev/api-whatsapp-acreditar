// Função para enviar dados do formulário via AJAX
// Parâmetros:
// - form: O formulário a ser enviado.
// - successCallback: Função a ser chamada em caso de sucesso.
// - errorCallback: Função a ser chamada em caso de erro.
function sendFormData(form, successCallback, errorCallback) {
    const $form = $(form);

    const formdata = new FormData(form);

    // Valida se todos os campos do formulário estão preenchidos
    let validationMessage = areAllFieldsValid($form);

    if (!!validationMessage) {
        alertify.error(validationMessage);
        return;
    }

    // Obtém a URL de destino do formulário (se não estiver definida, usa a URL atual da página)
    let url = $form.attr('action');
    if (!url) {
        url = window.location.href;
    }

    // Envia os dados do formulário via AJAX
    $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        data: formdata,
        cache: false,
        contentType: false,
        processData: false,
        success: function (response) {
            console.log(response);
            if (response.status == 'success') {
                if (typeof successCallback === 'function') {
                    successCallback(response);
                }
            } else {
                if (typeof errorCallback === 'function') {
                    message = response.message || 'Ocorreu um erro.';
                    alertify.error(message);

                    // Chama a função de retorno de erro e passa os detalhes do erro
                    errorCallback({ status: 'error', message: message, data: null });

                }
            }
        },
        error: function ($error) {
            if (typeof errorCallback === 'function') {
                console.log($error);
                message = 'Ocorreu um erro ao comunicar com o servidor';
                alertify.error(message);

                // Chama a função de retorno de erro em caso de erro de comunicação
                errorCallback({ status: 'error', message: message, data: null });
            }
        }
    });
}
