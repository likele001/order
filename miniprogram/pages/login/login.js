Page({
  data: {
    userInfo: null,
    hasUserInfo: false,
    canIUse: wx.canIUse('button.open-type.getUserInfo'),
    isLoggedIn: false,
    isBound: false,
    employeeNo: '',
    token: ''
  },

  onLoad: function () {
    // 检查是否已登录
    const userInfo = wx.getStorageSync('userInfo');
    if (userInfo && userInfo.token) {
      this.setData({
        isLoggedIn: true,
        token: userInfo.token,
        userInfo: userInfo
      });
      // 检查是否需要绑定
      if (userInfo.group_id === 0) {
        this.setData({ isBound: false });
      } else {
        this.setData({ isBound: true });
      }
    }
  },

  // 微信登录
  wechatLogin: function() {
    const that = this;
    
    wx.login({
      success: function(res) {
        if (res.code) {
          // 发送登录请求
          wx.request({
            url: getApp().globalData.apiUrl + 'user/miniprogram_login',
            method: 'POST',
            data: {
              code: res.code
            },
            success: function(response) {
              console.log('登录接口返回:', response.data);
              if (response.data.code === 0 || response.data.code === 1) {
                // 登录成功
                const userInfo = response.data.data.userinfo;
                wx.setStorageSync('userInfo', userInfo);
                getApp().globalData.userInfo = userInfo;
                
                that.setData({
                  isLoggedIn: true,
                  token: userInfo.token,
                  userInfo: userInfo
                });
                
                // 检查是否需要绑定
                if (userInfo.group_id === 0) {
                  that.setData({ isBound: false });
                  wx.showToast({
                    title: '登录成功，请绑定员工账号',
                    icon: 'success'
                  });
                } else {
                  that.setData({ isBound: true });
                  wx.showToast({
                    title: '登录成功',
                    icon: 'success'
                  });
                  // 跳转到主页
                  setTimeout(() => {
                    wx.switchTab({
                      url: '/pages/index/index'
                    });
                  }, 1500);
                }
              } else {
                wx.showToast({
                  title: response.data.msg || '登录失败',
                  icon: 'none'
                });
              }
            },
            fail: function() {
              wx.showToast({
                title: '网络错误',
                icon: 'none'
              });
            }
          });
        }
      }
    });
  },

  // 跳转到绑定页面
  goToBind: function() {
    wx.navigateTo({
      url: '/pages/login/bind'
    });
  },

  // 退出登录
  logout: function() {
    wx.removeStorageSync('userInfo');
    getApp().globalData.userInfo = null;
    this.setData({
      isLoggedIn: false,
      isBound: false,
      userInfo: null,
      employeeNo: '',
      token: ''
    });
    wx.showToast({
      title: '已退出登录',
      icon: 'success'
    });
  }
});