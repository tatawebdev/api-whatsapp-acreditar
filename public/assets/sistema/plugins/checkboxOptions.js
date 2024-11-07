(function ($) {
    const ENTER_KEY = 13;
    $.fn.checkboxOptionsPlugin = function (options) {
        const settings = $.extend({
            container: 'table',
            AjaxResponseSuccess: null,
            onClick: null,
            options: []
        }, options);


        return this.each(function () {
            var $container = $(settings.container);

            var $buttonGroup = $('.dt-buttons.options');

            if ($buttonGroup.length === 0) {
                $divGroup = $('<div class="div-group"></div>');
                $buttonGroup = $('<div class="dt-buttons btn-group options"></div>');

                if (settings.options.length > 0) {
                    var $span = $('<span class="btn btn-primary count"></button>')
                        .text('1')
                        .attr('data-form-action', '1');
                    $buttonGroup.append($span);


                    settings.options.forEach(function (option) {
                        var $button = $('<button class="btn btn-primary buttons-copy buttons-html5"></button>')
                            .text(option.label)
                            .attr('data-form-action', option.url);
                        if (!!(option.confimacao ?? null))
                            $button.attr('confimacao', true);


                        $buttonGroup.append($button);


                        $button.on('click', function () {
                            if (!!option.confimacao) {
                                const { title = "Tem certeza?", text = "Tem certeza?", confirmButtonText = "Sim" } = option.confimacao;
                                mostrarConfirmacao({
                                    sucessocalback: () => {
                                        click(option);
                                    },
                                    title: title ?? "Tem certeza?",
                                    text: text ?? "Tem certeza?",
                                    confirmButtonText: confirmButtonText ?? "Sim",
                                })
                            } else {
                                click(option);
                            }


                            function click(option) {
                                var collectedParams = [];
                                var checkedCheckboxes = $('.checkbox-options:checked');

                                checkedCheckboxes.each(function () {
                                    if ($(this).data()) {
                                        var dataObject = $(this).data() ?? {};
                                        dataObject.value = $(this).val() ?? '';

                                        if (!!$(this).attr('id'))
                                            dataObject.id = $(this).attr('id');

                                        collectedParams.push(dataObject);
                                    }
                                });


                                if (!!option.onClick) {
                                    option.onClick({ params: null, collected: collectedParams });
                                } else {
                                    var formDataAction = $(this).attr('data-form-action');
                                    if (formDataAction !== '#') {
                                        ajaxSimpleRequest(formDataAction, {}, function (response) {
                                            if (typeof option.AjaxResponseSuccess === 'function') {
                                                option.AjaxResponseSuccess(response);
                                            }
                                        });
                                    } else {
                                        if (typeof option.AjaxResponseSuccess === 'function') {
                                            option.AjaxResponseSuccess({ params: null, collected: collectedParams });
                                        }
                                    }
                                }
                            }

                        });


                    });
                    $divGroup.append($buttonGroup);
                    $divGroup.insertBefore($container);
                }

            }
            $(this).on('change', function () {
                $('span.count').html($('.checkbox-options:checked').length);

                var isChecked = $('.checkbox-options:checked').length > 0;
                if (isChecked) {
                    $('div.div-group').addClass('show');
                    $('div.dt-buttons.options').addClass('show');
                } else {
                    $('div.div-group').removeClass('show');
                    $('div.dt-buttons.options').removeClass('show');
                }
            });
        });
    };



})(jQuery);
