// pages/bind/bind.js
Page({
  data: {
    userInfo: null,
    loading: false
  },

  onLoad: function (options) {
    // 获取用户信息
    this.getUserInfo();
  },

  // 获取用户信息
  getUserInfo: function() {
    const that = this;
    
    wx.getUserProfile({
      desc: '用于绑定账号',
      success: function(res) {
        that.setData({
          userInfo: res.userInfo
        });
      },
      fail: function() {
        wx.showToast({
          title: '需要授权才能绑定',
          icon: 'none'
        });
      }
    });
  },

  // 确认绑定
  confirmBind: function() {
    if (!this.data.userInfo) {
      wx.showToast({
        title: '请先授权获取用户信息',
        icon: 'none'
      });
      return;
    }

    const that = this;
    this.setData({ loading: true });

    // 先进行微信登录获取openid
    wx.login({
      success: function(loginRes) {
        if (loginRes.code) {
          // 调用后端获取openid接口
          wx.request({
            url: getApp().globalData.apiUrl + 'wechat/login',
            method: 'POST',
            data: {
              code: loginRes.code,
              userInfo: that.data.userInfo
            },
            success: function(response) {
              if (response.data.code === 1) {
                const openid = response.data.data.user.wechat_openid;
                
                // 调用绑定接口，使用当前登录用户的token
                wx.request({
                  url: getApp().globalData.apiUrl + 'wechat/bind',
                  method: 'POST',
                  header: {
                    'Authorization': 'Bearer ' + getApp().globalData.token
                  },
                  data: {
                    openid: openid
                  },
                  success: function(res) {
                    if (res.data.code === 1) {
                      wx.showToast({
                        title: '绑定成功',
                        icon: 'success'
                      });
                      // 更新全局用户信息
                      getApp().globalData.userInfo.wechat_openid = openid;
                      setTimeout(() => {
                        wx.navigateBack();
                      }, 1500);
                    } else {
                      wx.showToast({
                        title: res.data.msg || '绑定失败',
                        icon: 'none'
                      });
                    }
                  },
                  fail: function() {
                    wx.showToast({
                      title: '网络错误',
                      icon: 'none'
                    });
                  },
                  complete: function() {
                    that.setData({ loading: false });
                  }
                });
              } else {
                that.setData({ loading: false });
                wx.showToast({
                  title: response.data.msg || '获取用户信息失败',
                  icon: 'none'
                });
              }
            },
            fail: function() {
              that.setData({ loading: false });
              wx.showToast({
                title: '网络错误',
                icon: 'none'
              });
            }
          });
        } else {
          that.setData({ loading: false });
          wx.showToast({
            title: '登录失败，无法获取code',
            icon: 'none'
          });
        }
      },
      fail: function() {
        that.setData({ loading: false });
        wx.showToast({
          title: '微信登录失败',
          icon: 'none'
        });
      }
    });
  }
});
