Page({
  data: {
    employees: [],
    selectedIndex: null,
    password: '',
    loading: false,
    openid: '',
    bindMethod: 'scan' // 默认使用扫码绑定
  },
  
  onLoad() {
    console.log('进入绑定页面 onLoad');
    const userInfo = wx.getStorageSync('userInfo');
    console.log('bind页面 onLoad userInfo:', userInfo);
    this.setData({ openid: userInfo && userInfo.openid ? userInfo.openid : '' });
    this.loadEmployees();
  },
  
  // 加载员工列表
  loadEmployees() {
    const app = getApp();
    wx.request({
      url: app.globalData.apiUrl + 'user/employee_list',
      success: res => {
        console.log('员工列表接口返回:', res.data);
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
  
  // 切换绑定方式
  switchMethod(e) {
    const method = e.currentTarget.dataset.method;
    this.setData({ bindMethod: method });
  },
  
  // 扫码绑定
  scanQRCode() {
    const openid = this.data.openid;
    if (!openid) {
      wx.showToast({ title: '未获取到微信openid', icon: 'none' });
      return;
    }
    
    wx.scanCode({
      success: res => {
        console.log('扫码结果:', res);
        this.handleScanResult(res.result);
      },
      fail: err => {
        console.log('扫码失败:', err);
        if (err.errMsg.includes('cancel')) {
          wx.showToast({ title: '取消扫码', icon: 'none' });
        } else {
          wx.showToast({ title: '扫码失败', icon: 'none' });
        }
      }
    });
  },
  
  // 处理扫码结果
  handleScanResult(result) {
    try {
      // 解析二维码内容
      if (result.startsWith('bind_token:')) {
        // PC端生成的token绑定
        const token = result.replace('bind_token:', '');
        this.bindByToken(token);
      } else if (result.startsWith('bind:')) {
        // 旧的手动绑定方式
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
      console.log('解析二维码失败:', error);
      wx.showToast({ title: '二维码格式错误', icon: 'none' });
    }
  },
  
  // 通过token绑定
  bindByToken(token) {
    const openid = this.data.openid;
    const app = getApp();
    
    this.setData({ loading: true });
    
    wx.request({
      url: app.globalData.apiUrl + 'user/bind_by_token',
      method: 'POST',
      data: {
        token: token,
        openid: openid
      },
      success: res => {
        console.log('token绑定接口返回:', res.data);
        if (res.data.code === 0) {
          wx.showToast({ title: '绑定成功', icon: 'success' });
          wx.setStorageSync('userInfo', res.data.data.userinfo);
          app.globalData.userInfo = res.data.data.userinfo;
          setTimeout(() => wx.reLaunch({ url: '/pages/index/index' }), 1000);
        } else {
          wx.showToast({ title: res.data.msg || '绑定失败', icon: 'none' });
        }
      },
      fail: err => {
        console.log('token绑定接口网络错误:', err);
        wx.showToast({ title: '网络错误', icon: 'none' });
      },
      complete: () => this.setData({ loading: false })
    });
  },
  
  // 使用凭据绑定
  bindWithCredentials(username, password) {
    const openid = this.data.openid;
    const app = getApp();
    
    wx.request({
      url: app.globalData.apiUrl + 'user/bind_account',
      method: 'POST',
      data: {
        openid: openid,
        account: username,
        password: password
      },
      success: res => {
        console.log('绑定接口返回:', res.data);
        if (res.data.code === 0) {
          wx.showToast({ title: '绑定成功', icon: 'success' });
          wx.setStorageSync('userInfo', res.data.data.userinfo);
          app.globalData.userInfo = res.data.data.userinfo;
          setTimeout(() => wx.reLaunch({ url: '/pages/index/index' }), 1000);
        } else {
          wx.showToast({ title: res.data.msg || '绑定失败', icon: 'none' });
        }
      },
      fail: err => {
        console.log('绑定接口网络错误:', err);
        wx.showToast({ title: '网络错误', icon: 'none' });
      },
      complete: () => this.setData({ loading: false })
    });
  },
  
  // 手动绑定相关方法
  onEmployeeChange(e) {
    console.log('选择员工index:', e.detail.value);
    this.setData({ selectedIndex: e.detail.value });
  },
  
  onPasswordInput(e) {
    console.log('输入密码:', e.detail.value);
    this.setData({ password: e.detail.value });
  },
  
  bindAccount() {
    if (this.data.selectedIndex === null || !this.data.password) {
      wx.showToast({ title: '请选择员工并输入密码', icon: 'none' });
      return;
    }
    
    const openid = this.data.openid;
    console.log('当前openid:', openid);
    if (!openid) {
      wx.showToast({ title: '未获取到微信openid', icon: 'none' });
      return;
    }
    
    const employee = this.data.employees[this.data.selectedIndex];
    console.log('绑定员工:', employee);
    
    this.bindWithCredentials(employee.username, this.data.password);
  }
}); 