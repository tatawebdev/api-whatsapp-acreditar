(function ($) {
    'use strict';

    function createCharts({ morris_bar, morris_donut, combinedData }) {
        var barChartData = [];
        var donutChartData = [];
        var barLabels = [];

        combinedData.forEach(function (data) {
            barChartData.push({
                y: data.titulo,
                a: data.a,
                b: data.b
            });

            barLabels.push(data.y);

            donutChartData.push({
                label: data.label,
                value: data.value
            });
        });

        if ($('#' + morris_bar).length) {
            Morris.Bar({
                element: morris_bar,
                barColors: ['#F44336'],
                data: barChartData,
                xkey: 'y',
                ykeys: ['a'],
                hideHover: 'auto',
                gridLineColor: '#eef0f2',
                resize: true,
                barSizeRatio: 0.4,
                labels: barLabels
            });
        }

        if ($('#' + morris_donut).length) {
            Morris.Donut({
                element: morris_donut,
                resize: true,
                colors: ['#7266bb', '#1d84c6', '#f85359'],
                data: donutChartData
            });
        }
    }

    window.createCharts = createCharts;
})(jQuery);
