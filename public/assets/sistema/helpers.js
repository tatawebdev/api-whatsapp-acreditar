function ajaxSimpleRequest(
	url,
	data = {},
	successCallback = (response) => { return response; }
) {
	const baseURL = document.querySelector('meta[name="base-url"]').getAttribute('content');
	const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

	const fullUrl = baseURL + url;
	$.ajax({
		url: fullUrl,
		type: "POST",
		dataType: "json",
		data: data,
		cache: false,
		headers: {
			'X-CSRF-TOKEN': csrfToken
		},
		success: function (response) {
			if (typeof successCallback === "function") {
				successCallback(response);
			}
		},
		error: function (error) {
			console.log(error);
			let message = error.responseJSON?.error || error.responseJSON?.message || "Ocorreu um erro ao comunicar com o servidor";

			alertify.error(message);
		},
	});
}
function ajaxFile(url, file, extraData = {}, successCallback = (response) => { return response; }) {
    const baseURL = document.querySelector('meta[name="base-url"]').getAttribute('content');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    
    // Criando o FormData para enviar o arquivo e dados adicionais
    const formData = new FormData();
    formData.append('file', file);  // Adicionando o arquivo ao FormData
    
    // Adicionando dados adicionais se existirem
    for (const key in extraData) {
        if (extraData.hasOwnProperty(key)) {
            formData.append(key, extraData[key]);
        }
    }

    // URL completa
    const fullUrl = baseURL + url;

    $.ajax({
        url: fullUrl,
        type: "POST",
        dataType: "json",
        data: formData,
        processData: false,  
        contentType: false,  
        cache: false,
        headers: {
            'X-CSRF-TOKEN': csrfToken
        },
        success: function (response) {
            if (typeof successCallback === "function") {
                successCallback(response);
            }
        },
        error: function (error) {
            console.log(error);
            let message = error.responseJSON?.error || error.responseJSON?.message || "Ocorreu um erro ao comunicar com o servidor";
            alertify.error(message);
        },
    });
}
