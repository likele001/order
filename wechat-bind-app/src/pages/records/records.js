Page({
  data: {
    records: [],
    loading: true,
  },

  onLoad() {
    this.loadRecords();
  },

  loadRecords() {
    const app = getApp();
    const userInfo = wx.getStorageSync('userInfo');

    if (!userInfo || !userInfo.token) {
      wx.reLaunch({ url: '/pages/login/login' });
      return;
    }

    wx.request({
      url: app.globalData.apiUrl + 'worker/records',
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
          this.setData({ records: res.data.data, loading: false });
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
});