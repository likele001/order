Page({
  data: {
    username: '',
    password: '',
    loading: false
  },
  onUsernameInput(e) {
    this.setData({ username: e.detail.value });
  },
  onPasswordInput(e) {
    this.setData({ password: e.detail.value });
  },
  login() {
    if (!this.data.username || !this.data.password) {
      wx.showToast({ title: '请输入账号和密码', icon: 'none' });
      return;
    }
    this.setData({ loading: true });
    const app = getApp();
    wx.request({
      url: app.globalData.apiUrl + 'user/login',
      method: 'POST',
      data: {
        account: this.data.username,
        password: this.data.password
      },
      success: res => {
        // 兼容code=1和code=0
        if ((res.data.code === 0 || res.data.code === 1) && res.data.data && res.data.data.userinfo) {
          // 统一userInfo结构
          const userInfo = res.data.data.userinfo;
          wx.setStorageSync('userInfo', userInfo);
          app.globalData.userInfo = userInfo;
          wx.showToast({ title: '登录成功', icon: 'success' });
          setTimeout(() => wx.reLaunch({ url: '/pages/index/index' }), 500);
        } else {
          wx.showToast({ title: res.data.msg || '登录失败', icon: 'none' });
        }
      },
      fail: err => {
        wx.showToast({ title: '网络错误', icon: 'none' });
      },
      complete: () => this.setData({ loading: false })
    });
  }
});