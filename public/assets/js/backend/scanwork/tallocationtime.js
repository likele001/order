define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'scanwork/tallocationtime/index' + location.search,
                    add_url: 'scanwork/tallocationtime/add',
                    edit_url: 'scanwork/tallocationtime/edit',
                    del_url: 'scanwork/tallocationtime/del',
                    multi_url: 'scanwork/tallocationtime/multi',
                    table: 'tallocationtime',
                }
            });
            var table = $("#table");
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id'), sortable: true},
                        {field: 'order.order_no', title: __('订单号'), align: 'left'},
                        {field: 'model.product.name', title: __('产品名称'), align: 'left'},
                        {field: 'model.name', title: __('型号名称'), align: 'left'},
                        {field: 'process.name', title: __('工序名称'), align: 'left'},
                        {field: 'user.nickname', title: __('员工'), align: 'left'},
                        {field: 'total_hours', title: __('工时'), align: 'center'},
                        {field: 'status', title: __('状态'), searchList: {"0":__('进行中'),"1":__('已完成')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('分配时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });
            Table.api.bindevent(table);
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
            // 订单-型号级联
            $('#order-select').on('change', function(){
                var orderId = $(this).val();
                var $model = $('#model-select');
                $model.empty();
                if(orderId){
                    $.get('scanwork/tallocationtime/getModelList', {order_id: orderId}, function(res){
                        var list = res.rows || res;
                        $model.append('<option value="">请选择型号</option>');
                        $.each(list, function(i, row){
                            $model.append('<option value="'+row.id+'">'+row.name+'</option>');
                        });
                        $model.selectpicker('refresh');
                    });
                }else{
                    $model.append('<option value="">请先选择订单</option>');
                    $model.selectpicker('refresh');
                }
            });
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
            // 订单-型号级联
            $('#order-select').on('change', function(){
                var orderId = $(this).val();
                var $model = $('#model-select');
                $model.empty();
                if(orderId){
                    $.get('scanwork/tallocationtime/getModelList', {order_id: orderId}, function(res){
                        var list = res.rows || res;
                        $model.append('<option value="">请选择型号</option>');
                        $.each(list, function(i, row){
                            $model.append('<option value="'+row.id+'">'+row.name+'</option>');
                        });
                        $model.selectpicker('refresh');
                    });
                }else{
                    $model.append('<option value="">请先选择订单</option>');
                    $model.selectpicker('refresh');
                }
            });
        },
        batch: function () {
            Form.api.bindevent($("form[role=form]"));
        }
    };
    return Controller;
}); 