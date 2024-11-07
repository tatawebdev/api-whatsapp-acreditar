(function ($) {
    $.fn.includeView = function (url, params, successCallback = () => { }, errorCallback = () => { }) {
        return this.each(function () {
            const $element = $(this);

            const requestData = {
                view: url,
                view_params: params
            };

            $.ajax({
                url: url_app + "home/includeView",
                type: "POST",
                dataType: "html",
                data: requestData,
                cache: false,
                success: (response) => {
                    if (typeof successCallback === "function") {
                        successCallback(response);
                    }
                    $element.html(response); 
                },
                error: ($error) => {
                    if (typeof errorCallback === "function") {
                        console.log($error);
                        mensagem = "Ocorreu um erro ao comunicar com o servidor";
                        alertify.error(mensagem);
                        errorCallback({ status: "error", message: mensagem, data: null });
                    }
                },
            });
        });
    };
})(jQuery);
