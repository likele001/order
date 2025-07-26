// wage.js
const app = getApp();

Page({
  data: {
    wages: [],
    totalWage: 0,
    averageWage: '0.00',
    month: '',
    loading: false
  },
  onLoad() {
    const userInfo = wx.getStorageSync('userInfo');
    if (!userInfo || !userInfo.token) {
      wx.reLaunch({ url: '/pages/login/login' });
      return;
    }
    
    // 设置当前月份
    const now = new Date();
    const currentMonth = `${now.getFullYear()}年${String(now.getMonth() + 1).padStart(2, '0')}月`;
    this.setData({ month: currentMonth });
    
    this.setData({ loading: true });
    wx.request({
      url: app.globalData.apiUrl + 'worker/wages',
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
          const data = res.data.data || {};
          const wages = data.wages || [];
          const totalWage = data.totalWage || 0;
          const averageWage = wages.length > 0 ? (totalWage / wages.length).toFixed(2) : '0.00';
          
          this.setData({ 
            wages: wages, 
            totalWage: totalWage,
            averageWage: averageWage,
            month: data.month || currentMonth,
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
  
  // 时间格式化函数
  formatTime(timestamp) {
    if (!timestamp) return '';
    const date = new Date(timestamp * 1000);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');
    return `${year}-${month}-${day} ${hour}:${minute}`;
  }
});