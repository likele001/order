/**
 * 员工端报工模块JS
 */

var ScanWorkWorker = {
    // 初始化
    init: function() {
        this.bindEvents();
        this.loadStats();
        this.initCharts();
    },
    
    // 绑定事件
    bindEvents: function() {
        var self = this;
        
        // 报工提交
        $(document).on('click', '.btn-submit-report', function() {
            var allocationId = $(this).data('id');
            var quantity = $(this).closest('.task-item').find('.report-quantity').val();
            self.submitReport(allocationId, quantity);
        });
        
        // 扫码报工
        $('#scan-btn').on('click', function() {
            window.location.href = 'scan';
        });
        
        // 查看详情
        $(document).on('click', '.btn-view-task', function() {
            var id = $(this).data('id');
            self.viewTaskDetail(id);
        });
        
        // 查看报工记录
        $(document).on('click', '.btn-view-records', function() {
            var id = $(this).data('id');
            self.viewReportRecords(id);
        });
        
        // 数量输入验证
        $(document).on('input', '.report-quantity', function() {
            var maxQuantity = parseInt($(this).attr('max'));
            var value = parseInt($(this).val());
            
            if (value > maxQuantity) {
                $(this).val(maxQuantity);
                ScanWorkUtils.showMessage('warning', '报工数量不能超过待报数量');
            }
        });
        
        // 刷新数据
        $('#refresh-btn').on('click', function() {
            self.refreshData();
        });
    },
    
    // 加载统计数据
    loadStats: function() {
        ScanWorkUtils.ajax('stats', {}, 'GET', function(response) {
            var data = response.data;
            
            // 更新统计卡片
            $('#total-tasks').text(data.total_tasks);
            $('#completed-tasks').text(data.completed_tasks);
            $('#total-quantity').text(data.total_quantity);
            $('#total-wage').text('¥' + ScanWorkUtils.formatNumber(data.total_wage, 2));
            
            // 更新进度条
            var progress = data.total_tasks > 0 ? (data.completed_tasks / data.total_tasks * 100) : 0;
            $('#task-progress').css('width', progress + '%').text(progress.toFixed(1) + '%');
        });
    },
    
    // 初始化图表
    initCharts: function() {
        this.loadWageChart();
        this.loadDailyReport();
    },
    
    // 加载工资图表
    loadWageChart: function() {
        ScanWorkUtils.ajax('wageChart', {}, 'GET', function(response) {
            ScanWorkWorker.updateWageChart(response.data);
        });
    },
    
    // 加载日报工图表
    loadDailyReport: function() {
        ScanWorkUtils.ajax('dailyReport', {}, 'GET', function(response) {
            ScanWorkWorker.updateDailyChart(response.data);
        });
    },
    
    // 更新工资图表
    updateWageChart: function(data) {
        var chartDom = document.getElementById('wage-chart');
        if (!chartDom) return;
        
        var myChart = echarts.init(chartDom);
        var option = {
            title: {
                text: '我的工资统计',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            xAxis: {
                type: 'category',
                data: data.dates || []
            },
            yAxis: {
                type: 'value',
                name: '工资 (元)'
            },
            series: [
                {
                    name: '日工资',
                    type: 'bar',
                    data: data.wages || [],
                    itemStyle: {
                        color: '#28a745'
                    }
                }
            ]
        };
        myChart.setOption(option);
    },
    
    // 更新日报工图表
    updateDailyChart: function(data) {
        var chartDom = document.getElementById('daily-chart');
        if (!chartDom) return;
        
        var myChart = echarts.init(chartDom);
        var option = {
            title: {
                text: '日报工趋势',
                left: 'center'
            },
            tooltip: {
                trigger: 'axis'
            },
            xAxis: {
                type: 'category',
                data: data.dates || []
            },
            yAxis: {
                type: 'value',
                name: '数量'
            },
            series: [
                {
                    name: '报工数量',
                    type: 'line',
                    data: data.quantities || [],
                    smooth: true,
                    itemStyle: {
                        color: '#667eea'
                    }
                }
            ]
        };
        myChart.setOption(option);
    },
    
    // 提交报工
    submitReport: function(allocationId, quantity) {
        if (!quantity || quantity <= 0) {
            ScanWorkUtils.showMessage('warning', '请输入有效的报工数量');
            return;
        }
        
        var btn = $('.btn-submit-report[data-id="' + allocationId + '"]');
        var originalText = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i> 提交中...').prop('disabled', true);
        
        ScanWorkUtils.ajax('submit', {
            allocation_id: allocationId,
            quantity: quantity
        }, 'POST', function(response) {
            ScanWorkUtils.showMessage('success', '报工成功！');
            btn.html(originalText).prop('disabled', false);
            
            // 刷新页面数据
            setTimeout(function() {
                window.location.reload();
            }, 1500);
        }, function() {
            btn.html(originalText).prop('disabled', false);
        });
    },
    
    // 查看任务详情
    viewTaskDetail: function(id) {
        ScanWorkUtils.ajax('taskDetail', {id: id}, 'GET', function(response) {
            ScanWorkWorker.showTaskDetailModal(response.data);
        });
    },
    
    // 查看报工记录
    viewReportRecords: function(id) {
        ScanWorkUtils.ajax('reportRecords', {allocation_id: id}, 'GET', function(response) {
            ScanWorkWorker.showRecordsModal(response.data);
        });
    },
    
    // 显示任务详情模态框
    showTaskDetailModal: function(data) {
        $('#task-detail-modal').modal('show');
        
        // 填充详情数据
        $('#detail-order-no').text(data.order.order_no);
        $('#detail-product-name').text(data.model.product.name + ' - ' + data.model.name);
        $('#detail-process-name').text(data.process.name);
        $('#detail-quantity').text(data.quantity);
        $('#detail-reported-quantity').text(data.reported_quantity);
        $('#detail-remaining-quantity').text(data.remaining_quantity);
        $('#detail-progress').text(data.progress + '%');
        $('#detail-status').text(data.status == 0 ? '进行中' : '已完成');
        $('#detail-createtime').text(ScanWorkUtils.formatDate(data.createtime, ScanWorkConfig.datetimeFormat));
        
        // 更新进度条
        $('#detail-progress-bar').css('width', data.progress + '%');
    },
    
    // 显示报工记录模态框
    showRecordsModal: function(data) {
        $('#records-modal').modal('show');
        
        var html = '';
        if (data && data.length > 0) {
            data.forEach(function(record) {
                html += `
                    <tr>
                        <td>${ScanWorkUtils.formatDate(record.createtime, ScanWorkConfig.datetimeFormat)}</td>
                        <td>${record.quantity}</td>
                        <td>¥${ScanWorkUtils.formatNumber(record.wage, 2)}</td>
                        <td>${record.status == 0 ? '待确认' : '已确认'}</td>
                        <td>${record.remark || '无'}</td>
                    </tr>
                `;
            });
        } else {
            html = '<tr><td colspan="5" class="text-center">暂无报工记录</td></tr>';
        }
        
        $('#records-list').html(html);
    },
    
    // 刷新数据
    refreshData: function() {
        this.loadStats();
        this.loadWageChart();
        this.loadDailyReport();
        
        // 刷新任务列表
        if (typeof TaskTable !== 'undefined') {
            TaskTable.bootstrapTable('refresh');
        }
    },
    
    // 扫码功能
    initScan: function() {
        var self = this;
        var currentMethod = 'camera';
        var stream = null;
        
        // 扫码方式切换
        $('.scan-method').click(function() {
            var method = $(this).data('method');
            self.switchScanMethod(method);
        });
        
        // 开启摄像头
        $('#start-camera').click(function() {
            self.startCamera();
        });
        
        // 关闭摄像头
        $('#stop-camera').click(function() {
            self.stopCamera();
        });
        
        // 手动提交
        $('#manual-submit').click(function() {
            var input = $('#manual-input').val().trim();
            if (input) {
                self.processQrCode(input);
            } else {
                ScanWorkUtils.showMessage('warning', '请输入任务ID或二维码内容');
            }
        });
        
        // 提交报工
        $('#submit-report').click(function() {
            self.submitScanReport();
        });
    },
    
    // 切换扫码方式
    switchScanMethod: function(method) {
        currentMethod = method;
        
        $('.scan-method').removeClass('active');
        $('.scan-method[data-method="' + method + '"]').addClass('active');
        
        $('.scan-content').hide();
        
        if (method === 'camera') {
            $('#camera-method').show();
            if (stream) {
                $('#video').show();
            }
        } else {
            $('#manual-method').show();
            this.stopCamera();
        }
        
        this.hideScanResult();
    },
    
    // 开启摄像头
    startCamera: function() {
        if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
            navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } })
                .then(function(mediaStream) {
                    stream = mediaStream;
                    var video = document.getElementById('video');
                    video.srcObject = mediaStream;
                    video.play();
                    
                    $('#start-camera').hide();
                    $('#stop-camera').show();
                    
                    // 开始扫描
                    this.scanQRCode();
                })
                .catch(function(error) {
                    ScanWorkUtils.showMessage('error', '无法访问摄像头：' + error.message);
                });
        } else {
            ScanWorkUtils.showMessage('error', '您的浏览器不支持摄像头功能');
        }
    },
    
    // 关闭摄像头
    stopCamera: function() {
        if (stream) {
            stream.getTracks().forEach(function(track) {
                track.stop();
            });
            stream = null;
        }
        
        $('#video').hide();
        $('#start-camera').show();
        $('#stop-camera').hide();
    },
    
    // 扫描二维码
    scanQRCode: function() {
        if (!stream) return;
        
        var video = document.getElementById('video');
        var canvas = document.createElement('canvas');
        var context = canvas.getContext('2d');
        
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        
        var imageData = context.getImageData(0, 0, canvas.width, canvas.height);
        var code = jsQR(imageData.data, imageData.width, imageData.height);
        
        if (code) {
            this.processQrCode(code.data);
            return;
        }
        
        // 继续扫描
        setTimeout(function() {
            this.scanQRCode();
        }.bind(this), 100);
    },
    
    // 处理二维码内容
    processQrCode: function(content) {
        this.showScanLoading();
        
        // 尝试解析JSON格式的二维码
        try {
            var qrData = JSON.parse(content);
            if (qrData.type === 'allocation' && qrData.id) {
                content = qrData.id;
            }
        } catch (e) {
            // 如果不是JSON格式，直接使用内容
        }
        
        ScanWorkUtils.ajax('scan', {qr_code: content}, 'POST', function(response) {
            ScanWorkWorker.showTaskInfo(response.data);
        });
    },
    
    // 显示任务信息
    showTaskInfo: function(allocation) {
        $('#order-no').text(allocation.order.order_no);
        $('#product-name').text(allocation.model.product.name + ' - ' + allocation.model.name);
        $('#process-name').text(allocation.process.name);
        $('#remaining-quantity').text(allocation.remaining_quantity);
        $('#max-quantity').text(allocation.remaining_quantity);
        $('#report-quantity').attr('max', allocation.remaining_quantity);
        
        this.hideScanResult();
        $('#task-info').show();
    },
    
    // 提交扫码报工
    submitScanReport: function() {
        var quantity = parseInt($('#report-quantity').val());
        if (!quantity || quantity <= 0) {
            ScanWorkUtils.showMessage('warning', '请输入有效的报工数量');
            return;
        }
        
        var btn = $('#submit-report');
        var originalText = btn.html();
        btn.html('<i class="fa fa-spinner fa-spin"></i> 提交中...').prop('disabled', true);
        
        ScanWorkUtils.ajax('submit', {
            allocation_id: currentAllocation.id,
            quantity: quantity
        }, 'POST', function(response) {
            ScanWorkUtils.showMessage('success', '报工成功！');
            setTimeout(function() {
                window.location.href = 'tasks';
            }, 1500);
        }, function() {
            btn.html(originalText).prop('disabled', false);
        });
    },
    
    // 显示扫码加载状态
    showScanLoading: function() {
        $('#scan-result').show().html(`
            <div class="loading">
                <i class="fa fa-spinner fa-spin fa-2x"></i>
                <p>正在识别二维码...</p>
            </div>
        `);
    },
    
    // 隐藏扫码结果
    hideScanResult: function() {
        $('#scan-result').hide();
        $('#task-info').hide();
    }
};

// 页面加载完成后初始化
$(document).ready(function() {
    ScanWorkWorker.init();
    
    // 如果是扫码页面，初始化扫码功能
    if (window.location.pathname.indexOf('scan') > -1) {
        ScanWorkWorker.initScan();
    }
    // 报工表单一次性提交图片和数据
    var reportForm = document.getElementById('reportForm');
    if(reportForm){
        reportForm.addEventListener('submit', function(e){
            e.preventDefault();
            var input = document.getElementById('images');
            var files = input && input.files ? Array.from(input.files) : [];
            if(files.length > 9){
                alert('最多只能上传9张图片');
                return;
            }
            for(let i=0;i<files.length;i++){
                if(files[i].size > 10*1024*1024){
                    alert('图片不能超过10M');
                    return;
                }
            }
            var formData = new FormData(reportForm);
            var btn = reportForm.querySelector('button[type=submit]');
            var originalText = btn.innerHTML;
            btn.innerHTML = '提交中...';
            btn.disabled = true;
            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then(resp => resp.text()).then(function(html){
                // 简单处理：页面刷新或跳转
                document.open();document.write(html);document.close();
            }).catch(function(){
                alert('提交失败，请重试');
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
        });
    }
}); 