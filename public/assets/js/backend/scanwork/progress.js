define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts'], function ($, undefined, Backend, Table, Form, echarts) {

    var Controller = {
        index: function () {
            Controller.api.initCharts();
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            initCharts: function(){
                // 基于准备好的dom，初始化echarts实例
                var orderStatusChart = echarts.init(document.getElementById('order-status-chart'));
                var workerStatsChart = echarts.init(document.getElementById('worker-stats-chart'));
                var dailyTrendChart = echarts.init(document.getElementById('daily-trend-chart'));
                var processStatsChart = echarts.init(document.getElementById('process-stats-chart'));

                // 指定图表的配置项和数据
                var orderStatusOption = {
                    tooltip: { trigger: 'item', formatter: "{a} <br/>{b}: {c} ({d}%)" },
                    legend: { orient: 'vertical', left: 10, data: [] },
                    series: [ { name: '订单状态', type: 'pie', radius: '55%', center: ['50%', '60%'], data: [], itemStyle: { emphasis: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)' } } } ]
                };
                var workerStatsOption = {
                    tooltip: { trigger: 'axis' },
                    legend: { data:['报工数', '总工资'] },
                    xAxis: { type: 'category', data: [] },
                    yAxis: { type: 'value' },
                    series: [ { name: '报工数', type: 'bar', data: [] }, { name: '总工资', type: 'bar', data: [] } ]
                };
                var dailyTrendOption = {
                    tooltip: { trigger: 'axis' },
                    legend: { data:['报工数', '总工资'] },
                    xAxis: { type: 'category', data: [] },
                    yAxis: { type: 'value' },
                    series: [ { name: '报工数', type: 'line', data: [] }, { name: '总工资', type: 'line', data: [] } ]
                };
                var processStatsOption = {
                    tooltip: { trigger: 'item', formatter: "{a} <br/>{b}: {c} ({d}%)" },
                    legend: { orient: 'vertical', left: 10, data: [] },
                    series: [ { name: '工序', type: 'pie', radius: '55%', center: ['50%', '60%'], data: [], itemStyle: { emphasis: { shadowBlur: 10, shadowOffsetX: 0, shadowColor: 'rgba(0, 0, 0, 0.5)' } } } ]
                };

                // 使用刚指定的配置项和数据显示图表。
                orderStatusChart.setOption(orderStatusOption);
                workerStatsChart.setOption(workerStatsOption);
                dailyTrendChart.setOption(dailyTrendOption);
                processStatsChart.setOption(processStatsOption);

                // 异步加载数据
                $.getJSON('/lsj5492li.php/scanwork/progress/getOverallProgress').done(function (data) {
                    orderStatusChart.setOption({
                        legend: { data: data.data.map(function(item){return item.name;}) },
                        series: [{ data: data.data }]
                    });
                });
                $.getJSON('/lsj5492li.php/scanwork/progress/getWorkerStats').done(function (data) {
                    workerStatsChart.setOption({
                        xAxis: { data: data.data.map(function(item){return item.user_name;}) },
                        series: [ { data: data.data.map(function(item){return item.total_quantity;}) }, { data: data.data.map(function(item){return item.total_wage;}) } ]
                    });
                });
                $.getJSON('/lsj5492li.php/scanwork/progress/getDailyTrend').done(function (data) {
                    dailyTrendChart.setOption({
                        xAxis: { data: data.data.map(function(item){return item.date;}) },
                        series: [ { data: data.data.map(function(item){return item.total_quantity;}) }, { data: data.data.map(function(item){return item.total_wage;}) } ]
                    });
                });
                $.getJSON('/lsj5492li.php/scanwork/progress/getProcessStats').done(function (data) {
                    processStatsChart.setOption({
                        legend: { data: data.data.map(function(item){return item.process_name;}) },
                        series: [{ data: data.data.map(function(item){return {name:item.process_name, value:item.total_quantity};}) }]
                    });
                });
                
                // 订单进度表格
                var table = $("#order-progress-table");
                table.bootstrapTable({
                    url: '/lsj5492li.php/scanwork/progress/getOrderProgress',
                    columns: [
                        [                            
                            {field: 'order_no', title: '订单号'},
                            {field: 'customer_name', title: '客户名称'},
                            {field: 'total_quantity', title: '总数量'},
                            {field: 'progress', title: '进度', formatter: function(value){return value + '%';}},
                            {field: 'status_text', title: '状态'},
                            {field: 'createtime', title: '创建时间', formatter: Table.api.formatter.datetime}
                        ]
                    ]
                });
            }
        }
    };
    return Controller;
});