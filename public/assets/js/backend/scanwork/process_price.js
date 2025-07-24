define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'scanwork/process_price/index' + location.search,
                    add_url: 'scanwork/process_price/add',
                    edit_url: 'scanwork/process_price/edit',
                    del_url: 'scanwork/process_price/del',
                    multi_url: 'scanwork/process_price/multi',
                    table: 'process_price',
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
                        {field: 'model.name', title: __('型号名称'), align: 'left', formatter: function(value, row, index) {
                            var displayName = value;
                            if (row.model && row.model.model_code) {
                                displayName += ' (' + row.model.model_code + ')';
                            }
                            return displayName;
                        }},
                        {field: 'process.name', title: __('工序名称'), align: 'left'},
                        {field: 'price', title: __('工价(元/件)'), align: 'right', formatter: function(value, row, index) {
                            return parseFloat(value).toFixed(2);
                        }},
                        {field: 'time_price', title: __('工时单价(元/小时)'), align: 'right', formatter: function(value, row, index) {
                            return parseFloat(value).toFixed(2);
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
            
            // 初始化批量设置功能
            Controller.api.initBatchPrice();
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
                $('#batch-set-btn').on('click', function() {
                    var modelId = $('#model_id').val();
                    if (!modelId) {
                        Toastr.error('请选择型号');
                        return;
                    }
                    
                    var prices = {};
                    $('.process-price').each(function() {
                        var processId = $(this).data('process-id');
                        var price = $(this).val();
                        if (price && price > 0) {
                            prices[processId] = price;
                        }
                    });
                    
                    if (Object.keys(prices).length === 0) {
                        Toastr.error('请至少设置一个工序的工价');
                        return;
                    }
                    
                    $.post('scanwork/processprice/batchSet', {
                        model_id: modelId,
                        prices: prices
                    }, function(data) {
                        if (data.code === 1) {
                            Toastr.success(data.msg);
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        } else {
                            Toastr.error(data.msg);
                        }
                    });
                });
            },
            
            // 初始化批量设置功能
            initBatchPrice: function() {
                // 批量设置工价表单提交
                $('#batch-form').on('submit', function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();
                    $.post('scanwork/process_price/batch', formData, function(data) {
                        if (data.code === 1) {
                            Toastr.success(data.msg);
                            $('#batch-modal').modal('hide');
                            $("#table").bootstrapTable('refresh');
                        } else {
                            Toastr.error(data.msg);
                        }
                    });
                });
            }
        }
    };
    return Controller;
}); 