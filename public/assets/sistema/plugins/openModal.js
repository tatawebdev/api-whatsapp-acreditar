
async function openModal(settings, $button = null) {
    if (!$button) {
        $button = $("<button>");
        $button.attr("data-view-params", null);
        $button.attr("view", null);
        $button.attr("title", null);
    }

    let view = settings.view ?? $button.data('view');
    let id = settings.id ?? ($button.data('id')) ?? null;


    var view_params = (settings.view_params ?? $button.data('view-params'))
    view_params = view_params ? await getParametros(view_params, id) : [];
    view_params = Object.assign({}, view_params, settings.params);

    console.log(view_params)
    if (!!view_params.close) {
        alertify.error(view_params.close)
        return false;
    }

    if (view) {
        let ajaxUrl = url_app + "home/modal_ajax";

        try {
            let data = await $.ajax({
                url: ajaxUrl,
                data: { view: view, view_params: view_params },
                method: 'POST',
                dataType: 'html',
                cache: false,
            });

            $('.modal-title').html(settings.title ?? $($button).data('title') ?? "Modal Sem título");
            $('#form-modal-unico').attr('action', settings.form_action ?? $($button).data('form-action') ?? null);

            let modalFooter = $($button).data('modal-footer') ?? true;

            if (modalFooter == "off") {
                $('.modal-footer').css('display', 'none');
            } else if (settings.button_save_off ?? $($button).data('button-save-off') ?? false) {
                $('.modal-save').css('display', 'none');
                $('.modal-cancel').html(settings.cancel ?? $($button).data('cancel') ?? "Fechar");
            }
            else {
                $('.modal-footer').css('display', 'flex');
                $('.modal-save').css('display', 'inline-block');
                console.log(settings.save)
                $('.modal-save').html(settings.save ?? $($button).data('save') ?? "Salvar");
                $('.modal-cancel').html(settings.cancel ?? $($button).data('cancel') ?? "Cancelar");

            }
            if ((settings.max_width_100 ?? $button.data('max-width-100') ?? false)) {
                $('#modal-unico .modal-dialog').addClass('max-width-100');
            } else if ($('#modal-unico .modal-dialog').hasClass('max-width-100')) {
                $('#modal-unico .modal-dialog').removeClass('max-width-100');
            }
            if ((!(settings.no_modal_save ?? $button.data('no-modal-save') ?? false))) {
                $('#modal-unico .modal-save').addClass('standard');
            } else if ($('#modal-unico .modal-save').hasClass('standard')) {
                $('#modal-unico .modal-save').removeClass('standard');
            }
            modalsavestandard();
            $('.seletor-do-elemento').css('max-width', '100% !important');

            $('#modal-unico-ajax-content').html(data);
            $('#id-modal-unico').val(id);

            if (!($button.data('view-params'))) {
                $('#modal-unico').on('shown.bs.modal', function () {
                    $('#modal-unico').bind('modalOpenClear', modalOpenClear);
                });
            }
            $('#modal-unico').modal('show');
        } catch (error) {
            console.log(error)
            $('#modal-unico-ajax-content').html('Ocorreu um erro ao carregar os parametros via AJAX.');
            $('#modal-unico').modal('show');
        }
    } else {
        $('#modal-unico-ajax-content').html('Parâmetro "view" não está presente.');
        $('#modal-unico').modal('show');
    }

    $(".modal-save").click(function () {
        const hasClass = $(this).hasClass("standard");
        if (!hasClass) {
            if (typeof settings.success === "function") {

                const form = $("#form-modal-unico")[0];
                const formData = new FormData(form);

                const formObject = {};
                for (let [key, value] of formData.entries()) {
                    formObject[key] = value;
                }

                settings.success(formObject);
                if (!settings.nocloseModal) $("#modal-unico").modal("hide");

            }
        }
    });

}


(function ($) {

    $.fn.openModal = function (options) {
        const defaults = {
            view: null,
            id: null,
            params: {},
            view_params: null,
            title: null,
            form_action: null,
            success: () => {

            }
        };
        const settings = $.extend({}, defaults, options);

        return this.each(async function () {
            const $button = $(this);
            $button.on('click', async function () {
                openModal(settings, $button);
            });
        });
    };
})(jQuery);

async function getParametros(url, id) {
    try {
        return await $.ajax({
            url: url_app + url,
            data: { id: id },
            method: 'POST',
            dataType: 'json'
        });
    } catch (error) {
        throw error;
    }
}
function modalOpenClear() {
    $('#form-modal-unico :input').not(':button, :submit, :reset, :hidden, :checkbox, :radio').val('');
    $('#form-modal-unico :input[type=checkbox], #form-modal-unico :input[type=radio]').prop('checked', false);
    $('#form-modal-unico select').prop('selectedIndex', -1);
    var primeiroCampo = $("#form-modal-unico :input:first");
    primeiroCampo.focus();

}
$(document).ready(function () {
    $('.open-modal').openModal();

    $('#modal-unico').on('modalOpen', function () {
        var primeiroCampo = $("#form-modal-unico :input:first");
        primeiroCampo.focus();
    });


});