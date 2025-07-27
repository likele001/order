const app = getApp();

Page({
  data: {
    user: {},
    todayTaskCount: 0,
    todayReportCount: 0,
    todayWage: 0,
    today: '',
    loading: false
  },
  
  onLoad() {
    const userInfo = wx.getStorageSync('userInfo');
    if (!userInfo || !userInfo.token) {
      wx.reLaunch({ url: '/pages/login/login' });
      return;
    }
    
    this.setData({ loading: true });
    this.loadData();
  },
  
  onShow() {
    // 页面显示时重新加载数据，确保绑定状态更新
    this.loadData();
  },
  
  loadData() {
    const userInfo = wx.getStorageSync('userInfo');
    if (!userInfo || !userInfo.token) {
      wx.reLaunch({ url: '/pages/login/login' });
      return;
    }
    
    wx.request({
      url: app.globalData.apiUrl + 'worker/index',
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
          const data = res.data.data;
          this.setData({
            user: data.user || userInfo,
            todayTaskCount: data.todayTaskCount || 0,
            todayReportCount: data.todayReportCount || 0,
            todayWage: data.todayWage || 0,
            today: this.formatDate(new Date()),
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
  
  // 去绑定页面
  goBind() {
    const userInfo = getApp().globalData.userInfo;
    if (!userInfo || !userInfo.id) {
      wx.showToast({
        title: '请先登录账号',
        icon: 'none'
      });
      wx.navigateTo({ url: '/pages/login/login' });
      return;
    }
    wx.navigateTo({ url: `/pages/bind/bind?bind=${userInfo.id}` });
  },
  
  // 解绑账号
  unbindAccount() {
    wx.showModal({
      title: '确认解绑',
      content: '解绑后将无法使用员工功能，确定要解绑吗？',
      success: res => {
        if (res.confirm) {
          this.doUnbind();
        }
      }
    });
  },

  // 执行解绑
  doUnbind() {
    const userInfo = wx.getStorageSync('userInfo');
    if (!userInfo || !userInfo.token) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      return;
    }
    wx.request({
      url: app.globalData.apiUrl + 'user/unbind_account',
      method: 'POST',
      header: {
        'Token': userInfo.token
      },
      success: res => {
        if (res.data.code === 0) {
          wx.showToast({ title: '解绑成功', icon: 'success' });
          wx.removeStorageSync('userInfo');
          setTimeout(() => {
            wx.reLaunch({ url: '/pages/login/login' });
          }, 1500);
        } else {
          wx.showToast({ title: res.data.msg || '解绑失败', icon: 'none' });
        }
      },
      fail: () => {
        wx.showToast({ title: '网络错误', icon: 'none' });
      }
    });
  },
  
  // 导航功能
  goTasks() {
    wx.switchTab({ url: '/pages/tasks/tasks' });
  },
  
  goRecords() {
    wx.switchTab({ url: '/pages/records/records' });
  },
  
  goWage() {
    wx.switchTab({ url: '/pages/wage/wage' });
  },
  
  // 格式化日期
  formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const weekdays = ['日', '一', '二', '三', '四', '五', '六'];
    const weekday = weekdays[date.getDay()];
    return `${year}年${month}月${day}日 星期${weekday}`;
  }
});