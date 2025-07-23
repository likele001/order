define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'scanwork/productmodel/index' + location.search,
                    add_url: 'scanwork/productmodel/add',
                    edit_url: 'scanwork/productmodel/edit',
                    del_url: 'scanwork/productmodel/del',
                    multi_url: 'scanwork/productmodel/multi',
                    table: 'productmodel',
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
                        {field: 'product.name', title: __('所属产品'), align: 'left'},
                        {field: 'name', title: __('型号名称'), align: 'left'},
                        {field: 'model_code', title: __('型号编号'), align: 'left'},
                        {field: 'description', title: __('型号描述'), align: 'left'},
                        {field: 'status', title: __('状态'), searchList: {"1":__('正常'),"0":__('禁用')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('创建时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'updatetime', title: __('更新时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
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
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
}); 