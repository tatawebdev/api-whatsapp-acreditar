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
