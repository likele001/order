define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    var Controller = {
        index: function () {
            Table.api.init({
                extend: {
                    index_url: 'scanwork/treporttime/index' + location.search,
                    add_url: 'scanwork/treporttime/add',
                    edit_url: 'scanwork/treporttime/edit',
                    del_url: 'scanwork/treporttime/del',
                    multi_url: 'scanwork/treporttime/multi',
                    table: 'treporttime',
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
                        {field: 'tallocationtime.order.order_no', title: __('订单号'), align: 'left'},
                        {field: 'tallocationtime.model.product.name', title: __('产品名称'), align: 'left'},
                        {field: 'tallocationtime.model.name', title: __('型号名称'), align: 'left'},
                        {field: 'tallocationtime.process.name', title: __('工序名称'), align: 'left'},
                        {field: 'user.nickname', title: __('员工'), align: 'left'},
                        {field: 'total_hours', title: __('工时'), align: 'center'},
                        {field: 'wage', title: __('工资'), align: 'center'},
                        {field: 'status', title: __('状态'), searchList: {"0":__('待确认'),"1":__('已确认')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('报工时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: function(value, row, index) {
                            var html = [];
                            if (row.status == 0) {
                                html.push('<a href="javascript:;" class="btn btn-success btn-xs btn-confirm" data-id="'+row.id+'">审核通过</a>');
                                html.push('<a href="javascript:;" class="btn btn-danger btn-xs btn-reject" data-id="'+row.id+'">拒绝</a>');
                            }
                            return html.join(' ');
                        }}
                    ]
                ]
            });
            Table.api.bindevent(table);
        },
        add: function () {
            Form.api.bindevent($("form[role=form]"));
        },
        edit: function () {
            Form.api.bindevent($("form[role=form]"));
        }
    };
    $(document).on('click', '.btn-confirm', function(){
        var id = $(this).data('id');
        Layer.confirm('确认审核通过？', function(){
            $.post('scanwork/treporttime/confirm', {ids: id}, function(res){
                Layer.msg(res.msg);
                Table.api.refresh();
            });
        });
    });
    $(document).on('click', '.btn-reject', function(){
        var id = $(this).data('id');
        Layer.confirm('确认拒绝？', function(){
            $.post('/admin/scanwork/treporttime/reject', {ids: id}, function(res){
                Layer.msg(res.msg);
                Table.api.refresh();
            });
        });
    });
    return Controller;
});