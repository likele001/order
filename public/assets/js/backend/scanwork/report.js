define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'scanwork/report/index' + location.search,
                    add_url: 'scanwork/report/add',
                    edit_url: 'scanwork/report/edit',
                    del_url: 'scanwork/report/del',
                    multi_url: 'scanwork/report/multi',
                    table: 'report',
                }
            });
            var table = $("#table");
            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'allocation.order.order_no', title: __('订单号'), align: 'left'},
                        {field: 'allocation.model.product.name', title: __('产品名称'), align: 'left'},
                        {field: 'allocation.model.name', title: __('型号名称'), align: 'left'},
                        {field: 'allocation.process.name', title: __('工序名称'), align: 'left'},
                        {field: 'user.nickname', title: __('员工'), align: 'left'},
                        {field: 'quantity', title: __('报工数量'), align: 'center'},
                        {field: 'wage', title: __('计件工资'), align: 'center', formatter: function(value){ return '¥' + (parseFloat(value) || 0).toFixed(2); }},
                        {field: 'status', title: __('状态'), searchList: {"0":__('待审核'),"1":__('已确认'),"2":__('已拒绝')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('报工时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('操作'), table: table, events: {
                            'click .btn-audit': function (e, value, row, index) {
                                e.stopPropagation();
                                var id = row.id;
                                Layer.confirm('确定要审核通过该记录吗？', function(index){
                                    $.ajax({
                                        url: 'scanwork/report/audit',
                                        type: 'POST',
                                        data: {ids: id, status: 1},
                                        success: function(res){
                                            Layer.close(index);
                                            if(res.code === 1){
                                                Toastr.success('审核成功');
                                                table.bootstrapTable('refresh');
                                            }else{
                                                Toastr.error(res.msg || '审核失败');
                                            }
                                        },
                                        error: function(){
                                            Layer.close(index);
                                            Toastr.error('请求失败');
                                        }
                                    });
                                });
                            },
                            'click .btn-reject': function (e, value, row, index) {
                                e.stopPropagation();
                                var id = row.id;
                                Layer.prompt({title: '请输入拒绝原因', formType: 2}, function(reason, layerIndex){
                                    if(!reason){
                                        Toastr.error('请填写拒绝原因');
                                        return;
                                    }
                                    $.ajax({
                                        url: 'scanwork/report/audit',
                                        type: 'POST',
                                        data: {ids: id, status: 2, reason: reason},
                                        success: function(res){
                                            Layer.close(layerIndex);
                                            if(res.code === 1){
                                                Toastr.success('已拒绝');
                                                table.bootstrapTable('refresh');
                                            }else{
                                                Toastr.error(res.msg || '操作失败');
                                            }
                                        },
                                        error: function(){
                                            Layer.close(layerIndex);
                                            Toastr.error('请求失败');
                                        }
                                    });
                                });
                            }
                        }, formatter: function (value, row, index) {
                         var buttons = [];
                            // 审核按钮，仅待审核状态显示
                            if(row.status == 0){
                                buttons.push('<a href="javascript:;" class="btn btn-xs btn-success btn-audit" title="审核"><i class="fa fa-check"></i> 审核</a> ');
                                buttons.push('<a href="javascript:;" class="btn btn-xs btn-danger btn-reject" title="拒绝"><i class="fa fa-close"></i> 拒绝</a> ');
                            }
                            // 详情按钮
                            buttons.push('<a href="view/ids/' + row.id + '" class="btn btn-xs btn-info btn-detail" title="详情"><i class="fa fa-list"></i> 详情</a> ');
                            // 保留原有操作按钮
                            buttons.push(Table.api.formatter.operate.call(this, value, row, index));
                            return buttons.join(' ');
                        }}
                    ]
                ]
            });
            // 添加批量审核和批量拒绝按钮
            var toolbar = $('.toolbar');
            if (toolbar.length === 0) {
                toolbar = $('<div class="toolbar" style="margin-bottom:10px;"></div>').prependTo(table.closest('.fixed-table-container').length ? table.closest('.fixed-table-container') : table.parent());
            }
            toolbar.html('<button type="button" class="btn btn-success btn-batch-audit"><i class="fa fa-check"></i> 批量审核</button> <button type="button" class="btn btn-danger btn-batch-reject"><i class="fa fa-close"></i> 批量拒绝</button>');
            // 批量审核
            toolbar.on('click', '.btn-batch-audit', function(){
                var ids = table.bootstrapTable('getSelections').map(function(row){return row.id;});
                if(ids.length === 0){ Toastr.error('请先选择要审核的数据'); return; }
                Layer.confirm('确定要批量审核通过选中的记录吗？', function(index){
                    $.ajax({
                        url: 'scanwork/report/audit',
                        type: 'POST',
                        data: {ids: ids.join(','), status: 1},
                        success: function(res){
                            Layer.close(index);
                            if(res.code === 1){
                                Toastr.success('批量审核成功');
                                table.bootstrapTable('refresh');
                            }else{
                                Toastr.error(res.msg || '操作失败');
                            }
                        },
                        error: function(){
                            Layer.close(index);
                            Toastr.error('请求失败');
                        }
                    });
                });
            });
            // 批量拒绝
            toolbar.on('click', '.btn-batch-reject', function(){
                var ids = table.bootstrapTable('getSelections').map(function(row){return row.id;});
                if(ids.length === 0){ Toastr.error('请先选择要拒绝的数据'); return; }
                Layer.prompt({title: '请输入拒绝原因', formType: 2}, function(reason, layerIndex){
                    if(!reason){ Toastr.error('请填写拒绝原因'); return; }
                    $.ajax({
                        url: 'scanwork/report/audit',
                        type: 'POST',
                        data: {ids: ids.join(','), status: 2, reason: reason},
                        success: function(res){
                            Layer.close(layerIndex);
                            if(res.code === 1){
                                Toastr.success('批量拒绝成功');
                                table.bootstrapTable('refresh');
                            }else{
                                Toastr.error(res.msg || '操作失败');
                            }
                        },
                        error: function(){
                            Layer.close(layerIndex);
                            Toastr.error('请求失败');
                        }
                    });
                });
            });
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        dailyReport: function () {
            $.getJSON('/lsj5492li.php/scanwork/report/getDailyReport', function(res){
                if(res.code === 1 && res.data){
                    var reportDailyData = res.data;
                    var $tbody = $('#table-report-daily tbody');
                    $tbody.empty();
                    reportDailyData.forEach(function(row){
                        $tbody.append('<tr><td>'+row.date+'</td><td>'+row.user+'</td><td>'+row.quantity+'</td><td>'+row.wage+'</td></tr>');
                    });
                    var chart = echarts.init(document.getElementById('echarts-report-daily'));
                    chart.setOption({
                        title: {text: '报工日报趋势'},
                        tooltip: {},
                        xAxis: {type:'category',data: reportDailyData.map(r=>r.date)},
                        yAxis: {},
                        series: [
                            {name:'报工数',type:'line',data:reportDailyData.map(r=>r.quantity)},
                            {name:'总工资',type:'line',data:reportDailyData.map(r=>r.wage)}
                        ]
                    });
                }
            });
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
}); 