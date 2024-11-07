(function ($) {
    $.fn.openModalWithContent = function () {
        let originalTitle = ""
        let iframeURL = ""
        let contato = 0

        function addPhoneNumber(phoneNumber) {
            const phoneNumberDisplay = $('.phone-number-display');
            phoneNumberDisplay.append('<p>' + phoneNumber + '</p>');
        }

        // Event handler for adding phone numbers
        $('#add-phone-number').on('click', function () {
            const phoneNumberInput = $('#phone-number');
            const phoneNumber = phoneNumberInput.val().trim();

            if (phoneNumber !== '') {
                addPhoneNumber(phoneNumber);
                phoneNumberInput.val(''); // Clear the input field
            }
        });
        function criarCampoWhatsApp() {
            let container = $('.modal-body-whatsapp');
            let newRow = $('<div class="form-row" style="margin-right:0px"></div>');

            let inputDiv = $('<div class="col-12 col-md"></div>');
            let inputWhatsApp = $(`<input type="tel" required name="whatsapp[${contato}][telefone]" class="form-control format whatsapp" placeholder="Digite o WhatsApp">`);
            inputDiv.append($('<label>WhatsApp:</label>'));
            inputDiv.append(inputWhatsApp);

            let inputDivUser = $('<div class="col-12 col-md"></div>');
            let inputUser = $(`<input type="text" required name="whatsapp[${contato}][cliente]" class="form-control format user" placeholder="Digite o Cliente Ex.: Sra. Helen">`);
            inputDivUser.append($('<label>Cliente:</label>'));
            inputDivUser.append(inputUser);

            // Cria o botão de remoção
            let removeDiv = $('<div class="col-12 col-md-2"></div>');
            let removeButton = $('<button type="button" class="btn btn-danger remove-whatsapp"><i class="fas fa-trash"></i></button>');
            removeDiv.append($('<label>Ação:</label><br>'));
            removeDiv.append(removeButton);

            newRow.append(inputDiv);
            newRow.append(inputDivUser); // Adicione o inputDivUser
            newRow.append(removeDiv);
            contato++;

            container.append(newRow);
            format();

            // Adicionar evento de remoção
            removeButton.click(function () {
                contato--;
                newRow.remove();
            });
        }

        $('#adicionarWhatsApp').click(criarCampoWhatsApp);

        function criarCampoEmail() {
            let container = $('.modal-body-mail');
            let newRow = $('<div class="form-row" style="margin-right:0px"></div>');

            let inputDiv = $('<div class="col-12 col-md"></div>');
            let inputEmail = $('<input type="email" required name="email[][email]" class="form-control format email" placeholder="Digite o Email">');
            inputDiv.append($('<label>Email:</label>'));
            inputDiv.append(inputEmail);

            let inputDivUser = $('<div class="col-12 col-md"></div>');
            let inputUser = $('<input type="text" required name="email[][cliente]" class="form-control format user" placeholder="Digite o Cliente Ex.: Sra. Helen">');
            inputDivUser.append($('<label>Cliente:</label>'));
            inputDivUser.append(inputUser);

            // Cria o botão de remoção
            let removeDiv = $('<div class="col-12 col-md-2"></div>');
            let removeButton = $('<button type="button" class="btn btn-danger remove-email"><i class="fas fa-trash"></i></button>');
            removeDiv.append($('<label>Ação:</label><br>'));
            removeDiv.append(removeButton);

            newRow.append(inputDiv);
            newRow.append(inputDivUser);
            newRow.append(removeDiv);

            container.append(newRow);
            format();
            contato++;

            // Adicionar evento de remoção
            removeButton.click(function () {
                contato--;
                newRow.remove();
            });
        }

        $(document).ready(function () {
            $('#adicionarIframeEmail').click(criarCampoEmail);
        });


        $('#submit-modal-iframe').on('click', function () {
            var dados = {};
            var form = $("#form-modal-iframe");
            let mensagemValidacao = areAllFieldsValid(form);


            if (contato == 0 && !mensagemValidacao) {
                mensagemValidacao = "Nenhum contato foi acionado. Por favor, selecione pelo menos um contato antes de continuar.";
            }


            if (mensagemValidacao) {
                alertify.error(mensagemValidacao);
                return;
            }


            form.find('input, textarea').each(function () {
                var nomeCampo = $(this).attr('name');
                var valorCampo = $(this).val();
                dados[nomeCampo] = valorCampo;
            });
            dados['url'] = iframeURL;

            ajaxSimpleRequest("send/iframe", dados, (response) => {
                console.log(response)
            });
        });


        this.each(function () {
            const $button = $(this);
            const modal = $('#modal-iframe');
            const modalBody = modal.find('.modal-body');
            let currentContent = 'iframe';
            function showContent(contentType, titulo = null) {
                $('#modal-iframe .modal-title').html(originalTitle + (titulo ? ` ${titulo}` : ""));
                modalBody.find('.modal-body-iframe, .modal-body-whatsapp, .modal-body-mail, .modal-body-contatos').hide();
                modalBody.find(`.modal-body-${contentType}`).show();
                currentContent = contentType;
            }
            showContent('iframe');
            modal.on('shown.bs.modal', function (e) {
                contato = 0;

                currentContent = 'iframe'
                showContent('iframe');
                $('.modal-body-whatsapp').find('textarea').val("")
                $('.modal-body-whatsapp').find('.form-row').remove()
                $('.modal-body-mail').find('.form-row').remove()
                $('.modal-body-mail').find('textarea').val("")
            });
            $button.on('click', function () {
                if (!($button.data('open-modal-view') ?? null)) {
                    startShow();
                } else {
                    openModal({
                        view: $button.data('open-modal-view'),
                        view_params: $button.data('open-modal-view-params'),
                        title: $button.data('title'),
                        save: $button.data('save'),
                        no_modal_save: true,
                        success: (params) => {
                            startShow(params);
                        }
                    })
                }
            });
            function startShow(params = null) {
                contato = 0;
                currentContent = 'iframe';
                showContent('iframe');

                const iframeURL = $button.data('iframe-url');

                const title = $button.data('title');

                if (iframeURL) {
                    $('.modal-title').html(title || "Modal Sem título");
                    originalTitle = $('.modal-title').html();

                    $('#modal-iframe .modal-body-iframe').show();
                    $('#modal-iframe .modal-body-whatsapp').hide();
                    $('#modal-iframe .modal-body-mail').hide();
                    $('#modal-iframe .modal-body-contatos').hide();

                    const queryParams = params ? new URLSearchParams(params).toString() : '';

                    let finalURL = iframeURL;
                    if (queryParams) {
                        finalURL += iframeURL.includes('?') ? '&' : '?';
                        finalURL += queryParams;
                    }

                    const iframeContainer = $('<iframe style="width:100%;height: 100%;"></iframe>').attr('src', finalURL);

                    $('#modal-iframe .modal-body-iframe').empty();
                    $('#modal-iframe .modal-body-iframe').append(iframeContainer);

                    $('#modal-iframe').modal('show');
                } else {
                    alertify.error('Atributo "data-iframe-url" não está presente.');
                }
            }

            $('.send-whatsapp').on('click', function () {
                if (currentContent !== 'whatsapp') {
                    showContent('whatsapp', 'Whats App');
                } else {
                    showContent('iframe');
                }
            });
            $('.send-link').on('click', function () {
                const iframeSrc = document.querySelector('#modal-iframe iframe').getAttribute('src');
                window.open(iframeSrc, '_blank');

            });

            $('.send-mail').on('click', function () {
                if (currentContent !== 'mail') {
                    showContent('mail', 'E-mail');
                } else {
                    showContent('iframe');
                }
            });
            $('.send-contatos').on('click', function () {
                if (currentContent !== 'contatos') {
                    showContent('contatos', 'Configuração de Envio');
                } else {
                    showContent('iframe');
                }
            });
        });
    };
})(jQuery);


function iframeShow(params) {
    if (params && params.url) {
        const dynamicButton = $('<a class="btndropdown-item waves-effect open-modal-iframe" data-title="' + params.title + '" data-iframe-url="' + params.url + '"><i class="fas fa-barcode"></i></a>');

        $('body').append(dynamicButton);
        $('.open-modal-iframe').openModalWithContent();
        dynamicButton.click();

        dynamicButton.remove();

    } else {
        alertify.error('Parâmetro "url" ausente para a função iframeShow.');
    }
}

$(document).ready(function () {
    $('.open-modal-iframe').openModalWithContent();
});
