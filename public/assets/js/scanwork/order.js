/**
 * 订单管理模块JS
 */

var ScanWorkOrder = {
    // 初始化
    init: function() {
        this.bindEvents();
        this.initTable();
    },
    
    // 绑定事件
    bindEvents: function() {
        var self = this;
        
        // 添加订单
        $(document).on('click', '.btn-add-order', function() {
            self.showAddModal();
        });
        
        // 编辑订单
        $(document).on('click', '.btn-edit-order', function() {
            var id = $(this).data('id');
            self.showEditModal(id);
        });
        
        // 删除订单
        $(document).on('click', '.btn-delete-order', function() {
            var id = $(this).data('id');
            self.deleteOrder(id);
        });
        
        // 查看详情
        $(document).on('click', '.btn-view-order', function() {
            var id = $(this).data('id');
            self.viewOrderDetail(id);
        });
        
        // 表单提交
        $('#order-form').on('submit', function(e) {
            e.preventDefault();
            self.submitOrder();
        });
        
        // 动态添加型号
        $('#add-model-btn').on('click', function() {
            self.addModelRow();
        });
        
        // 删除型号行
        $(document).on('click', '.remove-model-btn', function() {
            $(this).closest('.model-row').remove();
        });
    },
    
    // 初始化表格
    initTable: function() {
        var self = this;
        
        window.OrderTable = $("#order-table").bootstrapTable({
            url: 'scanwork/order/index',
            pk: 'id',
            sortName: 'id',
            columns: [
                [
                    {checkbox: true},
                    {field: 'id', title: __('Id'), sortable: true},
                    {field: 'order_no', title: __('订单号'), align: 'left'},
                    {field: 'customer_name', title: __('客户名称'), align: 'left'},
                    {field: 'total_quantity', title: __('总数量'), align: 'center'},
                    {field: 'progress', title: __('进度'), align: 'center', formatter: function(value, row, index) {
                        return value + '%';
                    }},
                    {field: 'status', title: __('状态'), searchList: {"0":__('待生产'),"1":__('生产中'),"2":__('已完成')}, formatter: Table.api.formatter.status},
                    {field: 'createtime', title: __('创建时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                    {field: 'operate', title: __('操作'), table: Table, events: Table.api.events.operate, formatter: function(value, row, index) {
                        var buttons = [];
                        buttons.push({
                            name: 'view',
                            text: __('查看'),
                            title: __('查看订单详情'),
                            classname: 'btn btn-xs btn-info',
                            icon: 'fa fa-eye',
                            url: 'scanwork/order/detail',
                            callback: function(data) {
                                self.showDetailModal(data.data);
                            }
                        });
                        buttons.push({
                            name: 'edit',
                            text: __('编辑'),
                            title: __('编辑订单'),
                            classname: 'btn btn-xs btn-success',
                            icon: 'fa fa-pencil',
                            url: 'scanwork/order/edit',
                            callback: function(data) {
                                self.showEditModal(data.data);
                            }
                        });
                        buttons.push({
                            name: 'delete',
                            text: __('删除'),
                            title: __('删除订单'),
                            classname: 'btn btn-xs btn-danger',
                            icon: 'fa fa-trash',
                            url: 'scanwork/order/del',
                            callback: function(data) {
                                OrderTable.bootstrapTable('refresh');
                            }
                        });
                        return Table.api.formatter.operate.call(this, value, row, index, buttons);
                    }}
                ]
            ]
        });
    },
    
    // 显示添加模态框
    showAddModal: function() {
        $('#order-modal').modal('show');
        $('#order-form')[0].reset();
        $('#order-modal .modal-title').text('添加订单');
        $('#order-form').data('action', 'add');
        
        // 清空型号行
        $('.model-row').not(':first').remove();
    },
    
    // 显示编辑模态框
    showEditModal: function(data) {
        $('#order-modal').modal('show');
        $('#order-modal .modal-title').text('编辑订单');
        $('#order-form').data('action', 'edit');
        
        // 填充表单数据
        $('#order-form [name="id"]').val(data.id);
        $('#order-form [name="order_no"]').val(data.order_no);
        $('#order-form [name="customer_name"]').val(data.customer_name);
        $('#order-form [name="customer_phone"]').val(data.customer_phone);
        $('#order-form [name="customer_address"]').val(data.customer_address);
        $('#order-form [name="remark"]').val(data.remark);
        
        // 填充型号数据
        this.fillModelRows(data.order_models);
    },
    
    // 显示详情模态框
    showDetailModal: function(data) {
        $('#detail-modal').modal('show');
        
        // 填充详情数据
        $('#detail-order-no').text(data.order_no);
        $('#detail-customer-name').text(data.customer_name);
        $('#detail-customer-phone').text(data.customer_phone);
        $('#detail-customer-address').text(data.customer_address);
        $('#detail-total-quantity').text(data.total_quantity);
        $('#detail-progress').text(data.progress + '%');
        $('#detail-status').text(ScanWorkConfig.statusMap[data.status]);
        $('#detail-createtime').text(ScanWorkUtils.formatDate(data.createtime, ScanWorkConfig.datetimeFormat));
        $('#detail-remark').text(data.remark || '无');
        
        // 填充型号列表
        this.fillModelList(data.order_models);
        
        // 填充分配列表
        this.fillAllocationList(data.allocations);
    },
    
    // 填充型号行
    fillModelRows: function(orderModels) {
        // 清空现有行
        $('.model-row').not(':first').remove();
        
        if (orderModels && orderModels.length > 0) {
            orderModels.forEach(function(model, index) {
                if (index > 0) {
                    ScanWorkOrder.addModelRow();
                }
                
                var row = $('.model-row').eq(index);
                row.find('[name="model_id[]"]').val(model.model_id);
                row.find('[name="quantity[]"]').val(model.quantity);
            });
        }
    },
    
    // 填充型号列表
    fillModelList: function(orderModels) {
        var html = '';
        if (orderModels && orderModels.length > 0) {
            orderModels.forEach(function(model) {
                html += `
                    <tr>
                        <td>${model.model.product.name}</td>
                        <td>${model.model.name}</td>
                        <td>${model.quantity}</td>
                        <td>${model.progress}%</td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="4" class="text-center">暂无型号数据</td></tr>';
        }
        $('#detail-model-list').html(html);
    },
    
    // 填充分配列表
    fillAllocationList: function(allocations) {
        var html = '';
        if (allocations && allocations.length > 0) {
            allocations.forEach(function(allocation) {
                html += `
                    <tr>
                        <td>${allocation.model.product.name} - ${allocation.model.name}</td>
                        <td>${allocation.process.name}</td>
                        <td>${allocation.user.nickname}</td>
                        <td>${allocation.quantity}</td>
                        <td>${allocation.reported_quantity}</td>
                        <td>${allocation.remaining_quantity}</td>
                        <td>${allocation.progress}%</td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="7" class="text-center">暂无分配数据</td></tr>';
        }
        $('#detail-allocation-list').html(html);
    },
    
    // 添加型号行
    addModelRow: function() {
        var rowHtml = `
            <div class="model-row">
                <div class="col-md-5">
                    <select name="model_id[]" class="form-control" required>
                        <option value="">请选择型号</option>
                    </select>
                </div>
                <div class="col-md-5">
                    <input type="number" name="quantity[]" class="form-control" placeholder="数量" min="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-model-btn">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        
        $('#model-container').append(rowHtml);
        this.loadModelOptions();
    },
    
    // 加载型号选项
    loadModelOptions: function() {
        ScanWorkUtils.ajax('scanwork/model/getModelOptions', {}, 'GET', function(response) {
            var options = '<option value="">请选择型号</option>';
            response.data.forEach(function(model) {
                options += `<option value="${model.id}">${model.product_name} - ${model.name}</option>`;
            });
            
            $('[name="model_id[]"]').html(options);
        });
    },
    
    // 提交订单
    submitOrder: function() {
        var formData = new FormData($('#order-form')[0]);
        var action = $('#order-form').data('action');
        
        // 验证表单
        var validation = this.validateOrderForm(formData);
        if (!validation.valid) {
            ScanWorkUtils.showMessage('error', Object.values(validation.errors)[0]);
            return;
        }
        
        // 收集型号数据
        var models = [];
        $('.model-row').each(function() {
            var modelId = $(this).find('[name="model_id[]"]').val();
            var quantity = $(this).find('[name="quantity[]"]').val();
            
            if (modelId && quantity) {
                models.push({
                    model_id: modelId,
                    quantity: quantity
                });
            }
        });
        
        if (models.length === 0) {
            ScanWorkUtils.showMessage('error', '请至少添加一个型号');
            return;
        }
        
        // 添加型号数据到表单
        formData.append('models', JSON.stringify(models));
        
        var url = action === 'add' ? 'scanwork/order/add' : 'scanwork/order/edit';
        
        ScanWorkUtils.showLoading('提交中...');
        
        $.ajax({
            url: url,
            data: formData,
            type: 'POST',
            processData: false,
            contentType: false,
            success: function(response) {
                ScanWorkUtils.hideLoading();
                if (response.code === 1) {
                    ScanWorkUtils.showMessage('success', response.msg);
                    $('#order-modal').modal('hide');
                    OrderTable.bootstrapTable('refresh');
                } else {
                    ScanWorkUtils.showMessage('error', response.msg);
                }
            },
            error: function() {
                ScanWorkUtils.hideLoading();
                ScanWorkUtils.showMessage('error', '网络错误，请重试');
            }
        });
    },
    
    // 删除订单
    deleteOrder: function(id) {
        if (confirm('确定要删除这个订单吗？')) {
            ScanWorkUtils.ajax('scanwork/order/del', {ids: id}, 'POST', function(response) {
                ScanWorkUtils.showMessage('success', response.msg);
                OrderTable.bootstrapTable('refresh');
            });
        }
    },
    
    // 验证订单表单
    validateOrderForm: function(formData) {
        var rules = {
            order_no: {
                required: true,
                message: '订单号不能为空'
            },
            customer_name: {
                required: true,
                message: '客户名称不能为空'
            }
        };
        
        return ScanWorkUtils.validateForm(Object.fromEntries(formData), rules);
    }
};

// 页面加载完成后初始化
$(document).ready(function() {
    ScanWorkOrder.init();
}); 