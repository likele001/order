define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {
    
    console.log('Order.js loaded successfully!');

    var Controller = {
        index: function () {
            console.log('Index function called');
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'scanwork/order/index' + location.search,
                    add_url: 'scanwork/order/add',
                    edit_url: 'scanwork/order/edit',
                    del_url: 'scanwork/order/del',
                    multi_url: 'scanwork/order/multi',
                    table: 'order',
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
                        {field: 'order_no', title: __('订单号'), align: 'left'},
                        {field: 'customer_name', title: __('客户名称'), align: 'left'},
                        {field: 'customer_phone', title: __('客户电话'), align: 'left'},
                        {field: 'total_quantity', title: __('总数量'), align: 'center'},
                        {field: 'progress', title: __('进度'), align: 'center', formatter: function(value, row, index) {
                            if (value === undefined || value === null) {
                                return '0%';
                            }
                            return value + '%';
                        }},
                        {field: 'status', title: __('状态'), searchList: {"0":__('待生产'),"1":__('生产中'),"2":__('已完成')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('创建时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'updatetime', title: __('更新时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: function(value, row, index) {
                            var table = this.table;
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            var buttons = [];
                            buttons.push({
                                name: 'detail',
                                text: __('详情'),
                                title: __('查看订单详情'),
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa fa-eye',
                                url: 'scanwork/order/detail/ids/{ids}',
                                callback: function (data) {
                                    table.bootstrapTable('refresh');
                                }
                            });
                            buttons.push({
                                name: 'allocation',
                                text: __('分工'),
                                title: __('分配生产任务'),
                                classname: 'btn btn-xs btn-success btn-dialog',
                                icon: 'fa fa-tasks',
                                url: 'scanwork/allocation/batch?order_id={ids}',
                                callback: function (data) {
                                    table.bootstrapTable('refresh');
                                }
                            });
                            return Table.api.formatter.operate.call(this, value, row, index, buttons);
                        }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            console.log('Add function called');
            Controller.api.bindevent();
        },
        edit: function () {
            console.log('Edit function called');
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                console.log('Bindevent function called');
                
                // 绑定FastAdmin的表单事件
                Form.api.bindevent($("form[role=form]"));
                
                // 动态添加型号行 - 使用简单直接的方式
                $('#add-model-row').click(function() {
                    console.log('Add model row clicked');
                    var newRow = $('.model-row:first').clone();
                    newRow.find('select').val('');
                    newRow.find('input').val('1');
                    $('#model-table tbody').append(newRow);
                });
                
                // 删除型号行
                $(document).on('click', '.remove-row', function() {
                    console.log('Remove row clicked');
                    if ($('.model-row').length > 1) {
                        $(this).closest('.model-row').remove();
                    } else {
                        Toastr.error('至少需要保留一个型号');
                    }
                });
                
                // 表单提交前处理
                $('form[role=form]').on('submit', function(e) {
                    console.log('Form submit triggered');
                    
                    // 构建型号数据
                    var models = {};
                    $('.model-row').each(function() {
                        var modelId = $(this).find('.model-select').val();
                        var quantity = parseInt($(this).find('.quantity-input').val()) || 0;
                        if (modelId && quantity > 0) {
                            if (models[modelId]) {
                                models[modelId] += quantity; // 累加相同型号的数量
                            } else {
                                models[modelId] = quantity;
                            }
                        }
                    });
                    
                    console.log('Models data:', models);
                    
                    // 移除可能存在的旧隐藏字段
                    $('form[role=form] input[name="models"]').remove();
                    
                    // 添加隐藏字段
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'models',
                        value: JSON.stringify(models)
                    }).appendTo('form[role=form]');
                    
                    console.log('Hidden field added with value:', JSON.stringify(models));
                });
            }
        }
    };
    return Controller;
}); 