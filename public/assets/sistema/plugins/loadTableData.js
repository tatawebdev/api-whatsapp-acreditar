$.fn.loadTableData = function (page = 1) {
    function processarTabela($table, paginaTable) {
        $(".pesquisar-tabela").off("input").on("input", (function () {
            processarTabela($table, paginaTable)
        }));


        let url = $table.data('url');
        var params = {};
        if ($table.find('thead th.acoes').length) {
            $table.find('thead th.acoes').remove();
        }

        $table.find('thead th').each(function () {
            var bd = $(this).attr('bd');
            params[bd] = true;
        });
        console.log();
        params['pagina'] = paginaTable;
        params['pesquisar'] = $($table.data("pesquisar")).val();

        if (url) {
            ajaxSimpleRequest(url, params, function (data) {

                $table.find('tbody').empty();


                $.each(data.dados, function (index, item) {
                    var row = '<tr>';
                    $table.find('thead th').each(function () {
                        var bd = $(this).attr('bd');
                        row += '<td class="text-center">' + item[bd] + '</td>';
                    });
                    row += '<td class="text-center">';
                    row += '<button class="btn btn-primary waves-effect waves-light td-btn-editar" data-id="' + item['id'] + '"><i class="fa fa-edit"></i></button>';
                    row += '<button class="btn btn-primary waves-effect waves-light td-btn-excluir" data-id="' + item['id'] + '"><i class="fa fa-trash"></i></button>';
                    row += '</td>';

                    row += '</tr>';
                    $table.find('tbody').append(row);
                });

                if (!$table.find('thead th.acoes').length) {
                    var $thAcoes = $('<th class="acoes text-center">Ações</th>');
                    $table.find('thead tr').append($thAcoes);
                }

                // Verifica se $paginationContainer já existe
                var $paginationContainer = $table.next('.pagination');
                if (!$paginationContainer.length) {
                    $paginationContainer = $('<div class="pagination text-center" style="display: flex; justify-content: center;"></div>');

                    $table.after($paginationContainer);

                } else {
                    $paginationContainer.empty();
                }

                // Adiciona navegação
                for (var i = 1; i <= data.pages; i++) {
                    if (i == 1 || i == data.pages || (i >= data.pageCurrent - 2 && i <= data.pageCurrent + 7)) {
                        var $pageLink = $('<a href="#" class="page-link">' + i + '</a>');
                        if (i == data.pageCurrent) {
                            $pageLink.addClass('active');
                        }
                        // Usando uma função anônima para garantir que i tenha o valor correto quando o link for clicado
                        $pageLink.click((function (pageNumber) {
                            return function () {
                                processarTabela($table, pageNumber); // Chamando a função processarTabela com o número da página
                            };
                        })(i));

                        $paginationContainer.append($pageLink);
                    } else if ($paginationContainer.children().last().text() !== "...") {
                        // Adiciona "..." para indicar que há mais páginas disponíveis
                        $paginationContainer.append($('<span>...</span>'));
                    }
                }

                $table.find('.td-btn-editar').click(function () {
                    var id = $(this).data('id');
                    $table.trigger('editar', { id: id, pagina: paginaTable });
                });

                $table.find('.td-btn-excluir').click(function () {
                    var id = $(this).data('id');
                    $table.trigger('excluir', { id: id, pagina: paginaTable });
                });

            });
        } else {
            console.error('A URL não está definida para a tabela.');
        }
    }
    return this.each(function () {
        var $table = $(this);

        processarTabela($table, 1);
    });
};




$.fn.reload = function () {
    return this.each(function () {
        $(this).loadTableData();
    });
};
