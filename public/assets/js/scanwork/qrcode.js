/**
 * 二维码管理模块JS
 */

var ScanWorkQrcode = {
    // 初始化
    init: function() {
        this.bindEvents();
        this.initTable();
    },
    
    // 绑定事件
    bindEvents: function() {
        var self = this;
        
        // 生成二维码
        $(document).on('click', '.btn-generate-qr', function() {
            var id = $(this).data('id');
            self.generateQrCode(id);
        });
        
        // 批量生成二维码
        $('#batch-generate-btn').on('click', function() {
            self.batchGenerateQrCodes();
        });
        
        // 下载二维码
        $(document).on('click', '.btn-download-qr', function() {
            var id = $(this).data('id');
            self.downloadQrCode(id);
        });
        
        // 打印二维码
        $(document).on('click', '.btn-print-qr', function() {
            var id = $(this).data('id');
            self.printQrCode(id);
        });
        
        // 预览二维码
        $(document).on('click', '.btn-preview-qr', function() {
            var id = $(this).data('id');
            self.previewQrCode(id);
        });
    },
    
    // 初始化表格
    initTable: function() {
        var self = this;
        
        window.QrcodeTable = $("#qrcode-table").bootstrapTable({
            url: 'scanwork/qrcode/index',
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
                    {field: 'quantity', title: __('分配数量'), align: 'center'},
                    {field: 'reported_quantity', title: __('已报数量'), align: 'center'},
                    {field: 'remaining_quantity', title: __('待报数量'), align: 'center'},
                    {field: 'status', title: __('状态'), searchList: {"0":__('进行中'),"1":__('已完成')}, formatter: Table.api.formatter.status},
                    {field: 'createtime', title: __('分配时间'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange', sortable: true},
                    {field: 'operate', title: __('操作'), table: Table, events: Table.api.events.operate, formatter: function(value, row, index) {
                        var buttons = [];
                        buttons.push({
                            name: 'generate',
                            text: __('生成二维码'),
                            title: __('生成任务二维码'),
                            classname: 'btn btn-xs btn-success',
                            icon: 'fa fa-qrcode',
                            url: 'scanwork/qrcode/generate',
                            callback: function(data) {
                                self.showQrPreview(data.data);
                            }
                        });
                        return Table.api.formatter.operate.call(this, value, row, index, buttons);
                    }}
                ]
            ]
        });
    },
    
    // 生成二维码
    generateQrCode: function(allocationId) {
        ScanWorkUtils.showLoading('生成二维码中...');
        
        ScanWorkUtils.ajax('scanwork/qrcode/generate', {allocation_id: allocationId}, 'POST', function(response) {
            ScanWorkUtils.hideLoading();
            ScanWorkQrcode.showQrPreview(response.data);
        }, function() {
            ScanWorkUtils.hideLoading();
        });
    },
    
    // 批量生成二维码
    batchGenerateQrCodes: function() {
        var selections = QrcodeTable.bootstrapTable('getSelections');
        if (selections.length === 0) {
            ScanWorkUtils.showMessage('warning', '请选择要生成二维码的任务');
            return;
        }
        
        var allocationIds = [];
        selections.forEach(function(item) {
            allocationIds.push(item.id);
        });
        
        var btn = $('#batch-generate-btn');
        var originalText = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i> 生成中...').prop('disabled', true);
        
        ScanWorkUtils.ajax('scanwork/qrcode/batchGenerate', {allocation_ids: allocationIds}, 'POST', function(response) {
            ScanWorkQrcode.showBatchResults(response.data);
            btn.html(originalText).prop('disabled', false);
        }, function() {
            btn.html(originalText).prop('disabled', false);
        });
    },
    
    // 显示二维码预览
    showQrPreview: function(data) {
        $('#qr-image').attr('src', data.qr_image);
        $('#task-info').html(`
            <strong>订单号：</strong>${data.allocation.order.order_no}<br>
            <strong>产品：</strong>${data.allocation.model.product.name} - ${data.allocation.model.name}<br>
            <strong>工序：</strong>${data.allocation.process.name}<br>
            <strong>员工：</strong>${data.allocation.user.nickname}<br>
            <strong>分配数量：</strong>${data.allocation.quantity}<br>
            <strong>待报数量：</strong>${data.allocation.remaining_quantity}
        `);
        
        // 设置下载和打印按钮的事件
        $('#download-qr-btn').off('click').on('click', function() {
            ScanWorkQrcode.downloadQrCode(data.allocation.id);
        });
        
        $('#print-qr-btn').off('click').on('click', function() {
            ScanWorkQrcode.printQrCode(data.allocation.id);
        });
        
        $('#qr-preview-modal').modal('show');
    },
    
    // 显示批量生成结果
    showBatchResults: function(qrCodes) {
        var html = '<div class="row">';
        qrCodes.forEach(function(item) {
            html += `
                <div class="col-md-4 text-center" style="margin-bottom: 20px;">
                    <img src="${item.qr_image}" alt="二维码" style="max-width: 100px; border: 1px solid #ddd;">
                    <div class="mt-2">
                        <small>${item.allocation.order.order_no}<br>${item.allocation.model.product.name} - ${item.allocation.model.name}<br>${item.allocation.process.name}</small>
                    </div>
                    <div class="mt-2">
                        <button class="btn btn-xs btn-success" onclick="ScanWorkQrcode.downloadQrCode(${item.allocation.id})">
                            <i class="fa fa-download"></i> 下载
                        </button>
                        <button class="btn btn-xs btn-primary" onclick="ScanWorkQrcode.printQrCode(${item.allocation.id})">
                            <i class="fa fa-print"></i> 打印
                        </button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        
        $('#batch-qr-results').html(html).show();
    },
    
    // 下载二维码
    downloadQrCode: function(allocationId) {
        var url = 'scanwork/qrcode/download/' + allocationId;
        window.open(url);
    },
    
    // 打印二维码
    printQrCode: function(allocationId) {
        var url = 'scanwork/qrcode/print/' + allocationId;
        window.open(url, '_blank');
    },
    
    // 预览二维码
    previewQrCode: function(allocationId) {
        ScanWorkUtils.ajax('scanwork/qrcode/generate', {allocation_id: allocationId}, 'POST', function(response) {
            ScanWorkQrcode.showQrPreview(response.data);
        });
    },
    
    // 获取二维码统计
    getQrStats: function() {
        ScanWorkUtils.ajax('scanwork/qrcode/stats', {}, 'GET', function(response) {
            var data = response.data;
            
            // 更新统计信息
            $('#total-allocations').text(data.total);
            $('#active-allocations').text(data.active);
            $('#completed-allocations').text(data.completed);
            
            // 更新进度条
            var progress = data.total > 0 ? (data.completed / data.total * 100) : 0;
            $('#completion-progress').css('width', progress + '%').text(progress.toFixed(1) + '%');
        });
    },
    
    // 刷新数据
    refreshData: function() {
        QrcodeTable.bootstrapTable('refresh');
        this.getQrStats();
    }
};

// 页面加载完成后初始化
$(document).ready(function() {
    ScanWorkQrcode.init();
}); 