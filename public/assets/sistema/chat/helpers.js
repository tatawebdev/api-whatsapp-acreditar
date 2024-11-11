
(function ($) {
	let originalAjax = $.ajax;

	$.ajax = function (options) {
		let beforeSendCallback = options.beforeSend;
		let completeCallback = options.complete;
		let myInterval;
		options.beforeSend = function (jqXHR, settings) {
			myInterval = setInterval(() => {
				$(".loader-progress").addClass("load-active");
			}, 500);

			if (beforeSendCallback) {
				beforeSendCallback(jqXHR, settings);
			}
		};

		options.complete = function (jqXHR, textStatus) {
			clearInterval(myInterval);
			$(".loader-progress").removeClass("load-active");
			if (completeCallback) {
				completeCallback(jqXHR, textStatus);
			}
		};

		return originalAjax(options);
	};
})(jQuery);

(function ($) {
	$.fn.cep = function (evento) {
		let $this = this;
		$this.on(evento, function () {
			$(this).mask("00000-000");
			$(this).attr("autocomplete", false);

			let cep = $(this).val().replace(/\D/g, "");
			if (cep !== "") {
				let validacep = /^[0-9]{8}$/;
				if (validacep.test(cep)) {
					$this.trigger("preparando");

					$.get("https://viacep.com.br/ws/" + cep + "/json/", function (dados) {
						if (!dados.erro) {
							$this.trigger("encontrado", [cep, dados]);
						} else {
							alertify.error("CEP não encontrado.");
							$this.trigger("cep-nao-encontrado", dados);
						}
					}).fail(function (error) {
						alertify.error("CEP não encontrado.");
						$this.trigger("cep-nao-encontrado", error);
					});
				}
			}
		});

		return $this;
	};
})(jQuery);

// Função para lidar com o sucesso do envio do formulário
function handleFormSuccess(params, classReturn, divReturn, formThis) {
	const mostrarMensagem = (mensagem) => {
		alertify.success(mensagem);
	};

	if (!params.data.swal) {
		if (params.message) {
			mostrarMensagem(params.message);
		} else if (!classReturn || !params.html) {
			mostrarMensagem("Nenhum erro foi encontrado");
		}
	}

	if (params.contentPage) {
		$(`.contentPage`).html(params.contentPage);
	} else if (params.html && classReturn) {
		divReturn.html(params.html);
	}

	let tempo = params.data.time || 1000;

	if (!!params.data.openModal) {
		openModal(params.data.openModal);
	}
	if (!!params.data.confirmButton) {
		Swal.fire({
			title: params.data.titulo || "Você tem certeza?",
			text: params.data.text || "O item será removido para sempre!",
			icon: "warning",
			showCancelButton: true,
			confirmButtonColor: "#3085d6",
			cancelButtonColor: "#d33",
			confirmButtonText: params.data.confirmButtonText ?? "Deletar",
		}).then((result) => {
			if (result.isConfirmed) {
				if (!!params.data.url) {
					ajaxSimpleRequest(params.data.url, params.data.params);
				}
				const classToRemove = params.data.class_remove ?? null;

				if (classToRemove) {
					$(`.${classToRemove}`).remove();
				}

				Swal.fire("Deletado!", "O item foi deletado com sucesso.", "success");
			}
		});
	}
	if (params.data.reload) {
		setTimeout(() => {
			location.reload();
		}, tempo);
	}

	if (params.data.clearForm) {
		$(formThis)[0].reset();
	}

	if (params.data.swal) {
		handleSwal(params);
	}
	if (params.data.iframe) {
		iframeShow(params.data.iframe);
	}

	if (params.data.changeLocation) {
		setTimeout(() => {
			window.location.href = url_app + params.data.changeLocation;
		}, tempo);
	}
}

// Função para lidar com o erro no envio do formulário
function handleFormError(params, classReturn, divReturn, formThis) {
	if (classReturn && params.html) {
		divReturn.html(divReturn.html() + params.html);
	}
}

// Função para enviar dados do formulário via AJAX
function sendFormData(
	form,
	successCallback,
	errorCallback = () => { },
	data = {}
) {
	const $form = $(form);
	const formdata = new FormData(form);
	const mensagemValidacao = areAllFieldsValid($form);

	if (!!data) {
		for (const key in data) {
			if (data.hasOwnProperty(key)) {
				formdata.append(key, data[key]);
			}
		}
	}

	if (mensagemValidacao) {
		alertify.error(mensagemValidacao);
		return;
	}

	let url = $form.attr("action");
	if (!url) {
		url = window.location.href;
	} else {
		url = url_app + url;
	}

	$.ajax({
		url: url,
		type: "POST",
		dataType: "json",
		data: formdata,
		cache: false,
		contentType: false,
		processData: false,
		success: (response) => {
			console.log(response);
			if (response.status === "success") {
				if (typeof successCallback === "function") {
					successCallback(response);
				}
			} else {
				mensagem = response.message || "Ocorreu um erro.";
				alertify.error(mensagem);
				if (typeof errorCallback === "function") {
					errorCallback({ status: "error", message: mensagem, data: null });
				}
			}
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
}

async function asyncAjaxSimpleRequest(url, data = {}) {
	return new Promise((resolve, reject) => {
		$.ajax({
			url: url_app + url,
			type: "POST",
			dataType: "json",
			data: data,
			success: function (response) {
				console.log(response);
				resolve(response);
			},
			error: function (error) {
				console.log(error);
				alertify.error("Ocorreu um erro ao comunicar com o servidor");
				reject(error);
			},
		});
	});
}
function ajaxSimpleRequest(
	url,
	data = {},
	successCallback = (response) => {
		return response;
	}
) {

	let fullUrl = url;
    if (!/^https?:\/\//i.test(url)) {
        fullUrl = url_app + url;
    }
    $.ajax({
        url: fullUrl,
		type: "POST",
		dataType: "json",
		data: data,
		cache: false,
		success: function (response) {
			if (typeof successCallback === "function") {
				successCallback(response);
			}
		},
		error: function (error) {
			console.log(error);
			message = "Ocorreu um erro ao comunicar com o servidor";
			alertify.error(message);
		},
	});
}
// Função para validar se todos os campos do formulário estão preenchidos
// Parâmetros:
// - form: O formulário a ser validado.
// Retorna uma mensagem de erro ou false se todos os campos estiverem preenchidos corretamente.
// Função para validar se todos os campos do formulário estão preenchidos
function areAllFieldsValid(form) {
	const fields = $(form).find(
		'input[type="text"], textarea[type="text"], input[type="email"], input[type="password"], input[type="date"], input[type="tel"], input[type="number"], select[type="select"], input[type="cpfpj"]'
	);

	for (let index = 0; index < fields.length; index++) {
		const field = fields[index];
		const value = $(field).val().trim();
		const dataType = $(field).attr("type");
		const required = $(field).prop("required"); // Usar .prop() para verificar a propriedade "required"
		const min = $(field).attr("min");

		let fieldName = $(field).attr("name") || $(field).attr("id"); // Simplificar a obtenção do nome do campo
		$(field).css("border-color", "1px solid #ced4da"); // Corrigir a definição da cor

		switch (dataType) {
			case "text":
				if (required && !value) {
					$(field).css("border-color", "red");
					$(field).focus();
					let name =
						$(field).data("name") ||
						$(field).attr("name") ||
						$(field).attr("id"); // Simplificar a obtenção do nome do campo

					return `O campo ${name} é obrigatório`; // Melhorar a mensagem de erro
				}
				break;
			case "email":
				if (required) {
					const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					if (!emailPattern.test(value)) {
						$(field).css("border-color", "red");
						return "Por favor, insira um endereço de email válido.";
					}
				}
				break;
			case "password":
				if (required && value.length < min) {
					$(field).css("border-color", "red");
					return "A senha deve conter pelo menos 6 caracteres.";
				}
				break;
			case "date":
				if (required) {
					const datePattern = /^\d{4}-\d{2}-\d{2}$/;
					if (!datePattern.test(value)) {
						$(field).css("border-color", "red");
						return "Por favor, insira uma data válida no formato AAAA-MM-DD.";
					}
				}
				break;
			case "tel":
				if (required && value.length < 14) {
					$(field).css("border-color", "red");
					return "Por favor, insira um número de telefone válido (14 dígitos).";
				}
				break;
			case "number":
				if (required && isNaN(value)) {
					$(field).css("border-color", "red");
					return "Por favor, insira um número válido.";
				}
				break;
			case "cpfpj":
				if (required) {
					const pattern =
						/^\d{2}.\d{3}.\d{3}\/\d{4}-\d{2}$|^\d{3}.\d{3}.\d{3}-\d{2}$/g;
					if (!pattern.test(value)) {
						$(field).css("border-color", "red");
						return "Por favor, insira o CPF ou CNPJ corretamente.";
					}
				}
				break;
			case "select":
				if (required) {
					if (value === "") {
						$(field).css("border-color", "red");
						return "Por favor, selecione uma opção!";
					}
				}

			default:
				if (required && $(field).html().length === 0) {
					$(field).css("border-color", "red");
					return "Preencha o campo corretamente.";
				}
		}
	}
	return false;
}

function showNotification(title, text, type) {
	Swal.fire(title, text, type);
}

function showAutoCloseNotification(title, text, milliseconds) {
	Swal.fire({
		title: title,
		text: text,
		timer: milliseconds,
		timerProgressBar: true,
		willOpen: () => {
			Swal.showLoading();
		},
		willClose: () => {
			clearInterval(timerInterval);
		},
	}).then((result) => {
		if (result.dismiss === Swal.DismissReason.timer) {
			console.log("I was closed by the timer");
		}
	});
}
let element;

$(document).ready(function () {
	const $input = $(".ajax-auto-complete");

	if (!$input) return;
	$input.autocomplete({
		onSelect: function (result) {
			const $targetInput = $input;
			const targetSelector = $targetInput.data("autocomplete-target");

			if (targetSelector) {
				const $targetElements = $targetInput.siblings(targetSelector);
				$targetElements.find("input").each(function () {
					const element = $(this);
					const name = element.attr("name") || element.attr("id");
					if (result.hasOwnProperty(name)) {
						element.val(result[name]);
					}
				});
			}
		},
		onNoResults: function (event) {
			alertify.error("Nenhum resultado encontrado.");
			console.log(event);
		},
	});
});

function format() {
	$('input[name="rg"].format, .rg.format').formatarCampo({
		formato1: "99.999.999-9",
	});

	$('input[name="cpf"].format, .cpf.format').formatarCampo({
		formato1: "999.999.999-99",
	});

	$(
		'input[name="cpf_cnpj"].format, input[type="cpfpj"].format, .cpf_cnpj.format'
	).formatarCampo({
		formato1: "999.999.999-99",
		formato2: "99.999.999/9999-99",
	});

	$('input[name="cnpj"].format, .cnpj.format').formatarCampo({
		formato1: "99.999.999/9999-99",
	});

	$(
		'input[name="telefone"].format, input[name="whatsapp"].format, .telefone.format, .whatsapp.format'
	).formatarCampo({
		formato1: "(00) 0000-0000",
		formato2: "(00) 00000-0000",
	});
}
$(document).ready(function () {
	format();
});
(function ($) {
	$.fn.formatarCampo = function (options) {
		const settings = $.extend(
			{
				formato1: "",
				formato2: "",
			},
			options
		);

		return this.each(function () {
			let $this = $(this);
			let formato1 = settings.formato1 || $this.data("formato1");
			let formato2 = settings.formato2 || $this.data("formato2");
			let lengthFormato1 = formato1.replace(/[^0-9]/g, "").length;

			$this.on("input", function () {
				let valor = $this.val().replace(/[^0-9]/g, "");

				if (valor.length < lengthFormato1 || !formato2) {
					$this.mask(formato1);
					console.log(valor, formato1, lengthFormato1);
				} else if (!!formato2) {
					console.log(valor, formato2);

					$this.mask(formato2);
				}
				console.log($this.data());
			});
		});
	};
})(jQuery);

// Impede o envio dos formulários padrão
$("form").on("submit", (e) => {
	e.preventDefault();
});
$("form").each(function () {
	if ($(this).find('input[type="file"]').length > 0) {
		if (!$(this).attr("enctype")) {
			$(this).attr("enctype", "multipart/form-data");
		}
	}
});

// Manipula o clique em elementos com a classe .form-ajax-standard
$(".form-ajax-standard").on("submit", function () {
	sendFormData(this, window.location.reload());
});

function modalsavestandard() {
	$(".modal-save.standard").off("click").on("click", function () {
		const forms = $("#form-modal-unico");
		console.log(this)
		const $this = this;

		forms.each(function () {
			const classReturn = $(this).attr("class-return");
			const divReturn = $(`.${classReturn}`);
			const formThis = this;

			sendFormData(
				this,
				(params) => {
					$(this).trigger("success", params);
					handleFormSuccess(params, classReturn, divReturn, formThis);
					if (!params.data.nocloseModal) $("#modal-unico").modal("hide");
				},
				(params) => {
					handleFormError(params, classReturn, divReturn, formThis);
				}
			);
		});
	});
}
modalsavestandard()
// Manipula o clique em elementos com a classe .floating-save
$(".floating-save").click(function () {
	const forms = $("form.form-save");

	forms.each(function () {
		const classReturn = $(this).attr("class-return");
		const divReturn = $(`.${classReturn}`);
		const formThis = this;

		sendFormData(
			this,
			(params) => {
				handleFormSuccess(params, classReturn, divReturn, formThis);
			},
			(params) => {
				handleFormError(params, classReturn, divReturn, formThis);
			}
		);
	});
});

function btn_delete(data = {}) {
	$(".btn-delete").on("click", function () {
		confirmacaoDelete(data)
	});
}
function confirmacaoDelete(data = {}) {
	const {
		sucessocalback = () => { },
		cancelarcalback = () => { },
		title = data.title ?? $(this).data("title") ?? "Você tem certeza?",
		text = data.text ?? $(this).data("text") ?? "O item será removido para sempre!",
		confirmButtonText = "Sim",
	} = data;

	const id = data.id ?? $(this).data("id");
	const url = data.url ?? $(this).data("url");
	const classToRemove = $(this).data("class-remove");

	if (!id) {
		return alertify.error("ID não encontrado no atributo data-id");
	}

	if (!url) {
		return alertify.error("URL não encontrado no atributo data-url");
	}

	Swal.fire({
		title: title,
		text: text,
		icon: "warning",
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
		confirmButtonText: confirmButtonText,
	}).then((result) => {
		if (result.isConfirmed) {
			ajaxSimpleRequest(url, { id: id }, (response) => {
				if (classToRemove) {
					$(`.${classToRemove}`).remove();
				}
				sucessocalback(response);
			});
		} else {
			cancelarcalback();
		}
	});
}



btn_delete();
function bindElementToRequest(element, page, subPage) {
	$(element).on("click", function () {
		const id = $(this).data("id");
		const classList = $(this)[0].classList;

		console.log($(this));

		let requestSubPage = "";
		if (
			classList.contains(
				element.replace(
					/([:\.\[\]\!\#\$\\\"\|\%\&\'\(\)\*\+\,\/\;\<\=\>\?\@\^\`\{\}\~])/g
				)
			)
		)
			requestSubPage = subPage;

		console.log(`/${page}/${method}/${id}`);

		id === undefined
			? ajaxSimpleRequest(`/${page}/${requestSubPage}`)
			: ajaxSimpleRequest(`/${page}/${requestSubPage}/${id}`);
	});
}

function getUrlParameter(name) {
	name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
	let regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
	let results = regex.exec(location.search);
	return results === null
		? ""
		: decodeURIComponent(results[1].replace(/\+/g, " "));
}

function checkPasswordStrength(password, check = false) {
	let strength = 0;
	if (password.match(/[a-z]+/)) {
		strength += 1;
	}
	if (password.match(/[A-Z]+/)) {
		strength += 1;
	}
	if (password.match(/[0-9]+/)) {
		strength += 1;
	}
	if (password.match(/[$@#&!]+/)) {
		strength += 1;
	}

	let strengthBar = $(".strength-bar");

	if (!check) {
		switch (strength) {
			case 0:
				strengthBar.html("");
				break;

			case 1:
				strengthBar.html(
					"<small class='progress-bar bg-danger' style='width: 25%'>Muito Fraca</small>"
				);
				break;

			case 2:
				strengthBar.html(
					"<small class='progress-bar bg-primary' style='width: 50%'>Fraca</small>"
				);
				break;

			case 3:
				strengthBar.html(
					"<small class='progress-bar bg-warning' style='width: 75%'>Média</small>"
				);
				break;

			case 4:
				strengthBar.html(
					"<small class='progress-bar bg-success' style='width: 96%; background-color: #55b96c !important;'>Forte</small>"
				);
				break;
		}
	}

	return strength;
}

//#endregion

function geradorCombinações(length) {
	let charset =
		"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789",
		retVal = "";
	for (let i = 0, n = charset.length; i < length; ++i) {
		retVal += charset.charAt(Math.floor(Math.random() * n));
	}
	return retVal;
}

//* Enum de ações
const Actions = {
	Edit: "Edit",
	EditDelete: "EditDelete",
	None: "None",
};

/*
 * @param { Object[] } data - Array dos dados que vão compor a tabela
 * @param { Object } options = {
 *		{ String[] } ignoreProp - Campos inseridos aqui não irão aparecer na tabela
 *		{ Boolean } searchBar - Insere a barra de pesquisa de dados
 *		{ Boolean } sortColumns - Faz com que clicar no heading da tabela ordene os dados
 *		{ Object } tableHeaders - Valores inseridos aqui poderão sobescrever os headers. exemplo: { id: "ID" }
 *		{ Boolean } pagination - Ativa a paginação da tabela
 *		{ String } action - Insere os botões de ação no final da tabela (edit e/ou delete)
 *		{ String} O resto são apenas dados comuns de modal
 * }
 */
function generateDataTable(
	data,
	{
		ignoreProp = [],
		searchBar = true,
		sortColumns = true,
		tableHeaders = { id: "ID" },
		pagination = false,
		action = Actions.None,
		modalTitle = "default",
		modalViewParams = "",
		modalView = "",
		modalFormAction = "",
	} = {}
) {
	if (!data) return;

	//? IgnoreProp - Remove campos indesejados da tabela
	if (!!ignoreProp)
		if (ignoreProp.length > 0)
			data.forEach((obj) => {
				ignoreProp.forEach((prop) => {
					delete obj[prop];
				});
			});

	const keys = Object.keys(data[0]);

	//#region Tabela

	if (searchBar) {
		let div = $('<div class="float-right"></div>');
		$(".dynamic-table").append(div);
		div.append('<label style="margin-right: 10px;">Buscar:</label>');
		div.append('<input type="text" class="datatable-search"/>');
	}

	$(".dynamic-table").append("<table>");

	$(".dynamic-table table").append("<thead>");

	let trHead = $("<tr></tr>");
	$(".dynamic-table table thead").append(trHead);

	keys.forEach((key) => {
		//? tableHeaders - Troca o header por outros nomes
		if (key in tableHeaders) {
			trHead.append(
				`<th>${tableHeaders[key].charAt(0).toUpperCase() +
				tableHeaders[key].slice(1).replace("_", " ")
				}</th>`
			);
		} else {
			trHead.append(
				`<th>${key.charAt(0).toUpperCase() + key.slice(1).replace("_", " ")
				}</th>`
			);
		}
	});

	if (action != Actions.None && action != Actions.EditDelete)
		trHead.append('<th style="width: 0%">Ações</th>');

	if (action == Actions.EditDelete) {
		trHead.append('<th style="width: 0%">Editar</th>');
		trHead.append('<th style="width: 0%">Deletar</th>');
	}

	$(".dynamic-table table").append("</thead>");

	$(".dynamic-table table").append("<tbody>");

	//? data -> [{a: 'a', b: 'b'}, {a: 'c', b: 'd'}, {a: 'e', b: 'f'}]
	Object.values(data).forEach((row) => {
		//? row -> {a: 'a', b: 'b'} ...
		let tr = $("<tr></tr>");
		$(".dynamic-table table tbody").append(tr);

		Object.values(row).forEach((cell) => {
			//? cell -> a: 'a' ...
			tr.append(`<td>${cell}</td`);
		});

		//#region Row Actions
		if (action == Actions.Edit)
			tr.append(`
				<td>
					<button class="btn btn-warning" onclick="openModal('', $(this))" data-id="${row.id}" data-title="${modalTitle}" data-view-params="${modalViewParams}" data-view="${modalView}" data-form-action="${modalFormAction}">
						<i class="fa fa-pen"></i>
					</button>
				</td>
				openModal
			`);

		if (action == Actions.EditDelete)
			tr.append(`
				<td>
					<button class="btn btn-warning open-modal" data-id="${row.id}" data-title="${modalTitle}" data-view-params="${modalViewParams}" data-view="${modalView}" data-form-action="${modalFormAction}">
						<i class="fa fa-pen"></i>
					</button>
				</td>
				<td>
					<button class="btn btn-danger" data-id="${row.id}"><i class="fas fa-trash"></i></button>
				</td>
			`);

		//#endregion
	});

	$(".dynamic-table table").append("</tbody>");
	$(".dynamic-table").append("</table>");
	$(".dynamic-table table").addClass(
		"table table-small table-striped table-vcenter font-size-sm table-borderless"
	);
	$(".dynamic-table table thead").addClass("table-dark");

	//#endregion

	//! Sorting - Pode ignorar como ela funciona, não tem pq refatorar isso...
	if (sortColumns) {
		$(".dynamic-table table th").click(function () {
			var table = $(this).parents("table").eq(0);
			var rows = table
				.find("tr:gt(0)")
				.toArray()
				.sort(comparer($(this).index()));
			this.asc = !this.asc;
			if (!this.asc) {
				rows = rows.reverse();
			}
			for (var i = 0; i < rows.length; i++) {
				table.append(rows[i]);
			}
		});
		const comparer = (index) => {
			return function (a, b) {
				var valA = getCellValue(a, index),
					valB = getCellValue(b, index);
				return $.isNumeric(valA) && $.isNumeric(valB)
					? valA - valB
					: valA.toString().localeCompare(valB);
			};
		};
		const getCellValue = (row, index) => {
			return $(row).children("td").eq(index).text();
		};
	}

	//! Search - Essa também, é muito complexo pra ter motivo pra mexer
	if (searchBar) {
		$(".datatable-search").on("keyup", function () {
			var input, filter, table, tr, td, i;
			input = $(this);
			filter = input.val().toUpperCase();
			table = document.querySelector(".dynamic-table table tbody");
			tr = table.getElementsByTagName("tr");

			var searchColumn = Object.keys(keys);

			for (i = 0; i < tr.length; i++) {
				if ($(tr[i]).parent().attr("class") == "head") {
					continue;
				}

				var found = false;
				for (var k = 0; k < searchColumn.length; k++) {
					td = tr[i].getElementsByTagName("td")[searchColumn[k]];

					if (td) {
						if (td.innerHTML.toUpperCase().indexOf(filter) > -1) {
							found = true;
						}
					}
				}
				if (found == true) {
					tr[i].style.display = "";
				} else {
					tr[i].style.display = "none";
				}
			}
		});
	}
}

$(".manual-dropdown-btn").on("click", function () {
	$(".manual-dropdown").toggleClass("show");
});

$("body").on("click", function (e) {
	if (
		!$(".manual-dropdown").is(e.target) &&
		$(".manual-dropdown").has(e.target).length === 0 &&
		$(".manual-dropdown.show").has(e.target).length === 0 &&
		$(".manual-dropdown-btn").has(e.target).length === 0
	) {
		$(".manual-dropdown").removeClass("show");
	}
});

$("#dropdownFilter a").on("click", function () {
	$(this).toggleClass("active");
	console.log($("#dropdownFilter a.active").text());
});

$(".date-max").attr("min", $(".date-min").val());
$(".date-min").attr("max", $(".date-max").val());
$(".date-min, .date-max").change(function () {
	let option = {
		year: "numeric",
		month: "short",
		day: "numeric",
	};
	let result = new Date($(".date-min").val()).toLocaleString("pt-BR", option);
	result += " a ";
	result += new Date($(".date-max").val()).toLocaleString("pt-BR", option);
	$(".date-result").html(result);
	$(".date-max").attr("min", $(".date-min").val());
	$(".date-min").attr("searchmax", $(".date-max").val());
});

(function ($) {
	$.fn.generatePagination = function (page, end_itens, countRows) {
		return this.each(function () {
			$this = $(this);
			let delayTimer;
			let url;
			page = page ?? $this.data("page") ?? 1;
			end_itens = end_itens ?? $this.data("end-itens") ?? 12;
			countRows = countRows ?? $this.data("count-rows") ?? 12;
			url = url ?? $this.data("url") ?? window.location.href;

			var paginationContainer = $(this);
			paginationContainer.empty();

			var totalPages = Math.ceil(countRows / end_itens);
			var pagesToShow = 10; // Total de páginas para mostrar (5 anteriores + página atual + 5 posteriores)

			if (totalPages < pagesToShow) {
				pagesToShow = totalPages;
			}

			var ul = $("<ul>").addClass("pagination justify-content-center");

			// Calcula o início e o fim das páginas a serem mostradas
			var startPage = Math.max(1, Math.min(page - 5, totalPages - 9));
			var endPage = Math.min(totalPages, startPage + 9);

			// Verifica se a página atual é a primeira ou última para desativar os botões
			var isPreviousDisabled = page === 1;
			var isNextDisabled = page === totalPages;

			var liPrev = $("<li>").addClass(
				"page-item " + (isPreviousDisabled ? "disabled" : "")
			);
			var liNext = $("<li>").addClass(
				"page-item " + (isNextDisabled ? "disabled" : "")
			);

			var linkPrev = $("<a>")
				.addClass("page-link performAccountSearch")
				.attr("href", "javascript:void(0);")
				.attr("data-page", page - 1)
				.attr("data-end-itens", end_itens)
				.attr("data-count-rows", countRows)
				.attr("tabindex", "-1")
				.text("Anterior");

			var linkNext = $("<a>")
				.addClass("page-link performAccountSearch")
				.attr("href", "javascript:void(0);")
				.attr("data-page", page + 1)
				.attr("data-end-itens", end_itens)
				.attr("data-count-rows", countRows)
				.text("Próximo");

			liPrev.append(linkPrev);
			liNext.append(linkNext);

			ul.append(liPrev);

			for (var i = startPage; i <= endPage; i++) {
				var li = $("<li>").addClass("page-item" + (page == i ? " active" : ""));
				var link = $("<a>")
					.addClass("page-link performAccountSearch")
					.attr("href", "javascript:void(0);")
					.attr("data-page", i)
					.attr("data-end-itens", end_itens)
					.attr("data-count-rows", countRows)
					.text(i);
				li.append(link);
				ul.append(li);
			}

			ul.append(liNext);
			paginationContainer.append(ul);

			function performAccountSearch(search, page, end_itens, time = 300) {
				let params = {
					page: page ?? 1,
					end_itens: end_itens ?? 24,
					countRows: countRows ?? null,
					search: search ?? "",
				};

				if (delayTimer) {
					clearTimeout(delayTimer);
				}
				delayTimer = setTimeout(function () {
					$this.trigger("page-click", params);
				}, time);
			}

			$(".performAccountSearch").on("click", function () {
				var search = "";
				var page = $(this).data("page");
				var end_itens = $(this).data("end-itens");
				updateURLParameter("page", page);
				var paramsToUpdate = {
					search: search,
					page: page,
					end_itens: end_itens,
				};

				performAccountSearch(search, page, end_itens, 0);

				$("html, body").animate({ scrollTop: 0 }, "slow");

				updateURLParameters(paramsToUpdate);
			});
		});
	};
})(jQuery);

function updateURLParameter(key, value) {
	const urlParams = new URLSearchParams(window.location.search);
	if (value === "" || value === null || value === undefined) {
		urlParams.delete(key);
	} else {
		urlParams.set(key, value);
	}

	const newURL = `${window.location.pathname}?${urlParams.toString()}`;
	history.pushState(null, null, newURL);
}
function updateURLParameters(paramsToUpdate) {
	const urlParams = new URLSearchParams(window.location.search);

	for (const [key, value] of Object.entries(paramsToUpdate)) {
		if (value === "" || value === null || value === undefined) {
			urlParams.delete(key);
		} else {
			urlParams.set(key, value);
		}
	}

	const newURL = `${window.location.pathname}?${urlParams.toString()}`;
	history.pushState(null, null, newURL);
}
function copytext() {
	$('.copy-text').on('click', (e) => {
		let txt = e.target.innerText.trim()

		if (navigator.clipboard.writeText(txt)) {
			e.target.innerHTML += ' <i class="fas fa-check icon-response" style="font-size: .7rem color: #f85359"></i>'
		}

		setTimeout(() => {
			$('.icon-response').css('display', 'none')
		}, 1000)

	})
}
copytext()

function mostrarConfirmacao(data = {}) {
	const {
		sucessocalback = () => { },
		cancelarcalback = () => { },
		title = "Tem certeza?",
		text = "Tem certeza?",
		confirmButtonText = "Sim",
	} = data;
	Swal.fire({
		title: title,
		html: text,
		icon: "warning",
		showCancelButton: true,
		confirmButtonColor: "#3085d6",
		cancelButtonColor: "#d33",
		confirmButtonText: confirmButtonText
	}).then((result) => {
		if (result.isConfirmed) {
			sucessocalback();
		} else {
			cancelarcalback();
		}
	});
}

function slugify(text) {
	var sanitizedText = text.toString().toLowerCase()
		.replace(/\s+/g, '-')           // Substitui espaÃ§os por hÃ­fens
		.normalize('NFD')               // Remove acentos
		.replace(/[\u0300-\u036f]/g, '')  // Remove outros caracteres especiais
		.replace(/[^a-z0-9-_]/g, '');     // Remove caracteres nÃ£o permitidos

	var rejectedCharacters = text.replace(new RegExp('[a-z0-9-_]', 'g'), '');

	if (rejectedCharacters.length > 0) {
		alertify.error(`Os seguintes caracteres foram rejeitados: ${rejectedCharacters}`);
	}

	return sanitizedText;
}


function getStatus(statusCode) {
	switch (statusCode) {
		case 0:
			return "Inativo";
		case 1:
			return "Ativo";
		case 2:
		case 3:
			return "Pago";
		case 51:
			return "Inválido";
		default:
			return "Desconhecido";
	}
}

function formatarData(dataString) {
	const [ano, mes, diaHora] = dataString.split(' ')[0].split('-');
	const [dia, hora] = diaHora.split(' ');
	return `${dia}/${mes}/${ano}`;
}

function formatarValor(valor) {
	const valorFormatado = parseFloat(valor.replace(',', '.')).toFixed(2);
	return `R$ ${valorFormatado.replace('.', ',')}`;
}
$('table[data-url]').loadTableData();
