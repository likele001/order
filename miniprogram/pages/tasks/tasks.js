const app = getApp();

Page({
  data: {
    tasks: [],
    loading: false
  },
  onLoad() {
    const userInfo = wx.getStorageSync('userInfo');
    if (!userInfo || !userInfo.token) {
      wx.reLaunch({ url: '/pages/login/login' });
      return;
    }
    this.setData({ loading: true });
    wx.request({
      url: app.globalData.apiUrl + 'worker/tasks',
      method: 'GET',
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
          this.setData({ tasks: res.data.data, loading: false });
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
  goReport(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/pages/report/report?id=' + id });
  },
  
  // 时间格式化函数
  formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hour}:${minute}`;
  }
});