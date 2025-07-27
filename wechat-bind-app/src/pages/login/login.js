Page({
  data: {
    username: '',
    password: '',
    loading: false,
    isLoggedIn: false,
    token: ''
  },

  onLoad: function () {
    // 检查是否已登录
    const token = wx.getStorageSync('token');
    if (token) {
      this.setData({
        isLoggedIn: true,
        token: token
      });
    }
  },

  onUsernameInput: function (e) {
    this.setData({
      username: e.detail.value
    });
  },

  onPasswordInput: function (e) {
    this.setData({
      password: e.detail.value
    });
  },

  login: function () {
    if (!this.data.username || !this.data.password) {
      wx.showToast({
        title: '请输入账号和密码',
        icon: 'none'
      });
      return;
    }

    this.setData({ loading: true });

    wx.request({
      url: getApp().globalData.apiUrl + 'wechat/login',
      method: 'POST',
      data: {
        username: this.data.username,
        password: this.data.password
      },
      success: res => {
        if (res.data.code === 1) {
          const data = res.data.data;
          wx.setStorageSync('token', data.token);
          this.setData({
            isLoggedIn: true,
            token: data.token
          });
          wx.showToast({
            title: '登录成功',
            icon: 'success'
          });
          setTimeout(() => {
            wx.reLaunch({ url: '/pages/index/index' });
          }, 1500);
        } else {
          wx.showToast({
            title: res.data.msg || '登录失败',
            icon: 'none'
          });
        }
      },
      fail: () => {
        wx.showToast({
          title: '网络错误',
          icon: 'none'
        });
      },
      complete: () => {
        this.setData({ loading: false });
      }
    });
  }
});