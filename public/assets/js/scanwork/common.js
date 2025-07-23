/**
 * 工厂报工管理系统 - 公共JS文件
 * 包含通用的工具函数和配置
 */

// 全局配置
window.ScanWorkConfig = {
    // API基础路径
    apiBase: '/index.php',
    
    // 默认分页大小
    pageSize: 20,
    
    // 日期格式
    dateFormat: 'YYYY-MM-DD',
    datetimeFormat: 'YYYY-MM-DD HH:mm:ss',
    
    // 状态映射
    statusMap: {
        0: '待生产',
        1: '生产中', 
        2: '已完成'
    },
    
    // 报工状态映射
    reportStatusMap: {
        0: '待确认',
        1: '已确认'
    }
};

// 工具函数
window.ScanWorkUtils = {
    /**
     * 格式化日期
     * @param {string|number} timestamp 时间戳
     * @param {string} format 格式
     * @returns {string} 格式化后的日期
     */
    formatDate: function(timestamp, format) {
        if (!timestamp) return '';
        
        var date = new Date(timestamp * 1000);
        format = format || ScanWorkConfig.dateFormat;
        
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        var hours = String(date.getHours()).padStart(2, '0');
        var minutes = String(date.getMinutes()).padStart(2, '0');
        var seconds = String(date.getSeconds()).padStart(2, '0');
        
        return format
            .replace('YYYY', year)
            .replace('MM', month)
            .replace('DD', day)
            .replace('HH', hours)
            .replace('mm', minutes)
            .replace('ss', seconds);
    },
    
    /**
     * 格式化数字
     * @param {number} num 数字
     * @param {number} decimals 小数位数
     * @returns {string} 格式化后的数字
     */
    formatNumber: function(num, decimals) {
        if (num === null || num === undefined) return '0';
        decimals = decimals || 2;
        return parseFloat(num).toFixed(decimals);
    },
    
    /**
     * 显示提示信息
     * @param {string} type 类型 (success, warning, error, info)
     * @param {string} message 消息内容
     * @param {number} duration 显示时长(毫秒)
     */
    showMessage: function(type, message, duration) {
        duration = duration || 3000;
        
        // 使用Toastr或原生alert
        if (typeof Toastr !== 'undefined') {
            Toastr[type](message);
        } else {
            alert(message);
        }
    },
    
    /**
     * 显示加载状态
     * @param {string} message 加载消息
     */
    showLoading: function(message) {
        message = message || '加载中...';
        
        // 创建加载遮罩
        var loadingHtml = `
            <div id="scanwork-loading" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
            ">
                <div style="
                    background: white;
                    padding: 20px;
                    border-radius: 10px;
                    text-align: center;
                ">
                    <i class="fa fa-spinner fa-spin fa-2x"></i>
                    <p style="margin-top: 10px;">${message}</p>
                </div>
            </div>
        `;
        
        $('body').append(loadingHtml);
    },
    
    /**
     * 隐藏加载状态
     */
    hideLoading: function() {
        $('#scanwork-loading').remove();
    },
    
    /**
     * AJAX请求封装
     * @param {string} url 请求地址
     * @param {object} data 请求数据
     * @param {string} method 请求方法
     * @param {function} success 成功回调
     * @param {function} error 错误回调
     */
    ajax: function(url, data, method, success, error) {
        method = method || 'GET';
        
        // 构建完整的URL
        var fullUrl = url;
        if (!url.startsWith('http') && !url.startsWith('/')) {
            fullUrl = '/index.php/worker/' + url;
        }
        
        $.ajax({
            url: fullUrl,
            data: data,
            type: method,
            dataType: 'json',
            success: function(response) {
                if (response.code === 1) {
                    if (typeof success === 'function') {
                        success(response);
                    }
                } else {
                    ScanWorkUtils.showMessage('error', response.msg || '请求失败');
                    if (typeof error === 'function') {
                        error(response);
                    }
                }
            },
            error: function(xhr, status, err) {
                ScanWorkUtils.showMessage('error', '网络错误，请重试');
                if (typeof error === 'function') {
                    error({code: 0, msg: '网络错误'});
                }
            }
        });
    },
    
    /**
     * 验证表单数据
     * @param {object} formData 表单数据
     * @param {object} rules 验证规则
     * @returns {object} 验证结果
     */
    validateForm: function(formData, rules) {
        var errors = {};
        
        for (var field in rules) {
            var value = formData[field];
            var rule = rules[field];
            
            // 必填验证
            if (rule.required && (!value || value.toString().trim() === '')) {
                errors[field] = rule.message || field + '不能为空';
                continue;
            }
            
            // 长度验证
            if (rule.minLength && value && value.toString().length < rule.minLength) {
                errors[field] = rule.message || field + '长度不能少于' + rule.minLength + '位';
                continue;
            }
            
            if (rule.maxLength && value && value.toString().length > rule.maxLength) {
                errors[field] = rule.message || field + '长度不能超过' + rule.maxLength + '位';
                continue;
            }
            
            // 数字验证
            if (rule.number && value && isNaN(parseFloat(value))) {
                errors[field] = rule.message || field + '必须是数字';
                continue;
            }
            
            // 最小值验证
            if (rule.min && value && parseFloat(value) < rule.min) {
                errors[field] = rule.message || field + '不能小于' + rule.min;
                continue;
            }
            
            // 最大值验证
            if (rule.max && value && parseFloat(value) > rule.max) {
                errors[field] = rule.message || field + '不能大于' + rule.max;
                continue;
            }
        }
        
        return {
            valid: Object.keys(errors).length === 0,
            errors: errors
        };
    },
    
    /**
     * 防抖函数
     * @param {function} func 要防抖的函数
     * @param {number} wait 等待时间
     * @returns {function} 防抖后的函数
     */
    debounce: function(func, wait) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    },
    
    /**
     * 节流函数
     * @param {function} func 要节流的函数
     * @param {number} limit 限制时间
     * @returns {function} 节流后的函数
     */
    throttle: function(func, limit) {
        var inThrottle;
        return function() {
            var args = arguments;
            var context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(function() {
                    inThrottle = false;
                }, limit);
            }
        };
    }
};

// 页面加载完成后初始化
$(document).ready(function() {
    // 初始化工具函数
    console.log('ScanWork Common JS Loaded');
    
    // 全局错误处理
    $(document).ajaxError(function(event, xhr, settings, error) {
        if (xhr.status === 401) {
            // 未授权，跳转到登录页
            window.location.href = '/index.php/user/login';
        }
    });
});

// 确保jQuery加载后再执行
if (typeof $ !== 'undefined') {
    $(document).ready(function() {
        // 初始化工具函数
        console.log('ScanWork Common JS Loaded');
        
        // 全局错误处理
        $(document).ajaxError(function(event, xhr, settings, error) {
            if (xhr.status === 401) {
                // 未授权，跳转到登录页
                window.location.href = '/index.php/user/login';
            }
        });
    });
} else {
    // jQuery未加载，等待加载
    window.addEventListener('load', function() {
        if (typeof $ !== 'undefined') {
            $(document).ready(function() {
                console.log('ScanWork Common JS Loaded (delayed)');
                
                // 全局错误处理
                $(document).ajaxError(function(event, xhr, settings, error) {
                    if (xhr.status === 401) {
                        window.location.href = '/index.php/user/login';
                    }
                });
            });
        }
    });
} 