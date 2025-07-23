define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'echarts'], function ($, undefined, Backend, Table, Form, echarts) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'scanwork/wage/index',
                    add_url: 'scanwork/wage/add',
                    edit_url: 'scanwork/wage/edit',
                    del_url: 'scanwork/wage/del',
                    multi_url: 'scanwork/wage/multi',
                    table: 'scanwork_report',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                sortOrder: 'desc',
                pagination: true,
                commonSearch: false,
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'user.nickname', title: __('员工姓名'), align: 'left', formatter: function(value, row, index) {
                            return value || '-';
                        }},
                        {field: 'allocation.order.order_no', title: __('订单号'), align: 'left', formatter: function(value, row, index) {
                            return value || '-';
                        }},
                        {field: 'allocation.model.product.name', title: __('产品名称'), align: 'left', formatter: function(value, row, index) {
                            return value || '-';
                        }},
                        {field: 'allocation.model.name', title: __('型号名称'), align: 'left', formatter: function(value, row, index) {
                            return value || '-';
                        }},
                        {field: 'allocation.process.name', title: __('工序名称'), align: 'left', formatter: function(value, row, index) {
                            return value || '-';
                        }},
                        {field: 'quantity', title: __('报工数量'), align: 'center'},
                        {field: 'wage', title: __('计件工资'), align: 'center', formatter: function(value, row, index) {
                            return '¥' + parseFloat(value || 0).toFixed(2);
                        }},
                        {field: 'status', title: __('状态'), searchList: {"0":__('待确认'),"1":__('已确认'),"2":__('已拒绝')}, formatter: function(value, row, index) {
                            var statusMap = {
                                0: {text: '待确认', class: 'label-warning'},
                                1: {text: '已确认', class: 'label-success'},
                                2: {text: '已拒绝', class: 'label-danger'}
                            };
                            var status = statusMap[value] || {text: '未知', class: 'label-default'};
                            return '<span class="label ' + status.class + '">' + status.text + '</span>';
                        }},
                        {field: 'createtime', title: __('报工时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        summary: function () {
            // 加载员工列表
            $.get('user/user/index', function(data) {
                if (data.rows) {
                    var html = '<option value="">全部员工</option>';
                    data.rows.forEach(function(user) {
                        html += '<option value="' + user.id + '">' + user.nickname + '</option>';
                    });
                    $('#user_id').html(html);
                }
            });

            // 查询按钮
            $('#search-btn').click(function() {
                loadSummaryData();
                loadChartData();
            });

            // 导出按钮
            $('#export-btn').click(function() {
                var params = $('#search-form').serialize();
                window.open('scanwork/wage/exportSummary?' + params, '_blank');
            });

            // 加载汇总数据
            function loadSummaryData() {
                var params = $('#search-form').serialize();
                $.get('scanwork/wage/summary?' + params, function(data) {
                    if (data.code === 1) {
                        updateSummaryStats(data.data.total);
                        updateSummaryTable(data.data.summary);
                    }
                });
            }

            // 更新统计信息
            function updateSummaryStats(total) {
                $('#total-users').text(total.count || 0);
                $('#total-quantity').text(total.quantity || 0);
                $('#total-wage').text('¥' + parseFloat(total.wage || 0).toFixed(2));
                $('#total-count').text(total.count || 0);
            }

            // 更新汇总表格
            function updateSummaryTable(summary) {
                var html = '';
                if (summary && summary.length > 0) {
                    summary.forEach(function(item) {
                        var avgWage = item.total_quantity > 0 ? parseFloat(item.total_wage / item.total_quantity).toFixed(2) : '0.00';
                        html += '<tr>';
                        html += '<td>' + item.user.nickname + '</td>';
                        html += '<td>' + item.total_quantity + '</td>';
                        html += '<td>¥' + parseFloat(item.total_wage).toFixed(2) + '</td>';
                        html += '<td>' + item.report_count + '</td>';
                        html += '<td>¥' + avgWage + '</td>';
                        html += '<td>';
                        html += '<a href="scanwork/wage/index?user_id=' + item.user_id + '" class="btn btn-xs btn-info">查看明细</a>';
                        html += '</td>';
                        html += '</tr>';
                    });
                } else {
                    html = '<tr><td colspan="6" class="text-center">暂无数据</td></tr>';
                }
                $('#summary-table').html(html);
            }

            // 加载图表数据
            function loadChartData() {
                var params = $('#search-form').serialize();
                console.log('Loading chart data with params:', params);
                
                $.get('scanwork/wage/chart?' + params, function(data) {
                    console.log('Chart data received:', data);
                    if (data.code === 1) {
                        updateChart(data.data);
                    } else {
                        console.error('加载图表数据失败:', data.msg);
                        // 显示错误信息
                        $('.panel-body').each(function() {
                            var $this = $(this);
                            if ($this.find('[id$="-chart"]').length > 0) {
                                $this.find('[id$="-chart"]').html('<div class="text-center text-muted" style="padding: 50px;">暂无数据</div>');
                            }
                        });
                    }
                }).fail(function(xhr, status, error) {
                    console.error('请求失败:', error);
                    console.error('Response:', xhr.responseText);
                    // 显示错误信息
                    $('.panel-body').each(function() {
                        var $this = $(this);
                        if ($this.find('[id$="-chart"]').length > 0) {
                            $this.find('[id$="-chart"]').html('<div class="text-center text-danger" style="padding: 50px;">加载失败</div>');
                        }
                    });
                });
            }

            // 更新图表
            function updateChart(chartData) {
                var chartDom = document.getElementById('wage-chart');
                if (chartDom && typeof echarts !== 'undefined') {
                    var myChart = echarts.init(chartDom);
                    var option = {
                        title: {
                            text: '工资趋势图',
                            left: 'center'
                        },
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {
                                type: 'cross'
                            }
                        },
                        legend: {
                            data: ['工资金额', '报工数量'],
                            top: 30
                        },
                        xAxis: {
                            type: 'category',
                            data: chartData.dates || []
                        },
                        yAxis: [
                            {
                                type: 'value',
                                name: '工资金额 (元)',
                                position: 'left'
                            },
                            {
                                type: 'value',
                                name: '报工数量',
                                position: 'right'
                            }
                        ],
                        series: [
                            {
                                name: '工资金额',
                                type: 'bar',
                                data: chartData.wages || [],
                                itemStyle: { color: '#28a745' }
                            },
                            {
                                name: '报工数量',
                                type: 'line',
                                yAxisIndex: 1,
                                data: chartData.quantities || [],
                                itemStyle: { color: '#667eea' }
                            }
                        ]
                    };
                    myChart.setOption(option);
                }
            }

            // 页面加载时自动查询
            loadSummaryData();
            loadChartData();
        },
        chart: function () {
            console.log('Chart method called');
            console.log('ECharts available:', typeof echarts !== 'undefined');
            
            // 加载员工列表
            $.get('user/user/index', function(data) {
                console.log('User data loaded:', data);
                if (data.rows) {
                    var html = '<option value="">全部员工</option>';
                    data.rows.forEach(function(user) {
                        html += '<option value="' + user.id + '">' + user.nickname + '</option>';
                    });
                    $('#user_id').html(html);
                }
            }).fail(function(xhr, status, error) {
                console.error('Failed to load user data:', error);
            });

            // 查询按钮
            $('#search-btn').click(function() {
                loadChartData();
            });

            // 加载图表数据
            function loadChartData() {
                var params = $('#search-form').serialize();
                $.get('scanwork/wage/chart?' + params, function(data) {
                    if (data.code === 1) {
                        updateWageTrendChart(data.data);
                        updateQuantityTrendChart(data.data);
                        updateUserWageChart(data.data);
                        updateProcessWageChart(data.data);
                    } else {
                        console.error('加载图表数据失败:', data.msg);
                        // 显示错误信息
                        $('.panel-body').each(function() {
                            var $this = $(this);
                            if ($this.find('[id$="-chart"]').length > 0) {
                                $this.find('[id$="-chart"]').html('<div class="text-center text-muted" style="padding: 50px;">暂无数据</div>');
                            }
                        });
                    }
                }).fail(function(xhr, status, error) {
                    console.error('请求失败:', error);
                    // 显示错误信息
                    $('.panel-body').each(function() {
                        var $this = $(this);
                        if ($this.find('[id$="-chart"]').length > 0) {
                            $this.find('[id$="-chart"]').html('<div class="text-center text-danger" style="padding: 50px;">加载失败</div>');
                        }
                    });
                });
            }

            // 更新工资趋势图
            function updateWageTrendChart(chartData) {
                var chartDom = document.getElementById('wage-trend-chart');
                if (chartDom && typeof echarts !== 'undefined') {
                    var myChart = echarts.init(chartDom);
                    var option = {
                        title: {
                            text: '工资趋势',
                            left: 'center',
                            textStyle: { fontSize: 14 }
                        },
                        tooltip: {
                            trigger: 'axis'
                        },
                        xAxis: {
                            type: 'category',
                            data: chartData.dates || []
                        },
                        yAxis: {
                            type: 'value',
                            name: '工资金额 (元)'
                        },
                        series: [{
                            name: '日工资',
                            type: 'line',
                            data: chartData.wages || [],
                            smooth: true,
                            itemStyle: { color: '#28a745' },
                            areaStyle: {
                                color: {
                                    type: 'linear',
                                    x: 0, y: 0, x2: 0, y2: 1,
                                    colorStops: [
                                        { offset: 0, color: 'rgba(40, 167, 69, 0.3)' },
                                        { offset: 1, color: 'rgba(40, 167, 69, 0.1)' }
                                    ]
                                }
                            }
                        }]
                    };
                    myChart.setOption(option);
                }
            }

            // 更新报工数量趋势图
            function updateQuantityTrendChart(chartData) {
                var chartDom = document.getElementById('quantity-trend-chart');
                if (chartDom && typeof echarts !== 'undefined') {
                    var myChart = echarts.init(chartDom);
                    var option = {
                        title: {
                            text: '报工数量趋势',
                            left: 'center',
                            textStyle: { fontSize: 14 }
                        },
                        tooltip: {
                            trigger: 'axis'
                        },
                        xAxis: {
                            type: 'category',
                            data: chartData.dates || []
                        },
                        yAxis: {
                            type: 'value',
                            name: '报工数量'
                        },
                        series: [{
                            name: '日报工数量',
                            type: 'bar',
                            data: chartData.quantities || [],
                            itemStyle: { color: '#667eea' }
                        }]
                    };
                    myChart.setOption(option);
                }
            }

            // 更新员工工资对比图
            function updateUserWageChart(chartData) {
                var chartDom = document.getElementById('user-wage-chart');
                if (chartDom && typeof echarts !== 'undefined') {
                    var myChart = echarts.init(chartDom);
                    var option = {
                        title: {
                            text: '员工工资对比',
                            left: 'center',
                            textStyle: { fontSize: 14 }
                        },
                        tooltip: {
                            trigger: 'item',
                            formatter: '{a} <br/>{b}: {c} ({d}%)'
                        },
                        series: [{
                            name: '员工工资',
                            type: 'pie',
                            radius: '50%',
                            data: chartData.users || [
                                { value: 0, name: '暂无数据' }
                            ],
                            emphasis: {
                                itemStyle: {
                                    shadowBlur: 10,
                                    shadowOffsetX: 0,
                                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                                }
                            }
                        }]
                    };
                    myChart.setOption(option);
                }
            }

            // 更新工序工资分布图
            function updateProcessWageChart(chartData) {
                var chartDom = document.getElementById('process-wage-chart');
                if (chartDom && typeof echarts !== 'undefined') {
                    var myChart = echarts.init(chartDom);
                    var option = {
                        title: {
                            text: '工序工资分布',
                            left: 'center',
                            textStyle: { fontSize: 14 }
                        },
                        tooltip: {
                            trigger: 'axis',
                            axisPointer: {
                                type: 'shadow'
                            }
                        },
                        xAxis: {
                            type: 'category',
                            data: chartData.processes || ['暂无数据'],
                            axisLabel: {
                                rotate: 45
                            }
                        },
                        yAxis: {
                            type: 'value',
                            name: '工资金额 (元)'
                        },
                        series: [{
                            name: '工序工资',
                            type: 'bar',
                            data: chartData.processWages || [0],
                            itemStyle: { color: '#ffc107' }
                        }]
                    };
                    myChart.setOption(option);
                }
            }

            // 页面加载时自动查询
            loadChartData();
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
            }
        }
    };
    return Controller;
}); 