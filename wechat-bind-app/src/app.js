Page({
  onLaunch: function () {
    // Initialize global data
    this.globalData = {
      userInfo: null,
      token: '',
      isLoggedIn: false,
      isBound: false
    };

    // Check if user is logged in
    const token = wx.getStorageSync('token');
    if (token) {
      this.globalData.isLoggedIn = true;
      this.globalData.token = token;
    }
  },

  onShow: function () {
    // Handle app show event
  },

  onHide: function () {
    // Handle app hide event
  },

  globalData: {
    apiUrl: 'https://your-api-url.com/' // Replace with your actual API URL
  }
});