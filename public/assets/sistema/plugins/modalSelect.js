(function ($) {
	$.fn.modalSelect = function (options) {
		const defaults = {
			data: [],
			url: null,
		};

		const settings = $.extend({}, defaults, options);

		return this.each(function () {
			const openModalBtn = $(this);
			const modal = $("#modal");
			const dataTable = $("#dataTable");
			const addBtn = $("#addBtn");
			const selectItemBtn = $("#selectItemBtn");
			const cancelSelectionBtn = $("#cancelSelectionBtn");
			const closeModalBtn = $("#closeModalBtn");
			const searchInput = document.getElementById("searchInput");
			let selectedItem = null;
			let data = [...settings.data];
			let allColumns = null;
			let dataAll = [];
			const ItemSelectedEvent = $.Event("item-selected");
			const closeEvent = $.Event("close");

			if (settings.url) {
				openModalBtn.on("click", () => {
					searchInput.setAttribute("autocomplete", "off");

					ajaxSimpleRequest(settings.url, {}, function (response) {
						data = extrairPropriedades(response);
						populateTable(data);
						modal.css("display", "block");
					});
				});
			} else {
				openModalBtn.on("click", () => {
					modal.css("display", "block");
					selectedItem = null;
					selectItemBtn.attr("disabled", true);
					if (settings.headerTable) {
						data.unshift(settings.headerTable);
					}
					populateTable(data);
				});
			}

			if (settings.addButton) {
				addBtn.addClass("col-2");
				addBtn.html(`
                <button class="form-control btn btn-primary open-modal" data-title="${settings.addButton.title}" data-cancel="Cancelar" data-save="Salvar" data-view="${settings.addButton.view}" data-form-action="${settings.addButton.formAction}">
                    <i class=" fa fa-plus"></i>
                </button>
                `);
			}

            if (settings.title) {
                $('#modalTitle').text(settings.title);
            }

			searchInput.addEventListener("input", () => {
				const searchText = searchInput.value.toLowerCase();

				// Percorre todas as linhas da tabela
				dataTable.find("tr").each(function () {
					const row = $(this);

					// Pula a primeira linha (cabeçalho)
					if (row.index() === 0) {
						return;
					}

					// Verifica se alguma célula da linha contém o texto da pesquisa
					const shouldDisplay = row
						.find("td")
						.get()
						.some((cell) => {
							const cellText = cell.textContent.toLowerCase();
							return cellText.includes(searchText);
						});

					// Aplica display: none para ocultar ou display: table-row para mostrar a linha
					if (shouldDisplay) {
						row.css("display", "table-row");
					} else {
						row.css("display", "none");
					}
				});

				// Reinicializa a seleção
				selectedItem = null;
			});

			function extrairPropriedades(objects) {
				if (objects.length === 0 || !objects[0]) {
					return settings.columns;
				}

				if (!settings.columns) {
					settings.columns = Object.keys(objects[0]);
				}
				allColumns = Object.keys(objects[0]) || settings.columns;

				const result = [settings.headerTable || settings.columns];
				const resultAll = [allColumns];

				objects.forEach((object) => {
					const row = [];
					const rowAll = [];

					settings.columns.forEach((property) => {
						row.push(object[property]);
					});
					allColumns.forEach((property) => {
						rowAll.push(object[property]);
					});
					result.push(row);
					resultAll.push(rowAll);
				});
				dataAll = resultAll;
				return result;
			}
			function populateTable(data, limit = 100) {
				dataTable.empty();
			
				for (let i = 0; i < Math.min(data.length, limit); i++) {
					const row = $(`<tr row="${i}"></tr>`);
					for (let j = 1; j < data[i].length; j++) {
						const cell = $("<td></td>");
						const cellValue = data[i][j];
			
						if (cellValue !== null && cellValue !== undefined) {
							cell.text(cellValue.toString());
						} else {
							cell.text("");
						}
			
						cell.on("dblclick", function () {
							if (selectedItem) {
								selectedItem.removeClass("selected-row");
							}
							selectedItem = $(this).parent();
							selectedItem.addClass("selected-row");
			
							selectItem();
						});
						row.append(cell);
					}
			
					row.on("click", function () {
						if (selectedItem) {
							selectedItem.removeClass("selected-row");
						}
						selectedItem = row;
						row.addClass("selected-row");
					});
					dataTable.append(row);
				}
				const rows = dataTable.find("tr");
				if (rows.length > 1) {
					selectedItem = rows.eq(1);
					selectedItem.addClass("selected-row");
					scrollToSelected(selectedItem);
				}
				searchInput.focus();
			}
			
			function selectItem() {
				if (selectedItem) {
					const rowIndex = selectedItem.index();
					if (rowIndex > 0) {
						const selectedDataRow = dataAll[rowIndex];

						const selectedDataObject = {};
						allColumns.forEach((column, columnIndex) => {
							selectedDataObject[column] = selectedDataRow[columnIndex];
						});

						ItemSelectedEvent.selectedData = selectedDataObject;
						openModalBtn.trigger(ItemSelectedEvent);
					}
					modal.modal("hide");
				}
			}

			openModalBtn.on("click", () => {
				modal.on("shown.bs.modal", function () {
					searchInput.focus();
				});
				selectedItem = null;
				populateTable(data);
				modal.modal("show");
				searchInput.focus();
			});

			closeModalBtn.on("click", () => {
				modal.modal("hide");
				openModalBtn.trigger(closeEvent);
			});

			selectItemBtn.on("click", () => {
				selectItem();
			});

			cancelSelectionBtn.on("click", () => {
				if (selectedItem) {
					selectedItem.removeClass("selected-row");
					selectedItem = null;
				}
			});

			$(document).on("keydown", function (e) {
				if (modal.css("display") === "block") {
					if (e.key === "Enter") {
						selectItem();
					} else if (e.key === "ArrowUp" || e.key === "ArrowDown") {
						e.preventDefault(); // Impedir a rolagem da página
						const rows = dataTable.find("tr");
						if (selectedItem) {
							const currentIndex = rows.index(selectedItem);
							selectedItem.removeClass("selected-row");

							// Função para encontrar a próxima linha visível
							function findNextVisibleRow(indexIncrement) {
								let nextIndex = currentIndex + indexIncrement;
								while (nextIndex >= 0 && nextIndex < rows.length) {
									const nextRow = rows.eq(nextIndex);
									if (nextRow.css("display") !== "none") {
										return nextRow;
									}
									nextIndex += indexIncrement;
								}
								return null; // Nenhuma linha visível encontrada
							}

							if (e.key === "ArrowUp") {
								// Navegar para cima
								const prevVisibleRow = findNextVisibleRow(-1);
								if (prevVisibleRow) {
									selectedItem = prevVisibleRow;
								}
							} else if (e.key === "ArrowDown") {
								// Navegar para baixo
								const nextVisibleRow = findNextVisibleRow(1);
								if (nextVisibleRow) {
									selectedItem = nextVisibleRow;
								}
							}
						} else {
							selectedItem = rows.eq(1); // Comece da segunda linha (índice 1)
						}
						if (selectedItem) {
							selectedItem.addClass("selected-row");
							scrollToSelected(selectedItem);
						}
					}
				}
			});

			function scrollToSelected(selectedElement) {
				// Verificar se o elemento está visível na tabela
				if (selectedElement[0] && selectedElement[0].scrollIntoView) {
					selectedElement[0].scrollIntoView({
						behavior: "auto",
						block: "nearest",
					});
				}
			}
		});
	};
})(jQuery);
// $('#pesquisa-dgv-clientes').modalSelect({
//     url: '/clientes/buscar',
//     columns: ['id', 'nome', 'rg'],
//     headerTable: ['ID', 'Nome', 'RG']
// });

// $('#pesquisa-dgv-clientes').on('item-selected', function (event) {
//     const cliente = event.selectedData;
//     console.log('Nome do Cliente selecionado:', cliente.nome);
// });