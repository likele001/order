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
    const token = wx.getStorageSync('token');
    if (token) {
      this.setData({
        isLoggedIn: true,
        token: token
      });
      this.checkBindStatus();
    }
  },

  // 微信登录
  wechatLogin: function() {
    const that = this;
    
    wx.login({
      success: function(res) {
        if (res.code) {
          // 获取用户信息
          wx.getUserProfile({
            desc: '用于完善用户资料',
            success: function(userRes) {
              // 发送登录请求
              wx.request({
                url: getApp().globalData.apiUrl + 'wechat/login',
                method: 'POST',
                data: {
                  code: res.code,
                  userInfo: userRes.userInfo
                },
                success: function(response) {
                  if (response.data.code === 1) {
                    // 登录成功
                    const data = response.data.data;
                    wx.setStorageSync('token', data.token);
                    
                    that.setData({
                      isLoggedIn: true,
                      isBound: data.is_bound,
                      userInfo: data.user,
                      token: data.token
                    });
                    
                    wx.showToast({
                      title: '登录成功',
                      icon: 'success'
                    });
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
            },
            fail: function() {
              wx.showToast({
                title: '需要授权才能登录',
                icon: 'none'
              });
            }
          });
        }
      }
    });
  },

  // 检查绑定状态
  checkBindStatus: function() {
    const that = this;
    wx.request({
      url: getApp().globalData.apiUrl + 'wechat/getBindStatus',
      method: 'POST',
      header: {
        'token': this.data.token
      },
      success: function(response) {
        if (response.data.code === 1) {
          const data = response.data.data;
          that.setData({
            isBound: data.is_bound,
            employeeNo: data.employee_no,
            userInfo: {
              nickname: data.nickname,
              avatar: data.avatar
            }
          });
        }
      }
    });
  },

  // 输入员工号
  onEmployeeNoInput: function(e) {
    this.setData({
      employeeNo: e.detail.value
    });
  },

  // 绑定员工号
  bindEmployee: function() {
    if (!this.data.employeeNo.trim()) {
      wx.showToast({
        title: '请输入员工号',
        icon: 'none'
      });
      return;
    }

    const that = this;
    wx.request({
      url: getApp().globalData.apiUrl + 'wechat/bindEmployee',
      method: 'POST',
      header: {
        'token': this.data.token
      },
      data: {
        employee_no: this.data.employeeNo
      },
      success: function(response) {
        if (response.data.code === 1) {
          that.setData({
            isBound: true
          });
          wx.showToast({
            title: '绑定成功',
            icon: 'success'
          });
          
          // 跳转到主页或其他页面
          setTimeout(() => {
            wx.switchTab({
              url: '/pages/index/index'
            });
          }, 1500);
        } else {
          wx.showToast({
            title: response.data.msg || '绑定失败',
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
  },

  // 解绑员工号
  unbindEmployee: function() {
    const that = this;
    wx.showModal({
      title: '确认解绑',
      content: '确定要解绑员工号吗？',
      success: function(res) {
        if (res.confirm) {
          wx.request({
            url: getApp().globalData.apiUrl + 'wechat/unbindEmployee',
            method: 'POST',
            header: {
              'token': that.data.token
            },
            success: function(response) {
              if (response.data.code === 1) {
                that.setData({
                  isBound: false,
                  employeeNo: ''
                });
                wx.showToast({
                  title: '解绑成功',
                  icon: 'success'
                });
              } else {
                wx.showToast({
                  title: response.data.msg || '解绑失败',
                  icon: 'none'
                });
              }
            }
          });
        }
      }
    });
  },

  // 退出登录
  logout: function() {
    wx.removeStorageSync('token');
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