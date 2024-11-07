(function ($) {
    const ENTER_KEY = 13;

    $.fn.autocompleteSelect = function (options) {
        const settings = $.extend({
            url: null,
            onSelect: function (result) { },
            onNoResults: function () { },
            text: 'nome',
            selected: null,
            clear: false,
            value: 'nome'
        }, options);

        return this.each(function () {
            const $this = $(this);

            $this.on("change", function () {
                const selectedOption = $this.find('option:selected');
                const selectedObject = selectedOption.data('result');
                settings.onSelect(selectedObject);
            });

            $this.on("keydown", function (event) {
                if (event.keyCode === ENTER_KEY) {
                    event.preventDefault();
                    const selectedOption = $this.find('option:selected');
                    const selectedObject = selectedOption.data('result');
                    settings.onSelect(selectedObject);
                }
            });

            const url = settings.url || $this.data('url') || window.location.href;
            let text = $this.data('text') ?? settings.text;;
            let value = $this.data('value') ?? settings.value;
            let selected = $this.data('selected') ?? settings.selected;
             if (settings.clear) {
                $this.empty();
            }
            $.ajax({
                url: url,
                method: "POST",
                data: {},
                dataType: "json",
                success: function (response) {
                    console.log(response)
                    if (response.length > 0) {
                        $.each(response, function (index, item) {
                            const option = $("<option></option>")
                                .val(item[value])
                                .text(item[text])
                                .data('result', item);
                            $this.append(option);
                        });
                        $this.find('option').each(function () {

                            if ($(this).val() == selected) {
                                $(this).prop('selected', true);
                            }
                        });
                    } else {
                        $this.append("<option value=''>Nenhum resultado encontrado.</option>");
                        settings.onNoResults();
                    }
                },
                error: function (response) {
                    console.error("Erro na requisição AJAX. " + url, response);
                    message = 'Ocorreu um erro ao comunicar com o servidor';
                    alertify.error(message);
                }
            });
        });
    };
})(jQuery);
