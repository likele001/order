const app = getApp();

Page({
  data: {
    task: null,
    quantity: '',
    remark: '',
    images: [],
    loading: false,
    uploading: false
  },
  onLoad(options) {
    const userInfo = wx.getStorageSync('userInfo');
    if (!userInfo || !userInfo.token) {
      wx.reLaunch({ url: '/pages/login/login' });
      return;
    }
    
    if (options.id) {
      this.setData({ loading: true });
      this.loadTask(options.id);
    } else {
      wx.showToast({ title: '缺少任务ID', icon: 'none' });
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
    }
  },
  loadTask(id) {
    const userInfo = wx.getStorageSync('userInfo');
    wx.request({
      url: app.globalData.apiUrl + 'worker/report',
      method: 'GET',
      data: { id: id },
      header: {
        'Token': userInfo.token
      },
      success: res => {
        if (res.data.code === 401) {
          wx.removeStorageSync('userInfo');
          wx.reLaunch({ url: '/pages/login/login' });
          return;
        }
        if (res.data.code === 1 || res.data.code === 0 || res.data.code === undefined) {
          this.setData({ 
            task: res.data.data, 
            loading: false 
          });
        } else {
          wx.showToast({ title: res.data.msg || '加载失败', icon: 'none' });
          this.setData({ loading: false });
        }
      },
      fail: () => {
        wx.showToast({ title: '网络错误', icon: 'none' });
        this.setData({ loading: false });
      }
    });
  },
  
  // 输入处理函数
  onQuantityInput(e) {
    this.setData({ quantity: e.detail.value });
  },
  
  onRemarkInput(e) {
    this.setData({ remark: e.detail.value });
  },
  
  submitReport() {
    if (!this.data.quantity) {
      wx.showToast({ title: '请输入报工数量', icon: 'none' });
      return;
    }
    
    this.setData({ uploading: true });
    const userInfo = wx.getStorageSync('userInfo');
    
    wx.request({
      url: app.globalData.apiUrl + 'worker/submit',
      method: 'POST',
      data: {
        allocation_id: this.data.task.id,
        quantity: this.data.quantity,
        remark: this.data.remark
      },
      header: {
        'Token': userInfo.token
      },
      success: res => {
        if (res.data.code === 401) {
          wx.removeStorageSync('userInfo');
          wx.reLaunch({ url: '/pages/login/login' });
          return;
        }
        if (res.data.code === 1 || res.data.code === 0 || res.data.code === undefined) {
          const reportId = res.data.data.report_id;
          wx.showToast({ title: '报工提交成功', icon: 'success' });
          
          // 如果有图片，开始上传图片
          if (this.data.images.length > 0) {
            this.uploadImages(reportId);
          } else {
            this.setData({ uploading: false });
            setTimeout(() => {
              wx.navigateBack();
            }, 1500);
          }
        } else {
          wx.showToast({ title: res.data.msg || '提交失败', icon: 'none' });
          this.setData({ uploading: false });
        }
      },
      fail: () => {
        wx.showToast({ title: '网络错误', icon: 'none' });
        this.setData({ uploading: false });
      }
    });
  },
  
  chooseImage() {
    wx.chooseImage({
      count: 9 - this.data.images.length,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: res => {
        this.setData({
          images: [...this.data.images, ...res.tempFilePaths]
        });
      }
    });
  },
  
  removeImage(e) {
    const idx = e.currentTarget.dataset.idx;
    const images = this.data.images;
    images.splice(idx, 1);
    this.setData({ images });
  },
  
  uploadImages(reportId) {
    if (!reportId || this.data.images.length === 0) {
      this.setData({ uploading: false });
      setTimeout(() => {
        wx.navigateBack();
      }, 1500);
      return;
    }
    
    const userInfo = wx.getStorageSync('userInfo');
    let uploadedCount = 0;
    const totalImages = this.data.images.length;
    
    this.data.images.forEach((imagePath, index) => {
      wx.uploadFile({
        url: app.globalData.apiUrl + 'worker/uploadImage',
        filePath: imagePath,
        name: 'file',
        formData: {
          report_id: reportId
        },
        header: {
          'Token': userInfo.token
        },
        success: res => {
          uploadedCount++;
          if (uploadedCount === totalImages) {
            wx.showToast({ title: '图片上传完成', icon: 'success' });
            this.setData({ uploading: false });
            setTimeout(() => {
              wx.navigateBack();
            }, 1500);
          }
        },
        fail: () => {
          uploadedCount++;
          if (uploadedCount === totalImages) {
            wx.showToast({ title: '部分图片上传失败', icon: 'none' });
            this.setData({ uploading: false });
            setTimeout(() => {
              wx.navigateBack();
            }, 1500);
          }
        }
      });
    });
  }
});