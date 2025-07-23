define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'scanwork/allocation/index' + location.search,
                    add_url: 'scanwork/allocation/add',
                    edit_url: 'scanwork/allocation/edit',
                    del_url: 'scanwork/allocation/del',
                    multi_url: 'scanwork/allocation/multi',
                    table: 'allocation',
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
                        {field: 'order.order_no', title: __('订单号'), align: 'left'},
                        {field: 'model.product.name', title: __('产品名称'), align: 'left'},
                        {field: 'model.name', title: __('型号名称'), align: 'left', formatter: function(value, row, index) {
                            var displayName = value;
                            if (row.model && row.model.model_code) {
                                displayName += ' (' + row.model.model_code + ')';
                            }
                            return displayName;
                        }},
                        {field: 'process.name', title: __('工序名称'), align: 'left'},
                        {field: 'user.nickname', title: __('员工'), align: 'left', formatter: function(value, row, index) {
                            return value || row.user ? (row.user.nickname || row.user.username || '-') : '-';
                        }},
                        {field: 'quantity', title: __('分配数量'), align: 'center'},
                        {field: 'reported_quantity', title: __('已报数量'), align: 'center', formatter: function(value, row, index) {
                            // 计算已报数量
                            var reported = 0;
                            if (row.reports && row.reports.length > 0) {
                                row.reports.forEach(function(report) {
                                    if (report.status == 1) {
                                        reported += parseInt(report.quantity) || 0;
                                    }
                                });
                            }
                            return reported;
                        }},
                        {field: 'remaining_quantity', title: __('待报数量'), align: 'center', formatter: function(value, row, index) {
                            var reported = 0;
                            if (row.reports && row.reports.length > 0) {
                                row.reports.forEach(function(report) {
                                    if (report.status == 1) {
                                        reported += parseInt(report.quantity) || 0;
                                    }
                                });
                            }
                            var remaining = Math.max(0, (parseInt(row.quantity) || 0) - reported);
                            return remaining;
                        }},
                        {field: 'progress', title: __('进度'), align: 'center', formatter: function(value, row, index) {
                            var reported = 0;
                            if (row.reports && row.reports.length > 0) {
                                row.reports.forEach(function(report) {
                                    if (report.status == 1) {
                                        reported += parseInt(report.quantity) || 0;
                                    }
                                });
                            }
                            var quantity = parseInt(row.quantity) || 0;
                            if (quantity > 0) {
                                var progress = Math.round((reported / quantity) * 100);
                                return progress + '%';
                            }
                            return '0%';
                        }},
                        {field: 'status', title: __('状态'), searchList: {"0":__('进行中'),"1":__('已完成')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('分配时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                        {field: 'operate', title: __('操作'), table: table, events: {
                            'click .btn-generate-qrcode': function (e, value, row, index) {
                                Fast.api.ajax({
                                    url: 'scanwork/qrcode/generate',
                                    data: {allocationId: row.id},
                                    success: function (data) {
                                        Layer.open({
                                            type: 1,
                                            title: '二维码预览',
                                            area: ['320px', '400px'],
                                            content: '<div style="text-align:center;padding:20px;"><img src="' + data.qr_image + '" style="width:256px;height:256px;"><p style="margin-top:10px;word-break:break-all;">' + data.qr_content + '</p></div>'
                                        });
                                    }
                                });
                                return false;
                            }
                        }, formatter: function (value, row, index) {
                            console.log('row.id:', row.id, row); // 调试用
                            var html = '';
                            html += '<a href="javascript:;" class="btn btn-xs btn-success btn-generate-qrcode" data-id="' + row.id + '"><i class="fa fa-qrcode"></i> 生成二维码</a> ';
                            html += '<a href="' + Fast.api.fixurl('scanwork/qrcode/download', {allocationId: row.id}) + '" class="btn btn-xs btn-info" target="_blank"><i class="fa fa-download"></i> 下载二维码</a> ';
                            html += '<a href="' + Fast.api.fixurl('scanwork/qrcode/print', {allocationId: row.id}) + '" class="btn btn-xs btn-warning" target="_blank"><i class="fa fa-print"></i> 打印标签</a> ';
                            html += Table.api.formatter.operate.call(this, value, row, index);
                            return html;
                        }}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
            
            // 批量分配功能
            Controller.api.initBatchAllocation();
        },
        add: function () {
            Controller.api.bindevent();
            Controller.api.initAddForm();
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
                
                // 批量分配的事件处理
                $('#batch-allocate-btn').on('click', function() {
                    var orderId = $('#order_id').val();
                    if (!orderId) {
                        Toastr.error('请选择订单');
                        return;
                    }
                    
                    var allocations = [];
                    $('.allocation-row').each(function() {
                        var modelId = $(this).find('[name="model_id[]"]').val();
                        var processId = $(this).find('[name="process_id[]"]').val();
                        var userId = $(this).find('[name="user_id[]"]').val();
                        var quantity = $(this).find('[name="quantity[]"]').val();
                        
                        if (modelId && processId && userId && quantity) {
                            allocations.push({
                                model_id: modelId,
                                process_id: processId,
                                user_id: userId,
                                quantity: quantity
                            });
                        }
                    });
                    
                    if (allocations.length === 0) {
                        Toastr.error('请至少添加一个分配项');
                        return;
                    }
                    
                    $.post('scanwork/allocation/batchAdd', {
                        order_id: orderId,
                        allocations: allocations
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
                
                // 动态添加分配行
                $('#add-allocation-btn').on('click', function() {
                    var rowHtml = `
                        <div class="allocation-row">
                            <div class="col-md-3">
                                <select name="model_id[]" class="form-control" required>
                                    <option value="">请选择型号</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="process_id[]" class="form-control" required>
                                    <option value="">请选择工序</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="user_id[]" class="form-control" required>
                                    <option value="">请选择员工</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="quantity[]" class="form-control" placeholder="数量" min="1" required>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-danger remove-allocation-btn">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    `;
                    $('#allocation-container').append(rowHtml);
                });
                
                // 删除分配行
                $(document).on('click', '.remove-allocation-btn', function() {
                    $(this).closest('.allocation-row').remove();
                });
            },
            
            // 初始化批量分配功能
            initBatchAllocation: function() {
                // 获取订单列表
                $.get('scanwork/order/index', function(data) {
                    var orderSelect = $('#batch-order-select');
                    if (data.rows) {
                        data.rows.forEach(function(order) {
                            orderSelect.append('<option value="' + order.id + '">' + order.order_no + ' - ' + order.customer_name + '</option>');
                        });
                    }
                });

                // 订单选择变化时加载型号
                $('#batch-order-select').change(function() {
                    var orderId = $(this).val();
                    if (orderId) {
                        $.get('scanwork/allocation/getOrderModels', {order_id: orderId}, function(data) {
                            if (data.code === 1) {
                                Controller.api.generateBatchTable(data.data);
                                $('#batch-content').show();
                            }
                        });
                    } else {
                        $('#batch-content').hide();
                    }
                });

                // 批量分配表单提交
                $('#batch-form').on('submit', function(e) {
                    e.preventDefault();
                    var formData = $(this).serialize();
                    $.post('scanwork/allocation/batch', formData, function(data) {
                        if (data.code === 1) {
                            Toastr.success(data.msg);
                            $('#batch-modal').modal('hide');
                            $("#table").bootstrapTable('refresh');
                        } else {
                            Toastr.error(data.msg);
                        }
                    });
                });
            },
            
            // 生成批量分配表格
            generateBatchTable: function(orderModels) {
                var html = '<table class="table table-bordered">';
                html += '<thead><tr><th>型号</th><th>工序</th><th>员工</th><th>分配数量</th></tr></thead>';
                html += '<tbody>';
                
                orderModels.forEach(function(orderModel) {
                    html += '<tr>';
                    var modelDisplayName = orderModel.model.product.name + ' - ' + orderModel.model.name;
                    if (orderModel.model.model_code) {
                        modelDisplayName += ' (' + orderModel.model.model_code + ')';
                    }
                    html += '<td>' + modelDisplayName + '</td>';
                    html += '<td>';
                    html += '<select name="allocations[' + orderModel.model_id + '][process_id]" class="form-control" required>';
                    html += '<option value="">请选择工序</option>';
                    // 这里需要从后端获取工序列表
                    html += '</select>';
                    html += '</td>';
                    html += '<td>';
                    html += '<select name="allocations[' + orderModel.model_id + '][user_id]" class="form-control" required>';
                    html += '<option value="">请选择员工</option>';
                    // 这里需要从后端获取员工列表
                    html += '</select>';
                    html += '</td>';
                    html += '<td>';
                    html += '<input type="number" name="allocations[' + orderModel.model_id + '][quantity]" class="form-control" min="1" max="' + orderModel.quantity + '" value="' + orderModel.quantity + '" required>';
                    html += '</td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                $('#batch-table-container').html(html);
            },

            // 初始化添加表单
            initAddForm: function() {
                // 订单选择变化时加载型号
                $('#order-select').change(function() {
                    var orderId = $(this).val();
                    if (orderId) {
                        $.get('scanwork/allocation/getOrderModels', {order_id: orderId}, function(data) {
                            if (data.code === 1) {
                                var modelSelect = $('#model-select');
                                modelSelect.empty().append('<option value="">请选择型号</option>');
                                
                                data.data.forEach(function(orderModel) {
                                    var modelDisplayName = orderModel.model.product.name + ' - ' + orderModel.model.name;
                                    if (orderModel.model.model_code) {
                                        modelDisplayName += ' (' + orderModel.model.model_code + ')';
                                    }
                                    modelDisplayName += ' (剩余可分配: ' + orderModel.remaining_quantity + '/' + orderModel.quantity + ')';
                                    
                                    modelSelect.append('<option value="' + orderModel.model_id + '" data-quantity="' + orderModel.remaining_quantity + '">' + modelDisplayName + '</option>');
                                });
                                
                                // 检查selectpicker是否存在，如果存在则刷新
                                if (typeof modelSelect.selectpicker === 'function') {
                                    modelSelect.selectpicker('refresh');
                                }
                            }
                        });
                    } else {
                        $('#model-select').empty().append('<option value="">请先选择订单</option>');
                        // 检查selectpicker是否存在，如果存在则刷新
                        if (typeof $('#model-select').selectpicker === 'function') {
                            $('#model-select').selectpicker('refresh');
                        }
                        $('#max-quantity').text('0');
                        $('#c-quantity').attr('max', 0);
                    }
                });
                
                // 型号选择变化时更新最大可分配数量
                $('#model-select').change(function() {
                    var modelId = $(this).val();
                    if (modelId) {
                        // 更新最大可分配数量
                        var selectedOption = $(this).find('option:selected');
                        var maxQuantity = selectedOption.data('quantity') || 0;
                        $('#max-quantity').text(maxQuantity);
                        $('#c-quantity').attr('max', maxQuantity);
                    } else {
                        $('#max-quantity').text('0');
                        $('#c-quantity').attr('max', 0);
                    }
                });
            }
        }
    };
    return Controller;
}); 