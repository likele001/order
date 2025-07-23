define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'scanwork/processprice/index' + location.search,
                    add_url: 'scanwork/processprice/add',
                    edit_url: 'scanwork/processprice/edit',
                    del_url: 'scanwork/processprice/del',
                    multi_url: 'scanwork/processprice/multi',
                    table: 'processprice',
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
                        {field: 'model.product.name', title: __('产品名称'), align: 'left'},
                        {field: 'model.name', title: __('型号名称'), align: 'left'},
                        {field: 'process.name', title: __('工序名称'), align: 'left'},
                        {field: 'price', title: __('工价'), align: 'center', formatter: function(value, row, index) {
                            return '¥' + parseFloat(value).toFixed(2);
                        }},
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
        batch: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                
                // 批量设置工价的事件处理
                $('#batch-form').on('submit', function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();
                    $.post('scanwork/processprice/batch', formData, function(data) {
                        if (data.code === 1) {
                            Toastr.success(data.msg);
                            $('#batch-modal').modal('hide');
                            // 刷新表格
                            $("#table").bootstrapTable('refresh');
                        } else {
                            Toastr.error(data.msg);
                        }
                    });
                });
                
                // 型号选择变化时加载已有工价
                $('#batch-model-id').on('change', function() {
                    var modelId = $(this).val();
                    if (modelId) {
                        $.get('scanwork/processprice/getModelPrices', {model_id: modelId}, function(data) {
                            if (data.code === 1) {
                                // 清空所有输入框
                                $('.process-price').val('');
                                // 填充已有工价
                                $.each(data.data, function(processId, price) {
                                    $('.process-price[data-process-id="' + processId + '"]').val(price);
                                });
                            }
                        });
                    } else {
                        $('.process-price').val('');
                    }
                });
            }
        }
    };
    return Controller;
}); 