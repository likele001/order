/**
 * 报工管理模块JS
 */

var ScanWorkReport = {
    // 初始化
    init: function() {
        this.bindEvents();
        this.initTable();
        this.initCharts();
    },
    
    // 绑定事件
    bindEvents: function() {
        var self = this;
        
        // 确认报工
        $(document).on('click', '.btn-confirm-report', function() {
            var id = $(this).data('id');
            self.confirmReport(id);
        });
        
        // 删除报工
        $(document).on('click', '.btn-delete-report', function() {
            var id = $(this).data('id');
            self.deleteReport(id);
        });
        
        // 查看详情
        $(document).on('click', '.btn-view-report', function() {
            var id = $(this).data('id');
            self.viewReportDetail(id);
        });
        
        // 导出数据
        $('#export-btn').on('click', function() {
            self.exportData();
        });
        
        // 日期范围变化
        $('.daterange').on('apply.daterangepicker', function(ev, picker) {
            self.refreshData();
        });
        
        // 员工选择变化
        $('#user-filter').on('change', function() {
            self.refreshData();
        });

        // 确认报工
        $('#confirm-reports').on('click', function() {
            var ids = ReportTable.bootstrapTable('getSelections').map(function(row) {
                return row.id;
            });
            
            if (ids.length === 0) {
                Toastr.error('请选择要确认的报工记录');
                return;
            }
            
            ScanWorkUtils.ajax('scanwork/report/confirm', {ids: ids}, 'POST', function(response) {
                $('#confirm-modal').modal('hide');
                ReportTable.bootstrapTable('refresh');
                Toastr.success(response.msg);
            });
        });
        
        // 拒绝报工
        $('#reject-reports').on('click', function() {
            var ids = ReportTable.bootstrapTable('getSelections').map(function(row) {
                return row.id;
            });
            
            if (ids.length === 0) {
                Toastr.error('请选择要拒绝的报工记录');
                return;
            }
            
            var reason = $('#reject-reason').val();
            
            ScanWorkUtils.ajax('scanwork/report/reject', {ids: ids, reason: reason}, 'POST', function(response) {
                $('#reject-modal').modal('hide');
                $('#reject-reason').val('');
                ReportTable.bootstrapTable('refresh');
                Toastr.success(response.msg);
            });
        });
    },
    
    // 初始化表格
    initTable: function() {
        var self = this;
        
        window.ReportTable = $("#report-table").bootstrapTable({
            url: 'scanwork/report/index',
            pk: 'id',
            sortName: 'id',
            columns: [
                [
                    {checkbox: true},
                    {field: 'id', title: __('Id'), sortable: true},
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
                    {field: 'user.nickname', title: __('员工'), align: 'left', formatter: function(value, row, index) {
                        return value || '-';
                    }},
                    {field: 'quantity', title: __('报工数量'), align: 'center'},
                    {field: 'wage', title: __('计件工资'), align: 'center', formatter: function(value, row, index) {
                        return '¥' + ScanWorkUtils.formatNumber(value, 2);
                    }},
                    {field: 'status', title: __('状态'), searchList: {"0":__('待审核'),"1":__('已确认'),"2":__('已拒绝')}, formatter: function(value, row, index) {
                        var statusMap = {
                            0: {text: '待审核', class: 'label-warning'},
                            1: {text: '已确认', class: 'label-success'},
                            2: {text: '已拒绝', class: 'label-danger'}
                        };
                        var status = statusMap[value] || {text: '未知', class: 'label-default'};
                        return '<span class="label ' + status.class + '">' + status.text + '</span>';
                    }},
                    {field: 'createtime', title: __('报工时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                    {field: 'operate', title: __('操作'), table: Table, events: Table.api.events.operate, formatter: function(value, row, index) {
                        var buttons = [];
                        buttons.push({
                            name: 'view',
                            text: __('查看'),
                            title: __('查看报工详情'),
                            classname: 'btn btn-xs btn-info',
                            icon: 'fa fa-eye',
                            url: 'scanwork/report/detail',
                            callback: function(data) {
                                self.showDetailModal(data.data);
                            }
                        });
                        
                        if (row.status == 0) {
                            buttons.push({
                                name: 'confirm',
                                text: __('确认'),
                                title: __('确认报工'),
                                classname: 'btn btn-xs btn-success',
                                icon: 'fa fa-check',
                                url: 'scanwork/report/confirm',
                                callback: function(data) {
                                    ReportTable.bootstrapTable('refresh');
                                }
                            });
                            
                            buttons.push({
                                name: 'reject',
                                text: __('拒绝'),
                                title: __('拒绝报工'),
                                classname: 'btn btn-xs btn-danger',
                                icon: 'fa fa-times',
                                url: 'scanwork/report/reject',
                                callback: function(data) {
                                    ReportTable.bootstrapTable('refresh');
                                }
                            });
                        }
                        
                        buttons.push({
                            name: 'delete',
                            text: __('删除'),
                            title: __('删除报工'),
                            classname: 'btn btn-xs btn-danger',
                            icon: 'fa fa-trash',
                            url: 'scanwork/report/del',
                            callback: function(data) {
                                ReportTable.bootstrapTable('refresh');
                            }
                        });
                        
                        return Table.api.formatter.operate.call(this, value, row, index, buttons);
                    }}
                ]
            ]
        });
    },
    
    // 初始化图表
    initCharts: function() {
        this.loadReportStats();
        this.loadWageStats();
        this.loadDailyReport();
    },
    
    // 加载报工统计
    loadReportStats: function() {
        var self = this;
        ScanWorkUtils.ajax('scanwork/report/stats', {}, 'GET', function(response) {
            var data = response.data;
            
            // 更新统计卡片
            $('#total-reports').text(data.total_reports);
            $('#total-quantity').text(data.total_quantity);
            $('#total-wage').text('¥' + ScanWorkUtils.formatNumber(data.total_wage, 2));
            $('#avg-wage').text('¥' + ScanWorkUtils.formatNumber(data.avg_wage, 2));
            
            // 更新状态分布图表
            self.updateStatusChart(data.status_distribution);
        });
    },
    
    // 加载工资统计
    loadWageStats: function() {
        var self = this;
        ScanWorkUtils.ajax('scanwork/report/wageStats', {}, 'GET', function(response) {
            var data = response.data;
            self.updateWageChart(data);
        });
    },
    
    // 加载日报工趋势
    loadDailyReport: function() {
        var self = this;
        ScanWorkUtils.ajax('scanwork/report/dailyReport', {}, 'GET', function(response) {
            var data = response.data;
            self.updateDailyChart(data);
        });
    },
    
    // 更新状态分布图表
    updateStatusChart: function(data) {
        var chartDom = document.getElementById('status-chart');
        if (!chartDom) return;
        
        var myChart = echarts.init(chartDom);
        var option = {
            title: {
                text: '报工状态分布',
                left: 'center'
            },
            tooltip: {
                trigger: 'item',
                formatter: '{a} <br/>{b}: {c} ({d}%)'
            },
            legend: {
                orient: 'vertical',
                left: 'left'
            },
            series: [
                {
                    name: '报工状态',
                    type: 'pie',
                    radius: '50%',
                    data: [
                        {value: data.pending || 0, name: '待确认'},
                        {value: data.confirmed || 0, name: '已确认'}
                    ],
                    emphasis: {
                        itemStyle: {
                            shadowBlur: 10,
                            shadowOffsetX: 0,
                            shadowColor: 'rgba(0, 0, 0, 0.5)'
                        }
                    }
                }
            ]
        };
        myChart.setOption(option);
    },
    
    // 更新工资统计图表
    updateWageChart: function(data) {
        var chartDom = document.getElementById('wage-chart');
        if (!chartDom) return;
        
        var myChart = echarts.init(chartDom);
        var option = {
            title: {
                text: '员工工资统计',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            xAxis: {
                type: 'category',
                data: data.users || []
            },
            yAxis: {
                type: 'value',
                name: '工资 (元)'
            },
            series: [
                {
                    name: '工资',
                    type: 'bar',
                    data: data.wages || [],
                    itemStyle: {
                        color: '#667eea'
                    }
                }
            ]
        };
        myChart.setOption(option);
    },
    
    // 更新日报工趋势图表
    updateDailyChart: function(data) {
        var chartDom = document.getElementById('daily-chart');
        if (!chartDom) return;
        
        var myChart = echarts.init(chartDom);
        var option = {
            title: {
                text: '日报工趋势',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis'
            },
            legend: {
                data: ['报工数量', '报工金额'],
                top: 30
            },
            xAxis: {
                type: 'category',
                data: data.dates || []
            },
            yAxis: [
                {
                    type: 'value',
                    name: '数量',
                    position: 'left'
                },
                {
                    type: 'value',
                    name: '金额 (元)',
                    position: 'right'
                }
            ],
            series: [
                {
                    name: '报工数量',
                    type: 'line',
                    data: data.quantities || [],
                    yAxisIndex: 0
                },
                {
                    name: '报工金额',
                    type: 'line',
                    data: data.wages || [],
                    yAxisIndex: 1
                }
            ]
        };
        myChart.setOption(option);
    },
    
    // 确认报工
    confirmReport: function(id) {
        if (confirm('确定要确认这个报工记录吗？')) {
            ScanWorkUtils.ajax('scanwork/report/confirm', {id: id}, 'POST', function(response) {
                ScanWorkUtils.showMessage('success', response.msg);
                ReportTable.bootstrapTable('refresh');
                ScanWorkReport.loadReportStats();
            });
        }
    },
    
    // 删除报工
    deleteReport: function(id) {
        if (confirm('确定要删除这个报工记录吗？')) {
            ScanWorkUtils.ajax('scanwork/report/del', {ids: id}, 'POST', function(response) {
                ScanWorkUtils.showMessage('success', response.msg);
                ReportTable.bootstrapTable('refresh');
                ScanWorkReport.loadReportStats();
            });
        }
    },
    
    // 查看报工详情
    viewReportDetail: function(id) {
        ScanWorkUtils.ajax('scanwork/report/detail', {id: id}, 'GET', function(response) {
            ScanWorkReport.showDetailModal(response.data);
        });
    },
    
    // 显示详情模态框
    showDetailModal: function(data) {
        $('#detail-modal').modal('show');
        
        // 填充详情数据
        $('#detail-order-no').text(data.allocation.order.order_no);
        $('#detail-product-name').text(data.allocation.model.product.name + ' - ' + data.allocation.model.name);
        $('#detail-process-name').text(data.allocation.process.name);
        $('#detail-user-name').text(data.allocation.user.nickname);
        $('#detail-quantity').text(data.quantity);
        $('#detail-wage').text('¥' + ScanWorkUtils.formatNumber(data.wage, 2));
        $('#detail-status').text(ScanWorkConfig.reportStatusMap[data.status]);
        $('#detail-createtime').text(ScanWorkUtils.formatDate(data.createtime, ScanWorkConfig.datetimeFormat));
        $('#detail-remark').text(data.remark || '无');
    },
    
    // 导出数据
    exportData: function() {
        var params = {};
        
        // 获取筛选条件
        var dateRange = $('.daterange').val();
        if (dateRange) {
            var dates = dateRange.split(' - ');
            params.start_date = dates[0];
            params.end_date = dates[1];
        }
        
        var userId = $('#user-filter').val();
        if (userId) {
            params.user_id = userId;
        }
        
        // 构建导出URL
        var url = 'scanwork/report/export?' + $.param(params);
        window.open(url);
    },
    
    // 刷新数据
    refreshData: function() {
        ReportTable.bootstrapTable('refresh');
        this.loadReportStats();
        this.loadWageStats();
        this.loadDailyReport();
    }
};

// 页面加载完成后初始化
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        ScanWorkReport.init();
    });
} else {
    // jQuery未加载，等待加载
    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                ScanWorkReport.init();
            });
        } else {
            console.error('jQuery not loaded, ScanWorkReport cannot initialize');
        }
    });
} 