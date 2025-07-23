define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'scanwork/qrcode/index' + location.search,
                    add_url: 'scanwork/qrcode/add',
                    edit_url: 'scanwork/qrcode/edit',
                    del_url: 'scanwork/qrcode/del',
                    multi_url: 'scanwork/qrcode/multi',
                    table: 'qrcode',
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
                        {field: 'allocation.user.nickname', title: __('员工'), align: 'left'},
                        {field: 'quantity', title: __('任务数量'), align: 'center'},
                        {field: 'scan_count', title: __('扫码次数'), align: 'center'},
                        {field: 'status', title: __('状态'), searchList: {"0":__('未使用'),"1":__('已使用')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('生成时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('操作'), table: table, events: Table.api.events.operate, formatter: function(value, row, index) {
                            var table = this.table;
                            var options = table ? table.bootstrapTable('getOptions') : {};
                            var buttons = [];
                            buttons.push({
                                name: 'preview',
                                text: __('预览'),
                                title: __('预览二维码'),
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa fa-eye',
                                url: 'scanwork/qrcode/preview/ids/{ids}',
                                callback: function (data) {
                                    table.bootstrapTable('refresh');
                                }
                            });
                            buttons.push({
                                name: 'download',
                                text: __('下载'),
                                title: __('下载二维码'),
                                classname: 'btn btn-xs btn-success btn-ajax',
                                icon: 'fa fa-download',
                                url: 'scanwork/qrcode/download/ids/{ids}',
                                callback: function (data) {
                                    if (data.code === 1) {
                                        window.open(data.url);
                                    }
                                }
                            });
                            buttons.push({
                                name: 'print',
                                text: __('打印'),
                                title: __('打印标签'),
                                classname: 'btn btn-xs btn-warning btn-dialog',
                                icon: 'fa fa-print',
                                url: 'scanwork/qrcode/print/ids/{ids}',
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
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        batchGenerate: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
                
                // 批量生成二维码的事件处理
                $('#batch-generate-btn').on('click', function() {
                    var orderId = $('#order_id').val();
                    if (!orderId) {
                        Toastr.error('请选择订单');
                        return;
                    }
                    
                    var allocations = [];
                    $('.allocation-checkbox:checked').each(function() {
                        allocations.push($(this).val());
                    });
                    
                    if (allocations.length === 0) {
                        Toastr.error('请至少选择一个任务');
                        return;
                    }
                    
                    $.post('scanwork/qrcode/batchGenerate', {
                        order_id: orderId,
                        allocation_ids: allocations
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
                
                // 全选/取消全选
                $('#select-all').on('change', function() {
                    $('.allocation-checkbox').prop('checked', $(this).prop('checked'));
                });
                
                // 单个选择框变化时检查全选状态
                $(document).on('change', '.allocation-checkbox', function() {
                    var total = $('.allocation-checkbox').length;
                    var checked = $('.allocation-checkbox:checked').length;
                    $('#select-all').prop('checked', total === checked);
                });
            }
        }
    };
    return Controller;
}); 