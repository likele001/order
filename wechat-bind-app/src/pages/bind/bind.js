Page({
  data: {
    openid: '',
    loading: false,
    bindMethod: 'scan', // Default to scan method
    employees: [],
    selectedIndex: null,
    password: ''
  },

  onLoad() {
    const userInfo = wx.getStorageSync('userInfo');
    this.setData({ openid: userInfo && userInfo.openid ? userInfo.openid : '' });
    this.loadEmployees();
  },

  loadEmployees() {
    const app = getApp();
    wx.request({
      url: app.globalData.apiUrl + 'user/employee_list',
      success: res => {
        if (res.data.code === 0) {
          this.setData({ employees: res.data.data });
        } else {
          wx.showToast({ title: '获取员工列表失败', icon: 'none' });
        }
      },
      fail: () => {
        wx.showToast({ title: '网络错误', icon: 'none' });
      }
    });
  },

  switchMethod(e) {
    const method = e.currentTarget.dataset.method;
    this.setData({ bindMethod: method });
  },

  scanQRCode() {
    const openid = this.data.openid;
    if (!openid) {
      wx.showToast({ title: '未获取到微信openid', icon: 'none' });
      return;
    }
    wx.scanCode({
      success: res => {
        this.handleScanResult(res.result);
      },
      fail: err => {
        wx.showToast({ title: err.errMsg.includes('cancel') ? '取消扫码' : '扫码失败', icon: 'none' });
      }
    });
  },

  handleScanResult(result) {
    try {
      if (result.startsWith('bind:')) {
        const parts = result.split(':');
        if (parts.length >= 3) {
          const username = parts[1];
          const password = parts[2];
          this.setData({ loading: true });
          this.bindWithCredentials(username, password);
        } else {
          wx.showToast({ title: '无效的绑定二维码', icon: 'none' });
        }
      } else {
        wx.showToast({ title: '无效的绑定二维码', icon: 'none' });
      }
    } catch (error) {
      wx.showToast({ title: '二维码格式错误', icon: 'none' });
    }
  },

  bindWithCredentials(username, password) {
    const openid = this.data.openid;
    const app = getApp();
    this.setData({ loading: true });
    wx.request({
      url: app.globalData.apiUrl + 'user/bind_account',
      method: 'POST',
      data: {
        openid: openid,
        account: username,
        password: password
      },
      success: res => {
        if (res.data.code === 0) {
          wx.showToast({ title: '绑定成功', icon: 'success' });
          wx.setStorageSync('userInfo', res.data.data.userinfo);
          app.globalData.userInfo = res.data.data.userinfo;
          setTimeout(() => wx.reLaunch({ url: '/pages/index/index' }), 1000);
        } else {
          wx.showToast({ title: res.data.msg || '绑定失败', icon: 'none' });
        }
      },
      fail: () => {
        wx.showToast({ title: '网络错误', icon: 'none' });
      },
      complete: () => this.setData({ loading: false })
    });
  },

  onEmployeeChange(e) {
    this.setData({ selectedIndex: e.detail.value });
  },

  onPasswordInput(e) {
    this.setData({ password: e.detail.value });
  },

  bindAccount() {
    if (this.data.selectedIndex === null || !this.data.password) {
      wx.showToast({ title: '请选择员工并输入密码', icon: 'none' });
      return;
    }
    const openid = this.data.openid;
    if (!openid) {
      wx.showToast({ title: '未获取到微信openid', icon: 'none' });
      return;
    }
    const employee = this.data.employees[this.data.selectedIndex];
    this.bindWithCredentials(employee.username, this.data.password);
  }
});