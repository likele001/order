Page({
  data: {
    user: {},
    isLoggedIn: false,
    loading: false
  },

  onLoad() {
    this.checkLoginStatus();
  },

  checkLoginStatus() {
    const userInfo = wx.getStorageSync('userInfo');
    if (userInfo && userInfo.token) {
      this.setData({
        user: userInfo,
        isLoggedIn: true
      });
    } else {
      this.setData({
        isLoggedIn: false
      });
    }
  },

  goToBindPage() {
    wx.navigateTo({
      url: '/pages/bind/bind'
    });
  },

  logout() {
    wx.removeStorageSync('userInfo');
    this.setData({
      user: {},
      isLoggedIn: false
    });
    wx.showToast({
      title: '已退出登录',
      icon: 'success'
    });
  }
});