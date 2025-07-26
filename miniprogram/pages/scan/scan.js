// scan.js
Page({
  data: {
    allocationId: '',
    reportQuantity: '',
    remark: '',
    images: []
  },
  onLoad: function(options) {
    // 通过扫码获取 allocation_id
    if (options.allocationId) {
      this.setData({ allocationId: options.allocationId });
      this.loadTaskInfo(options.allocationId);
    }
  },
  loadTaskInfo: function(id) {
    // 调用 API 获取任务信息
    wx.request({
      url: 'https://your-domain.com/api/worker/task/' + id,
      success: res => {
        if (res.data.success) {
          this.setData({ task: res.data.task });
        }
      }
    });
  },
  inputQuantity: function(e) {
    this.setData({ reportQuantity: e.detail.value });
  },
  inputRemark: function(e) {
    this.setData({ remark: e.detail.value });
  },
  uploadImage: function() {
    wx.chooseImage({
      success: res => {
        const tempFilePaths = res.tempFilePaths;
        this.setData({ images: this.data.images.concat(tempFilePaths) });
      }
    });
  },
  submitReport: function() {
    wx.request({
      url: 'https://your-domain.com/api/worker/report',
      method: 'POST',
      data: {
        allocation_id: this.data.allocationId,
        quantity: this.data.reportQuantity,
        remark: this.data.remark,
        images: this.data.images
      },
      success: res => {
        if (res.data.success) {
          wx.showToast({ title: '报工成功' });
        }
      }
    });
  }
});