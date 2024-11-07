
(function ($) {
    const UP_ARROW = 38;
    const DOWN_ARROW = 40;
    const ENTER_KEY = 13;

    $.fn.autocomplete = function (options) {
        const settings = $.extend({
            url: null,
            onSelect: function (result) { },
            onNoResults: function () { },
            resultField: 'nome'
        }, options);

        return this.each(function () {
            const $this = $(this);
            const resultsContainer = $("<div class='ajax-autocomplete-results'></div>");
            let selectedResultIndex = -1;
            let timer;

            function onAutoCompleteSelect(result) {
                $this.val(result[settings.resultField]);
                resultsContainer.empty().hide();
                settings.onSelect(result);
            }

            $this.after(resultsContainer);

            $this.on("input", function () {
                clearTimeout(timer);
                const searchTerm = $this.val();

                let _url = "";
                if ($this.data('url')) {
                    _url = url_app + $this.data('url');
                }

                const url = settings.url || _url || window.location.href;
                console.log(url)
                if (searchTerm.length >= 0) {
                    timer = setTimeout(function () {
                        $.ajax({
                            url: url,
                            method: "POST",
                            data: { searchTerm: searchTerm },
                            dataType: "json",
                            success: function (response) {
                                resultsContainer.empty();

                                if (response.length > 0) {
                                    $.each(response, function (index, item) {
                                        const resultItem = $("<div class='ajax-autocomplete-result'>" + item[settings.resultField] + "</div>");
                                        resultItem.data('result', item);
                                        resultItem.on("click", function () {
                                            onAutoCompleteSelect(item);
                                        });
                                        resultsContainer.append(resultItem);
                                    });
                                } else {
                                    resultsContainer.append("<div class='ajax-auto-complete no-results'>Nenhum resultado encontrado.</div>");
                                    settings.onNoResults();
                                }

                                resultsContainer.show();
                            },
                            error: function (response) {
                                console.error("Erro na requisição AJAX. " + url, response);
                                message = 'Ocorreu um erro ao comunicar com o servidor';
                                alertify.error(message);
                            }
                        });
                    }, 500);
                } else {
                    resultsContainer.empty().hide();
                }
            });

            $this.on("keydown", function (event) {
                switch (event.keyCode) {
                    case UP_ARROW:
                        event.preventDefault();
                        if (selectedResultIndex > 0) {
                            selectedResultIndex--;
                            updateSelectedResult();
                        }
                        break;
                    case DOWN_ARROW:
                        event.preventDefault();
                        if (selectedResultIndex < resultsContainer.children().length - 1) {
                            selectedResultIndex++;
                            updateSelectedResult();
                        }
                        break;
                    case ENTER_KEY:
                        event.preventDefault();
                        const selectedResult = resultsContainer.children().eq(selectedResultIndex);
                        if (selectedResult.length) {
                            const selectedObject = selectedResult.data('result');
                            onAutoCompleteSelect(selectedObject);
                        }
                        break;
                }
            });

            function updateSelectedResult() {
                resultsContainer.children().removeClass("ajax-auto-complete selected");
                const selectedResult = resultsContainer.children().eq(selectedResultIndex);
                if (selectedResult.length) {
                    selectedResult.addClass("ajax-auto-complete selected");
                }
            }
        });
    };
})(jQuery);
