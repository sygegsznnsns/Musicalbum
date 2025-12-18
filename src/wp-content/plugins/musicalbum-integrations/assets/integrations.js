(function($){
  $(function(){
    // OCR 功能
    var btn = $('#musicalbum-ocr-button');
    var file = $('#musicalbum-ocr-file');
    btn.on('click', function(){
      var f = file[0] && file[0].files && file[0].files[0];
      if(!f) return;
      var fd = new FormData();
      fd.append('image', f);
      $.ajax({
        url: ViewingRecords.rest.ocr,
        method: 'POST',
        headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce },
        data: fd,
        processData: false,
        contentType: false
      }).done(function(res){
        try {
          if (window.acf && res) {
            if (res.title) $('input[name="post_title"]').val(res.title);
            // 尝试新的字段key，如果不存在则尝试旧的（向后兼容）
            var theaterField = acf.getField('field_viewing_theater') || acf.getField('field_malbum_theater');
            if (res.theater && theaterField) theaterField.val(res.theater);
            var castField = acf.getField('field_viewing_cast') || acf.getField('field_malbum_cast');
            if (res.cast && castField) castField.val(res.cast);
            var priceField = acf.getField('field_viewing_price') || acf.getField('field_malbum_price');
            if (res.price && priceField) priceField.val(res.price);
            var dateField = acf.getField('field_viewing_date') || acf.getField('field_malbum_date');
            if (res.view_date && dateField) dateField.val(res.view_date);
          }
        } catch(e) {}
      });
    });

    // 统计数据图表渲染
    if ($('.musicalbum-statistics-container').length > 0) {
      // 等待Chart.js加载完成
      if (typeof Chart !== 'undefined') {
        loadStatistics();
      } else {
        // 如果Chart.js还没加载，等待一下
        setTimeout(function() {
          if (typeof Chart !== 'undefined') {
            loadStatistics();
          }
        }, 500);
      }
    }
    
    // 按钮事件绑定（使用事件委托，不依赖Chart.js）
    $(document).on('click', '#musicalbum-refresh-btn', function(e) {
      e.preventDefault();
      var btn = $(this);
      if (btn.prop('disabled')) return;
      
      btn.prop('disabled', true);
      var icon = btn.find('.musicalbum-icon-refresh');
      if (icon.length) {
        icon.addClass('spin');
      }
      
      loadStatistics(function() {
        btn.prop('disabled', false);
        if (icon.length) {
          icon.removeClass('spin');
        }
      });
    });
    
    $(document).on('click', '#musicalbum-export-btn', function(e) {
      e.preventDefault();
      exportStatistics();
    });
  });

  // 存储图表实例，用于刷新和导出
  var chartInstances = {
    category: null,
    cast: null,
    price: null
  };

  /**
   * 加载统计数据并渲染图表
   */
  function loadStatistics(callback) {
    var loadingEl = $('#musicalbum-statistics-loading');
    loadingEl.show();

    $.ajax({
      url: ViewingRecords.rest.statistics,
      method: 'GET',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce }
    }).done(function(data) {
      loadingEl.hide();
      
      // 销毁旧图表
      if (chartInstances.category) chartInstances.category.destroy();
      if (chartInstances.cast) chartInstances.cast.destroy();
      if (chartInstances.price) chartInstances.price.destroy();
      
      // 渲染剧目类别分布饼图
      renderCategoryChart(data.category || {});
      
      // 渲染演员出场频率柱状图
      renderCastChart(data.cast || {});
      
      // 渲染票价区间折线图
      renderPriceChart(data.price || {});
      
      if (callback) callback();
    }).fail(function() {
      loadingEl.html('加载数据失败，请稍后重试').css('color', '#dc2626');
      if (callback) callback();
    });
  }

  /**
   * 渲染剧目类别分布饼图
   */
  function renderCategoryChart(data) {
    var ctx = document.getElementById('musicalbum-chart-category');
    if (!ctx) return;

    var labels = Object.keys(data);
    var values = Object.values(data);
    
    // 生成颜色
    var colors = generateColors(labels.length);

    chartInstances.category = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: labels,
        datasets: [{
          data: values,
          backgroundColor: colors,
          borderWidth: 2,
          borderColor: '#fff'
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        onClick: function(event, elements) {
          if (elements.length > 0) {
            var index = elements[0].index;
            var category = labels[index];
            showDetails('category', category);
          }
        },
        plugins: {
          legend: {
            position: 'bottom',
            labels: {
              padding: 15,
              font: {
                size: 12
              }
            }
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                var label = context.label || '';
                var value = context.parsed || 0;
                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                var percentage = ((value / total) * 100).toFixed(1);
                return label + ': ' + value + ' 场 (' + percentage + '%)';
              }
            }
          }
        }
      }
    });
  }

  /**
   * 渲染演员出场频率柱状图
   */
  function renderCastChart(data) {
    var ctx = document.getElementById('musicalbum-chart-cast');
    if (!ctx) return;

    var labels = Object.keys(data);
    var values = Object.values(data);

    chartInstances.cast = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: labels,
        datasets: [{
          label: '出场次数',
          data: values,
          backgroundColor: 'rgba(59, 130, 246, 0.6)',
          borderColor: 'rgba(59, 130, 246, 1)',
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        onClick: function(event, elements) {
          if (elements.length > 0) {
            var index = elements[0].index;
            var cast = labels[index];
            showDetails('cast', cast);
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return '出场 ' + context.parsed.y + ' 次';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          },
          x: {
            ticks: {
              maxRotation: 45,
              minRotation: 45
            }
          }
        }
      }
    });
  }

  /**
   * 渲染票价区间折线图
   */
  function renderPriceChart(data) {
    var ctx = document.getElementById('musicalbum-chart-price');
    if (!ctx) return;

    var labels = Object.keys(data);
    var values = Object.values(data);

    // 按区间排序
    var sorted = labels.map(function(label, index) {
      return {
        label: label,
        value: values[index],
        sortKey: parseFloat(label.split('-')[0])
      };
    }).sort(function(a, b) {
      return a.sortKey - b.sortKey;
    });

    var sortedLabels = sorted.map(function(item) { return item.label; });
    var sortedValues = sorted.map(function(item) { return item.value; });

    chartInstances.price = new Chart(ctx, {
      type: 'line',
      data: {
        labels: sortedLabels,
        datasets: [{
          label: '场次数量',
          data: sortedValues,
          borderColor: 'rgba(16, 185, 129, 1)',
          backgroundColor: 'rgba(16, 185, 129, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4,
          pointRadius: 4,
          pointHoverRadius: 6
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: true,
        onClick: function(event, elements) {
          if (elements.length > 0) {
            var index = elements[0].index;
            var priceRange = sortedLabels[index];
            showDetails('price', priceRange);
          }
        },
        plugins: {
          legend: {
            display: false
          },
          tooltip: {
            callbacks: {
              label: function(context) {
                return context.parsed.y + ' 场';
              }
            }
          }
        },
        scales: {
          y: {
            beginAtZero: true,
            ticks: {
              stepSize: 1
            }
          },
          x: {
            ticks: {
              maxRotation: 45,
              minRotation: 45
            }
          }
        }
      }
    });
  }

  /**
   * 生成颜色数组
   */
  function generateColors(count) {
    var colors = [
      'rgba(59, 130, 246, 0.8)',   // 蓝色
      'rgba(16, 185, 129, 0.8)',   // 绿色
      'rgba(245, 158, 11, 0.8)',   // 黄色
      'rgba(239, 68, 68, 0.8)',    // 红色
      'rgba(139, 92, 246, 0.8)',   // 紫色
      'rgba(236, 72, 153, 0.8)',   // 粉色
      'rgba(20, 184, 166, 0.8)',   // 青色
      'rgba(251, 146, 60, 0.8)',   // 橙色
      'rgba(99, 102, 241, 0.8)',   // 靛蓝
      'rgba(168, 85, 247, 0.8)'    // 紫罗兰
    ];
    
    var result = [];
    for (var i = 0; i < count; i++) {
      result.push(colors[i % colors.length]);
    }
    return result;
  }

  /**
   * 显示详情弹窗
   */
  function showDetails(type, value) {
    // 创建或显示详情模态框
    var modal = $('#musicalbum-details-modal');
    if (modal.length === 0) {
      modal = $('<div id="musicalbum-details-modal" class="musicalbum-modal"><div class="musicalbum-modal-content"><span class="musicalbum-modal-close">&times;</span><h3 class="musicalbum-modal-title"></h3><div class="musicalbum-modal-body"></div></div></div>');
      $('body').append(modal);
      
      // 关闭按钮
      modal.find('.musicalbum-modal-close').on('click', function() {
        modal.hide();
      });
      
      // 点击外部关闭
      modal.on('click', function(e) {
        if ($(e.target).is('.musicalbum-modal')) {
          modal.hide();
        }
      });
    }
    
    var title = '';
    if (type === 'category') title = '类别：' + value;
    else if (type === 'cast') title = '演员：' + value;
    else if (type === 'price') title = '票价区间：' + value;
    
    modal.find('.musicalbum-modal-title').text(title);
    modal.find('.musicalbum-modal-body').html('<div class="musicalbum-loading">加载中...</div>');
    modal.show();
    
    // 加载详情数据
    $.ajax({
      url: ViewingRecords.rest.statisticsDetails,
      method: 'GET',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce },
      data: {
        type: type,
        value: value,
        per_page: 50
      }
    }).done(function(response) {
      var html = '<div class="musicalbum-details-list">';
      if (response.data && response.data.length > 0) {
        response.data.forEach(function(item) {
          html += '<div class="musicalbum-details-item">';
          html += '<h4><a href="' + item.url + '" target="_blank">' + item.title + '</a></h4>';
          html += '<div class="musicalbum-details-meta">';
          if (item.category) html += '<span>类别：' + item.category + '</span>';
          if (item.theater) html += '<span>剧院：' + item.theater + '</span>';
          if (item.cast) html += '<span>卡司：' + item.cast + '</span>';
          if (item.price) html += '<span>票价：' + item.price + '</span>';
          if (item.view_date) html += '<span>日期：' + item.view_date + '</span>';
          html += '</div></div>';
        });
        if (response.total > response.data.length) {
          html += '<div class="musicalbum-details-more">共 ' + response.total + ' 条记录，显示前 ' + response.data.length + ' 条</div>';
        }
      } else {
        html += '<div class="musicalbum-details-empty">暂无数据</div>';
      }
      html += '</div>';
      modal.find('.musicalbum-modal-body').html(html);
    }).fail(function() {
      modal.find('.musicalbum-modal-body').html('<div class="musicalbum-details-error">加载失败，请稍后重试</div>');
    });
  }

  /**
   * 导出统计数据
   */
  function exportStatistics() {
    // 移除已存在的菜单
    $('.musicalbum-export-menu').remove();
    
    // 创建导出选项菜单
    var btn = $('#musicalbum-export-btn');
    if (btn.length === 0) return;
    
    var menu = $('<div class="musicalbum-export-menu">' +
      '<div class="musicalbum-export-section"><strong>导出数据</strong></div>' +
      '<a href="#" data-type="data" data-format="csv">导出为 CSV</a>' +
      '<a href="#" data-type="data" data-format="json">导出为 JSON</a>' +
      '<div class="musicalbum-export-section"><strong>导出图表</strong></div>' +
      '<a href="#" data-type="chart" data-chart="category">导出类别分布图</a>' +
      '<a href="#" data-type="chart" data-chart="cast">导出演员频率图</a>' +
      '<a href="#" data-type="chart" data-chart="price">导出票价分布图</a>' +
      '<a href="#" data-type="chart" data-chart="all">导出所有图表</a>' +
      '</div>');
    
    var btnOffset = btn.offset();
    if (btnOffset) {
      menu.css({
        position: 'absolute',
        top: btnOffset.top + btn.outerHeight() + 5,
        left: btnOffset.left,
        background: '#fff',
        border: '1px solid #ddd',
        borderRadius: '4px',
        boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
        padding: '8px 0',
        zIndex: 10000,
        minWidth: '180px'
      });
    }
    
    menu.find('a').on('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      var type = $(this).data('type');
      var format = $(this).data('format');
      var chart = $(this).data('chart');
      
      if (type === 'data') {
        // 导出数据
        if (ViewingRecords && ViewingRecords.rest && ViewingRecords.rest.statisticsExport) {
          var url = ViewingRecords.rest.statisticsExport + '?format=' + format + '&_wpnonce=' + ViewingRecords.rest.nonce;
          window.location.href = url;
        } else {
          alert('导出功能暂时不可用，请刷新页面后重试');
        }
      } else if (type === 'chart') {
        // 导出图表
        exportChart(chart);
      }
      
      menu.remove();
    });
    
    // 点击外部关闭
    setTimeout(function() {
      $(document).one('click', function(e) {
        if (!$(e.target).closest('.musicalbum-export-menu, #musicalbum-export-btn').length) {
          menu.remove();
        }
      });
    }, 100);
    
    $('body').append(menu);
  }

  /**
   * 导出图表为图片
   */
  function exportChart(chartType) {
    if (!chartInstances || typeof Chart === 'undefined') {
      alert('图表尚未加载完成，请稍后再试');
      return;
    }
    
    if (chartType === 'all') {
      // 导出所有图表
      var charts = ['category', 'cast', 'price'];
      var chartNames = {
        'category': '剧目类别分布',
        'cast': '演员出场频率',
        'price': '票价区间分布'
      };
      
      charts.forEach(function(chartName, index) {
        setTimeout(function() {
          exportSingleChart(chartName, chartNames[chartName]);
        }, index * 500); // 延迟导出，避免浏览器阻止多个下载
      });
    } else {
      // 导出单个图表
      var chartNames = {
        'category': '剧目类别分布',
        'cast': '演员出场频率',
        'price': '票价区间分布'
      };
      exportSingleChart(chartType, chartNames[chartType] || chartType);
    }
  }

  /**
   * 导出单个图表
   */
  function exportSingleChart(chartType, chartName) {
    var chart = chartInstances[chartType];
    if (!chart) {
      alert('图表 "' + chartName + '" 尚未加载');
      return;
    }
    
    try {
      // 使用Chart.js的toBase64Image方法
      var url = chart.toBase64Image('image/png', 1);
      var link = document.createElement('a');
      link.download = '观演统计_' + chartName + '_' + new Date().toISOString().slice(0, 10) + '.png';
      link.href = url;
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    } catch (e) {
      console.error('导出图表失败:', e);
      alert('导出图表失败，请稍后重试');
    }
  }

  // ==================== 观演记录管理模块 ====================
  
  // 初始化管理界面
  if ($('.musicalbum-manager-container').length > 0) {
    initViewingManager();
  }

  function initViewingManager() {
    // 视图切换
    $('.musicalbum-view-btn').on('click', function() {
      var view = $(this).data('view');
      $('.musicalbum-view-btn').removeClass('active');
      $(this).addClass('active');
      $('.musicalbum-view-content').removeClass('active');
      if (view === 'list') {
        $('#musicalbum-list-view').addClass('active');
        loadListView();
      } else {
        $('#musicalbum-calendar-view').addClass('active');
        initCalendarView();
      }
    });

    // 表单标签切换
    $('.musicalbum-tab-btn').on('click', function() {
      var tab = $(this).data('tab');
      $('.musicalbum-tab-btn').removeClass('active');
      $(this).addClass('active');
      $('.musicalbum-tab-content').removeClass('active');
      $('#musicalbum-tab-' + tab).addClass('active');
    });

    // 新增按钮
    $('#musicalbum-add-btn').on('click', function() {
      resetForm();
      $('#musicalbum-form-title').text('新增观演记录');
      $('#musicalbum-form-modal').show();
    });

    // 关闭模态框
    $('.musicalbum-modal-close, #musicalbum-form-cancel, #musicalbum-ocr-cancel').on('click', function() {
      $('#musicalbum-form-modal').hide();
      resetForm();
    });

    // 点击外部关闭
    $(document).on('click', '#musicalbum-form-modal', function(e) {
      if ($(e.target).is('#musicalbum-form-modal')) {
        $(this).hide();
        resetForm();
      }
    });

    // 手动录入表单提交
    $('#musicalbum-manual-form').on('submit', function(e) {
      e.preventDefault();
      saveViewing($(this));
    });

    // OCR识别
    $('#musicalbum-ocr-manager-button').on('click', function() {
      console.log('=== OCR识别开始 ===');
      var file = $('#musicalbum-ocr-manager-file')[0].files[0];
      if (!file) {
        console.warn('OCR: 未选择文件');
        alert('请先选择图片文件');
        return;
      }
      
      console.log('OCR: 文件已选择', file.name, file.size + ' bytes');
      
      var $btn = $(this);
      var originalText = $btn.text();
      $btn.prop('disabled', true).text('识别中...');
      
      // 显示预览
      var reader = new FileReader();
      reader.onload = function(e) {
        $('#musicalbum-ocr-preview').html('<img src="' + e.target.result + '" alt="预览" style="max-width:100%;max-height:300px;border-radius:6px;margin-top:1rem;">');
      };
      reader.readAsDataURL(file);
      
      var fd = new FormData();
      fd.append('image', file);
      
      console.log('OCR: 发送请求到', ViewingRecords.rest.ocr);
      
      $.ajax({
        url: ViewingRecords.rest.ocr,
        method: 'POST',
        headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce },
        data: fd,
        processData: false,
        contentType: false
      }).done(function(res) {
        console.log('=== OCR API响应 ===');
        console.log('完整响应对象:', res);
        console.log('响应类型:', typeof res);
        console.log('响应是否为数组:', Array.isArray(res));
        
        $btn.prop('disabled', false).text(originalText);
        
        if (res) {
          console.log('OCR: 提取的字段值:');
          console.log('  - title:', res.title);
          console.log('  - theater:', res.theater);
          console.log('  - cast:', res.cast);
          console.log('  - price:', res.price);
          console.log('  - view_date:', res.view_date);
          
          if (res._debug_text) {
            console.log('OCR: 原始识别文本:');
            console.log(res._debug_text);
          }
          
          if (res._debug_message) {
            console.error('OCR: 错误信息:', res._debug_message);
            // 显示详细的错误提示
            var errorMsg = 'OCR识别失败\n\n' + res._debug_message;
            if (res._debug_text) {
              errorMsg += '\n\n识别到的原始文本：\n' + res._debug_text;
            }
            // 如果是API未配置的错误，提供配置说明
            if (res._debug_message.indexOf('未配置') !== -1) {
              errorMsg += '\n\n配置方法：\n';
              errorMsg += '1. 登录WordPress数据库（phpMyAdmin）\n';
              errorMsg += '2. 在 wp_options 表中添加以下选项：\n';
              errorMsg += '   - musicalbum_baidu_api_key (百度OCR API Key)\n';
              errorMsg += '   - musicalbum_baidu_secret_key (百度OCR Secret Key)\n';
              errorMsg += '   或\n';
              errorMsg += '   - musicalbum_aliyun_api_key (阿里云OCR API Key)\n';
              errorMsg += '   - musicalbum_aliyun_endpoint (阿里云OCR端点URL)';
            }
            alert(errorMsg);
            // 如果有错误消息，不继续处理，直接返回
            return;
          }
          
          // 填充表单字段
          if (res.title) {
            $('#musicalbum-ocr-title').val(res.title);
            console.log('✓ 已填充标题:', res.title);
          }
          if (res.theater) {
            $('#musicalbum-ocr-theater').val(res.theater);
            console.log('✓ 已填充剧院:', res.theater);
          }
          if (res.cast) {
            $('#musicalbum-ocr-cast').val(res.cast);
            console.log('✓ 已填充卡司:', res.cast);
          }
          if (res.price) {
            $('#musicalbum-ocr-price').val(res.price);
            console.log('✓ 已填充票价:', res.price);
          }
          if (res.view_date) {
            $('#musicalbum-ocr-date').val(res.view_date);
        $('#musicalbum-ocr-date-picker').val(res.view_date);
            console.log('✓ 已填充日期:', res.view_date);
          }
          $('#musicalbum-ocr-form').show();
          
          // 检查是否识别到任何有效数据
          var hasData = !!(res.title || res.theater || res.cast || res.price || res.view_date);
          console.log('OCR: 是否识别到有效数据:', hasData);
          
          if (hasData) {
            console.log('✓ OCR识别成功！已填充表单字段');
          } else {
            console.warn('⚠ OCR识别完成，但未提取到有效字段');
            // 显示更详细的错误信息
            var errorMsg = '未能识别到有效信息，请检查图片或手动填写';
            if (res._debug_text) {
              errorMsg += '\n\n识别到的原始文本：\n' + res._debug_text;
              console.log('OCR原始文本:', res._debug_text);
            }
            alert(errorMsg);
          }
        } else {
          console.error('OCR: 响应为空或无效');
          alert('识别失败，请检查图片或稍后重试');
        }
        console.log('=== OCR识别结束 ===');
      }).fail(function(xhr, status, error) {
        console.error('=== OCR API请求失败 ===');
        console.error('状态:', status);
        console.error('错误:', error);
        console.error('XHR对象:', xhr);
        console.error('响应状态码:', xhr.status);
        console.error('响应文本:', xhr.responseText);
        
        $btn.prop('disabled', false).text(originalText);
        var errorMsg = '识别失败';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
          console.error('错误消息:', xhr.responseJSON.message);
        } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.status) {
          errorMsg = '识别失败 (状态码: ' + xhr.responseJSON.data.status + ')';
        }
        console.error('OCR识别错误:', xhr);
        alert(errorMsg);
        console.log('=== OCR识别结束（失败）===');
      });
    });

    // OCR表单提交
    $('#musicalbum-ocr-form').on('submit', function(e) {
      e.preventDefault();
      saveViewing($(this));
    });

    // 搜索和过滤
    $('#musicalbum-search-input, #musicalbum-filter-category, #musicalbum-sort-by').on('change input', function() {
      loadListView();
    });

    // 初始加载列表视图
    loadListView();
    
    // 初始化表单中的日期输入框（手动录入和OCR识别）
    initFormDateInputs();
    
    // 初始化图片上传预览
    initImageUpload();
  }
  
  // 初始化图片上传和预览
  function initImageUpload() {
    // 手动录入表单的图片上传
    $('#musicalbum-form-ticket-image').on('change', function() {
      handleImageUpload(this, '#musicalbum-form-ticket-preview', '#musicalbum-form-ticket-image-id');
    });
    
    // OCR识别表单的图片上传
    $('#musicalbum-ocr-ticket-image').on('change', function() {
      handleImageUpload(this, '#musicalbum-ocr-ticket-preview', '#musicalbum-ocr-ticket-image-id');
    });
  }
  
  // 处理图片上传和预览
  function handleImageUpload(input, previewSelector, imageIdSelector) {
    if (input.files && input.files[0]) {
      var file = input.files[0];
      var reader = new FileReader();
      
      reader.onload = function(e) {
        var preview = $(previewSelector);
        preview.html('<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px; border-radius: 4px; border: 1px solid #e5e7eb;" />');
      };
      
      reader.readAsDataURL(file);
      
      // 上传图片到服务器
      var formData = new FormData();
      formData.append('file', file);
      
      $.ajax({
        url: ViewingRecords.rest.uploadImage,
        method: 'POST',
        headers: {
          'X-WP-Nonce': ViewingRecords.rest.nonce
        },
        processData: false,
        contentType: false,
        data: formData
      }).done(function(res) {
        $(imageIdSelector).val(res.id);
      }).fail(function(xhr) {
        console.error('图片上传失败:', xhr);
        alert('图片上传失败，请重试');
      });
    }
  }
  
  // 初始化表单日期输入框（支持手动输入和选择）
  function initFormDateInputs() {
    // 手动录入表单的日期输入框
    initDateInput('#musicalbum-form-date', '#musicalbum-form-date-picker');
    
    // OCR识别表单的日期输入框
    initDateInput('#musicalbum-ocr-date', '#musicalbum-ocr-date-picker');
    
    // 添加时间输入框的实时验证
    initTimeValidation('#musicalbum-form-time-start', '#musicalbum-form-time-end');
    initTimeValidation('#musicalbum-ocr-time-start', '#musicalbum-ocr-time-end');
  }

  // 初始化时间输入框的实时验证
  function initTimeValidation(startSelector, endSelector) {
    var $start = $(startSelector);
    var $end = $(endSelector);
    
    function validateTime() {
      var startVal = $start.val();
      var endVal = $end.val();
      
      // 清除之前的错误样式
      $start.css('border-color', '');
      $end.css('border-color', '');
      
      // 如果两个时间都填写了，进行验证
      if (startVal && endVal) {
        var startMinutes = timeToMinutes(startVal);
        var endMinutes = timeToMinutes(endVal);
        
        if (startMinutes >= endMinutes) {
          // 显示错误样式
          $start.css('border-color', '#ef4444');
          $end.css('border-color', '#ef4444');
          return false;
        }
      }
      return true;
    }
    
    // 当任一时间输入框改变时，进行验证
    $start.on('change blur', validateTime);
    $end.on('change blur', validateTime);
  }

  // 将时间字符串（HH:MM格式）转换为分钟数，便于比较
  function timeToMinutes(timeStr) {
    if (!timeStr || !timeStr.match(/^\d{2}:\d{2}$/)) {
      return 0;
    }
    var parts = timeStr.split(':');
    var hours = parseInt(parts[0], 10);
    var minutes = parseInt(parts[1], 10);
    return hours * 60 + minutes;
  }
  
  // 初始化单个日期输入框
  function initDateInput(textInputSelector, datePickerSelector) {
    var $textInput = $(textInputSelector);
    var $datePicker = $(datePickerSelector);
    var $iconBtn = $textInput.siblings('.musicalbum-calendar-icon-btn');
    
    if ($textInput.length === 0 || $datePicker.length === 0) {
      return;
    }
    
    // 验证和格式化日期
    function validateAndFormatDate(dateStr) {
      if (!dateStr) return null;
      
      // 支持多种格式：YYYY-MM-DD, YYYY/MM/DD, YYYY.MM.DD
      var datePattern = /^(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})$/;
      var match = dateStr.trim().match(datePattern);
      
      if (!match) {
        return null;
      }
      
      var year = parseInt(match[1]);
      var month = parseInt(match[2]);
      var day = parseInt(match[3]);
      
      // 验证日期有效性
      if (year < 1900 || year > 2100) {
        return null;
      }
      
      var date = new Date(year, month - 1, day);
      if (date.getFullYear() === year && 
          date.getMonth() === month - 1 && 
          date.getDate() === day) {
        // 格式化为标准格式
        return year + '-' + 
               String(month).padStart(2, '0') + '-' + 
               String(day).padStart(2, '0');
      }
      
      return null;
    }
    
    // 文本输入框：支持直接输入日期
    $textInput.on('change blur', function() {
      var dateStr = $(this).val();
      var formattedDate = validateAndFormatDate(dateStr);
      
      if (formattedDate) {
        $(this).val(formattedDate);
        $datePicker.val(formattedDate);
      } else if (dateStr) {
        alert('日期格式不正确，请使用 YYYY-MM-DD 格式（如：2025-12-17）');
        $(this).focus();
      }
    });
    
    // 支持回车键验证
    $textInput.on('keypress', function(e) {
      if (e.which === 13) { // Enter键
        e.preventDefault();
        $(this).trigger('change');
      }
    });
    
    // 日期选择器改变时，同步到文本输入框
    $datePicker.on('change', function() {
      var dateStr = $(this).val();
      if (dateStr) {
        $textInput.val(dateStr);
      }
    });
    
    // 日历图标按钮：点击后弹出日期选择器
    $iconBtn.on('click', function(e) {
      e.preventDefault();
      if ($datePicker[0].showPicker) {
        $datePicker[0].showPicker();
      } else {
        // 如果不支持showPicker，直接触发点击
        $datePicker[0].click();
      }
    });
    
    // 点击输入框右侧区域时，也可以触发日期选择器
    $textInput.on('click', function(e) {
      var input = this;
      var clickX = e.pageX - $(input).offset().left;
      var inputWidth = $(input).outerWidth();
      
      // 如果点击在右侧20%区域，触发日期选择器
      if (clickX > inputWidth * 0.8) {
        if ($datePicker[0].showPicker) {
          $datePicker[0].showPicker();
        } else {
          $datePicker[0].click();
        }
      }
    });
  }

  // 加载列表视图
  function loadListView() {
    var container = $('#musicalbum-list-container');
    container.html('<div class="musicalbum-loading">加载中...</div>');

    var params = {
      search: $('#musicalbum-search-input').val(),
      category: $('#musicalbum-filter-category').val(),
      sort: $('#musicalbum-sort-by').val()
    };

    $.ajax({
      url: ViewingRecords.rest.viewings,
      method: 'GET',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce },
      data: params
    }).done(function(data) {
      if (data && data.length > 0) {
        var html = '<div class="musicalbum-list-items">';
        data.forEach(function(item) {
          html += '<div class="musicalbum-list-item" data-id="' + item.id + '">';
          
          // 主要信息区域（默认显示：标题和类型）
          html += '<div class="musicalbum-item-main">';
          html += '<div class="musicalbum-item-title-row">';
          html += '<h4><a href="' + item.url + '" target="_blank">' + escapeHtml(item.title) + '</a></h4>';
          if (item.category) {
            html += '<span class="musicalbum-meta-tag">' + escapeHtml(item.category) + '</span>';
          }
          html += '</div>';
          html += '<div class="musicalbum-item-actions">';
          html += '<button type="button" class="musicalbum-btn-icon musicalbum-btn-edit" data-id="' + item.id + '" title="编辑">✏️</button>';
          html += '<button type="button" class="musicalbum-btn-icon musicalbum-btn-delete" data-id="' + item.id + '" title="删除">🗑️</button>';
          html += '<button type="button" class="musicalbum-btn-toggle" data-id="' + item.id + '" title="展开详情">▼</button>';
          html += '</div>';
          html += '</div>';
          
          // 详细信息区域（默认隐藏，可展开）
          html += '<div class="musicalbum-item-details" id="details-' + item.id + '" style="display:none;">';
          html += '<div class="musicalbum-item-meta">';
          if (item.theater) {
            html += '<span>剧院：' + escapeHtml(item.theater) + '</span>';
          }
          if (item.cast) {
            html += '<span>卡司：' + escapeHtml(item.cast) + '</span>';
          }
          if (item.price) {
            html += '<span>票价：' + escapeHtml(item.price) + '</span>';
          }
          if (item.view_date) {
            var dateTimeStr = escapeHtml(item.view_date);
            if (item.view_time_start || item.view_time_end) {
              var timeStr = '';
              if (item.view_time_start && item.view_time_end) {
                timeStr = escapeHtml(item.view_time_start) + ' - ' + escapeHtml(item.view_time_end);
              } else if (item.view_time_start) {
                timeStr = escapeHtml(item.view_time_start) + ' 开始';
              } else if (item.view_time_end) {
                timeStr = escapeHtml(item.view_time_end) + ' 结束';
              }
              if (timeStr) {
                dateTimeStr += ' ' + timeStr;
              }
            }
            html += '<span>日期：' + dateTimeStr + '</span>';
          }
          html += '</div>';
          if (item.notes) {
            html += '<div class="musicalbum-item-notes">' + escapeHtml(item.notes) + '</div>';
          }
          html += '</div>';
          
          html += '</div>';
        });
        html += '</div>';
        container.html(html);

        // 绑定编辑和删除按钮
        $('.musicalbum-btn-edit').on('click', function() {
          var id = $(this).data('id');
          editViewing(id);
        });
        $('.musicalbum-btn-delete').on('click', function() {
          var id = $(this).data('id');
          if (confirm('确定要删除这条记录吗？')) {
            deleteViewing(id);
          }
        });
        
        // 绑定展开/收起按钮（使用off先移除可能存在的旧绑定，避免重复绑定）
        $('.musicalbum-btn-toggle').off('click').on('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          
          var id = $(this).data('id');
          var $details = $('#details-' + id);
          var $btn = $(this);
          
          if ($details.is(':visible')) {
            $details.slideUp(200);
            $btn.html('▼').attr('title', '展开详情');
          } else {
            $details.slideDown(200);
            $btn.html('▲').attr('title', '收起详情');
          }
          
          return false;
        });
      } else {
        container.html('<div class="musicalbum-empty">暂无记录</div>');
      }
    }).fail(function() {
      container.html('<div class="musicalbum-error">加载失败，请稍后重试</div>');
    });
  }

  // 初始化日历视图
  function initCalendarView() {
    var calendarEl = document.getElementById('musicalbum-calendar-container');
    if (!calendarEl || typeof FullCalendar === 'undefined') {
      $('#musicalbum-calendar-container').html('<div class="musicalbum-error">日历组件加载失败</div>');
      return;
    }

    // 如果已经初始化，先销毁
    if (window.viewingCalendar) {
      window.viewingCalendar.destroy();
    }

    // 创建快速导航容器（使用文本输入框避免浏览器限制）
    var navContainer = $('<div class="musicalbum-calendar-nav"></div>');
    // 使用text类型，避免type="date"的浏览器限制
    var dateInput = $('<input type="text" class="musicalbum-calendar-date-input" placeholder="输入日期（YYYY-MM-DD）或点击选择" autocomplete="off">');
    // 创建一个隐藏的date输入框用于日期选择器
    var datePicker = $('<input type="date" class="musicalbum-calendar-date-picker" style="position:absolute;opacity:0;pointer-events:none;width:0;height:0;">');
    
    navContainer.append($('<label class="musicalbum-calendar-nav-label">快速跳转：</label>'));
    navContainer.append($('<div class="musicalbum-calendar-input-wrapper"></div>').append(dateInput).append(datePicker));
    
    // 插入到日历容器前
    $(calendarEl).before(navContainer);

    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      locale: 'zh-cn',
      firstDay: 1, // 周一作为第一天
      headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,listWeek'
      },
      buttonText: {
        today: '今天',
        month: '月',
        week: '周',
        day: '日'
      },
      datesSet: function(dateInfo) {
        // 当日历日期改变时，更新日期输入框的值（显示当前月份的第一天）
        var currentDate = dateInfo.view.currentStart;
        var year = currentDate.getFullYear();
        var month = String(currentDate.getMonth() + 1).padStart(2, '0');
        var day = String(currentDate.getDate()).padStart(2, '0');
        var dateStr = year + '-' + month + '-' + day;
        dateInput.val(dateStr);
        datePicker.val(dateStr);
      },
      events: function(fetchInfo, successCallback, failureCallback) {
        $.ajax({
          url: ViewingRecords.rest.viewings,
          method: 'GET',
          headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce }
        }).done(function(data) {
          var events = [];
          if (data && data.length > 0) {
            // 定义一组美观的彩色（基于类别或ID分配颜色，确保同一记录颜色一致）
            var colors = [
              '#3b82f6', // 蓝色
              '#10b981', // 绿色
              '#f59e0b', // 橙色
              '#ef4444', // 红色
              '#8b5cf6', // 紫色
              '#ec4899', // 粉色
              '#06b6d4', // 青色
              '#84cc16', // 黄绿色
              '#f97316', // 深橙色
              '#6366f1', // 靛蓝色
              '#14b8a6', // 青绿色
              '#a855f7'  // 深紫色
            ];
            
            // 根据类别映射颜色的函数（如果类别相同，颜色也相同）
            var categoryColorMap = {};
            var colorIndex = 0;
            
            function getColorForCategory(category) {
              if (!category) {
                return colors[0]; // 默认蓝色
              }
              if (!categoryColorMap[category]) {
                categoryColorMap[category] = colors[colorIndex % colors.length];
                colorIndex++;
              }
              return categoryColorMap[category];
            }
            
            data.forEach(function(item) {
              if (item.view_date) {
                // 优先使用类别颜色，如果没有类别则使用ID分配颜色
                var eventColor = item.category ? getColorForCategory(item.category) : colors[item.id % colors.length];
                
                var eventData = {
                  id: item.id,
                  title: item.title,
                  backgroundColor: eventColor,
                  borderColor: eventColor,
                  textColor: '#ffffff', // 白色文字
                  extendedProps: {
                    category: item.category,
                    theater: item.theater,
                    cast: item.cast,
                    price: item.price,
                    view_time_start: item.view_time_start,
                    view_time_end: item.view_time_end,
                    url: item.url
                  }
                };
                
                // 如果有开始时间或结束时间，在周视图中显示具体时间
                if (item.view_time_start || item.view_time_end) {
                  // 构建完整的日期时间字符串
                  var startDateTime = item.view_date;
                  if (item.view_time_start) {
                    startDateTime += 'T' + item.view_time_start + ':00';
                  } else {
                    startDateTime += 'T00:00:00';
                  }
                  
                  eventData.start = startDateTime;
                  eventData.allDay = false; // 在周视图中显示具体时间
                  
                  // 如果有结束时间，设置结束时间
                  if (item.view_time_end) {
                    var endDateTime = item.view_date + 'T' + item.view_time_end + ':00';
                    eventData.end = endDateTime;
                  }
                } else {
                  // 没有时间信息，创建全天事件
                  eventData.start = item.view_date;
                  eventData.allDay = true;
                }
                
                events.push(eventData);
              }
            });
          }
          successCallback(events);
        }).fail(function() {
          failureCallback();
        });
      },
      eventClick: function(info) {
        var item = info.event.extendedProps;
        showCalendarEventDetail(info.event.id, info.event.title, item);
      },
      eventDidMount: function(arg) {
        // 在月视图中，将所有事件统一显示为全天事件（不显示时段）
        if (arg.view.type === 'dayGridMonth') {
          // 强制设置为全天事件样式
          if (!arg.event.allDay) {
            // 如果原本不是全天事件，在月视图中也显示为全天样式
            arg.el.classList.add('fc-event-all-day');
            // 移除时间相关的显示
            var timeEl = arg.el.querySelector('.fc-event-time');
            if (timeEl) {
              timeEl.style.display = 'none';
            }
          }
        }
      }
    });
    calendar.render();
    
    // 保存日历实例以便刷新
    window.viewingCalendar = calendar;
    
    // 设置初始值（当前日期）
    var today = new Date();
    var year = today.getFullYear();
    var month = String(today.getMonth() + 1).padStart(2, '0');
    var day = String(today.getDate()).padStart(2, '0');
    var todayStr = year + '-' + month + '-' + day;
    dateInput.val(todayStr);
    datePicker.val(todayStr);
    
    // 验证和格式化日期
    function validateAndFormatDate(dateStr) {
      if (!dateStr) return null;
      
      // 支持多种格式：YYYY-MM-DD, YYYY/MM/DD, YYYY.MM.DD
      var datePattern = /^(\d{4})[-\/\.](\d{1,2})[-\/\.](\d{1,2})$/;
      var match = dateStr.trim().match(datePattern);
      
      if (!match) {
        return null;
      }
      
      var year = parseInt(match[1]);
      var month = parseInt(match[2]);
      var day = parseInt(match[3]);
      
      // 验证日期有效性
      if (year < 1900 || year > 2100) {
        return null;
      }
      
      var date = new Date(year, month - 1, day);
      if (date.getFullYear() === year && 
          date.getMonth() === month - 1 && 
          date.getDate() === day) {
        // 格式化为标准格式
        return year + '-' + 
               String(month).padStart(2, '0') + '-' + 
               String(day).padStart(2, '0');
      }
      
      return null;
    }
    
    // 文本输入框：支持直接输入日期
    dateInput.on('change blur', function() {
      var dateStr = $(this).val();
      var formattedDate = validateAndFormatDate(dateStr);
      
      if (formattedDate) {
        $(this).val(formattedDate);
        datePicker.val(formattedDate);
        calendar.gotoDate(formattedDate);
      } else if (dateStr) {
        alert('日期格式不正确，请使用 YYYY-MM-DD 格式（如：2025-12-17）');
        $(this).focus();
      }
    });
    
    // 支持回车键跳转
    dateInput.on('keypress', function(e) {
      if (e.which === 13) { // Enter键
        e.preventDefault();
        $(this).trigger('change');
      }
    });
    
    // 点击输入框右侧区域时，触发日期选择器
    dateInput.on('click', function(e) {
      // 如果点击的是输入框右侧区域，触发日期选择器
      var input = this;
      var clickX = e.pageX - $(input).offset().left;
      var inputWidth = $(input).outerWidth();
      
      // 如果点击在右侧20%区域，触发日期选择器
      if (clickX > inputWidth * 0.8) {
        datePicker[0].showPicker();
      }
    });
    
    // 日期选择器改变时，同步到文本输入框
    datePicker.on('change', function() {
      var dateStr = $(this).val();
      if (dateStr) {
        dateInput.val(dateStr);
        calendar.gotoDate(dateStr);
      }
    });
    
    // 添加一个日历图标按钮
    var calendarIcon = $('<button type="button" class="musicalbum-calendar-icon-btn" title="选择日期">📅</button>');
    calendarIcon.on('click', function(e) {
      e.preventDefault();
      datePicker[0].showPicker();
    });
    
    // 将图标按钮添加到输入框容器中
    navContainer.find('.musicalbum-calendar-input-wrapper').append(calendarIcon);
  }

  // 显示日历事件详情
  function showCalendarEventDetail(id, title, props) {
    var modal = $('#musicalbum-calendar-detail-modal');
    if (modal.length === 0) {
      modal = $('<div id="musicalbum-calendar-detail-modal" class="musicalbum-modal"><div class="musicalbum-modal-content"><span class="musicalbum-modal-close">&times;</span><div class="musicalbum-modal-body"></div></div></div>');
      $('body').append(modal);
      modal.find('.musicalbum-modal-close').on('click', function() {
        modal.hide();
      });
      modal.on('click', function(e) {
        if ($(e.target).is('#musicalbum-calendar-detail-modal')) {
          modal.hide();
        }
      });
    }
    
    // 先获取完整记录信息（包含时间）
    $.ajax({
      url: ViewingRecords.rest.viewings + '/' + id,
      method: 'GET',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce }
    }).done(function(item) {
      var html = '<h3><a href="' + (item.url || props.url) + '" target="_blank">' + escapeHtml(title) + '</a></h3>';
      if (item.category || props.category) html += '<p><strong>类别：</strong>' + escapeHtml(item.category || props.category) + '</p>';
      if (item.theater || props.theater) html += '<p><strong>剧院：</strong>' + escapeHtml(item.theater || props.theater) + '</p>';
      if (item.cast || props.cast) html += '<p><strong>卡司：</strong>' + escapeHtml(item.cast || props.cast) + '</p>';
      if (item.price || props.price) html += '<p><strong>票价：</strong>' + escapeHtml(item.price || props.price) + '</p>';
      if (item.view_date) {
        var dateTimeStr = escapeHtml(item.view_date);
        if (item.view_time_start || item.view_time_end) {
          var timeStr = '';
          if (item.view_time_start && item.view_time_end) {
            timeStr = escapeHtml(item.view_time_start) + ' - ' + escapeHtml(item.view_time_end);
          } else if (item.view_time_start) {
            timeStr = escapeHtml(item.view_time_start) + ' 开始';
          } else if (item.view_time_end) {
            timeStr = escapeHtml(item.view_time_end) + ' 结束';
          }
          if (timeStr) {
            dateTimeStr += ' ' + timeStr;
          }
        }
        html += '<p><strong>观演时间：</strong>' + dateTimeStr + '</p>';
      }
      html += '<div class="musicalbum-calendar-actions" style="margin-top:1rem;">';
      html += '<button type="button" class="musicalbum-btn musicalbum-btn-sm musicalbum-btn-edit" data-id="' + id + '">编辑</button>';
      html += '<button type="button" class="musicalbum-btn musicalbum-btn-sm musicalbum-btn-delete" data-id="' + id + '">删除</button>';
      html += '</div>';
      
      modal.find('.musicalbum-modal-body').html(html);
      modal.show();
      
      // 绑定事件
      modal.find('.musicalbum-btn-edit').on('click', function() {
        modal.hide();
        editViewing(id);
      });
      modal.find('.musicalbum-btn-delete').on('click', function() {
        if (confirm('确定要删除这条记录吗？')) {
          deleteViewing(id);
          modal.hide();
        }
      });
    }).fail(function() {
      // 如果获取失败，使用props中的信息
      var html = '<h3><a href="' + props.url + '" target="_blank">' + escapeHtml(title) + '</a></h3>';
      if (props.category) html += '<p><strong>类别：</strong>' + escapeHtml(props.category) + '</p>';
      if (props.theater) html += '<p><strong>剧院：</strong>' + escapeHtml(props.theater) + '</p>';
      if (props.cast) html += '<p><strong>卡司：</strong>' + escapeHtml(props.cast) + '</p>';
      if (props.price) html += '<p><strong>票价：</strong>' + escapeHtml(props.price) + '</p>';
      html += '<div class="musicalbum-calendar-actions" style="margin-top:1rem;">';
      html += '<button type="button" class="musicalbum-btn musicalbum-btn-sm musicalbum-btn-edit" data-id="' + id + '">编辑</button>';
      html += '<button type="button" class="musicalbum-btn musicalbum-btn-sm musicalbum-btn-delete" data-id="' + id + '">删除</button>';
      html += '</div>';
      
      modal.find('.musicalbum-modal-body').html(html);
      modal.show();
      
      // 绑定事件
      modal.find('.musicalbum-btn-edit').on('click', function() {
        modal.hide();
        editViewing(id);
      });
      modal.find('.musicalbum-btn-delete').on('click', function() {
        if (confirm('确定要删除这条记录吗？')) {
          deleteViewing(id);
          modal.hide();
        }
      });
    });
    
    // 绑定事件
    modal.find('.musicalbum-btn-edit').on('click', function() {
      modal.hide();
      editViewing(id);
    });
    modal.find('.musicalbum-btn-delete').on('click', function() {
      if (confirm('确定要删除这条记录吗？')) {
        deleteViewing(id);
        modal.hide();
      }
    });
  }

  // 保存观演记录
  function saveViewing($form) {
    var formData = {};
    $form.find('input, select, textarea').each(function() {
      var $el = $(this);
      if ($el.attr('name') && $el.attr('name') !== 'id' && $el.attr('type') !== 'file') {
        formData[$el.attr('name')] = $el.val();
      }
    });

    // 验证开始时间和结束时间
    var timeStart = formData.view_time_start;
    var timeEnd = formData.view_time_end;
    if (timeStart && timeEnd) {
      // 将时间字符串转换为可比较的格式（HH:MM -> 分钟数）
      var startMinutes = timeToMinutes(timeStart);
      var endMinutes = timeToMinutes(timeEnd);
      
      if (startMinutes >= endMinutes) {
        alert('开始时间不能晚于或等于结束时间，请检查后重试');
        // 高亮显示错误的时间输入框
        $('#musicalbum-form-time-start, #musicalbum-ocr-time-start').css('border-color', '#ef4444');
        $('#musicalbum-form-time-end, #musicalbum-ocr-time-end').css('border-color', '#ef4444');
        setTimeout(function() {
          $('#musicalbum-form-time-start, #musicalbum-ocr-time-start').css('border-color', '');
          $('#musicalbum-form-time-end, #musicalbum-ocr-time-end').css('border-color', '');
        }, 3000);
        return;
      }
    }

    var id = $('#musicalbum-edit-id').val();
    var url = ViewingRecords.rest.viewings;
    var method = 'POST';

    if (id) {
      url += '/' + id;
      method = 'PUT';
    }

    // 如果有图片需要上传，先上传图片
    var ticketImageInput = $form.closest('.musicalbum-modal-content').find('input[type="file"][name="ticket_image"]');
    var ticketImageId = $form.closest('.musicalbum-modal-content').find('input[type="hidden"][name="ticket_image_id"]').val();
    
    if (ticketImageInput.length && ticketImageInput[0].files.length > 0) {
      // 上传图片
      var formDataUpload = new FormData();
      formDataUpload.append('file', ticketImageInput[0].files[0]);
      
      $.ajax({
        url: ViewingRecords.rest.uploadImage,
        method: 'POST',
        headers: {
          'X-WP-Nonce': ViewingRecords.rest.nonce
        },
        processData: false,
        contentType: false,
        data: formDataUpload
      }).done(function(imageRes) {
        formData.ticket_image_id = imageRes.id;
        saveViewingData(url, method, formData, id);
      }).fail(function(xhr) {
        var msg = '图片上传失败';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        }
        alert(msg);
      });
    } else if (ticketImageId) {
      // 使用已有的图片ID
      formData.ticket_image_id = ticketImageId;
      saveViewingData(url, method, formData, id);
    } else {
      // 没有图片，直接保存
      saveViewingData(url, method, formData, id);
    }
  }
  
  function saveViewingData(url, method, formData, id) {
    $.ajax({
      url: url,
      method: method,
      headers: {
        'X-WP-Nonce': ViewingRecords.rest.nonce,
        'Content-Type': 'application/json'
      },
      data: JSON.stringify(formData)
    }).done(function(res) {
      alert(id ? '记录更新成功' : '记录创建成功');
      $('#musicalbum-form-modal').hide();
      resetForm();
      loadListView();
      if (window.viewingCalendar) {
        window.viewingCalendar.refetchEvents();
      }
    }).fail(function(xhr) {
      var msg = '保存失败';
      if (xhr.responseJSON && xhr.responseJSON.message) {
        msg = xhr.responseJSON.message;
      }
      alert(msg);
    });
  }

  // 将时间字符串（HH:MM格式）转换为分钟数，便于比较
  function timeToMinutes(timeStr) {
    if (!timeStr || !timeStr.match(/^\d{2}:\d{2}$/)) {
      return 0;
    }
    var parts = timeStr.split(':');
    var hours = parseInt(parts[0], 10);
    var minutes = parseInt(parts[1], 10);
    return hours * 60 + minutes;
  }

  // 编辑观演记录
  function editViewing(id) {
    $.ajax({
      url: ViewingRecords.rest.viewings,
      method: 'GET',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce }
    }).done(function(data) {
      var item = data.find(function(i) { return i.id == id; });
      if (item) {
        $('#musicalbum-edit-id').val(item.id);
        $('#musicalbum-form-title-input').val(item.title);
        $('#musicalbum-form-category').val(item.category || '');
        $('#musicalbum-form-theater').val(item.theater || '');
        $('#musicalbum-form-cast').val(item.cast || '');
        $('#musicalbum-form-price').val(item.price || '');
        $('#musicalbum-form-date').val(item.view_date || '');
        $('#musicalbum-form-date-picker').val(item.view_date || '');
        $('#musicalbum-form-time-start').val(item.view_time_start || '');
        $('#musicalbum-form-time-end').val(item.view_time_end || '');
        $('#musicalbum-form-notes').val(item.notes || '');
        
        // 显示票面图片
        if (item.ticket_image) {
          var imageUrl = typeof item.ticket_image === 'object' ? item.ticket_image.url : item.ticket_image;
          var imageId = typeof item.ticket_image === 'object' ? item.ticket_image.id : '';
          $('#musicalbum-form-ticket-preview').html('<img src="' + imageUrl + '" style="max-width: 200px; max-height: 200px; border-radius: 4px; border: 1px solid #e5e7eb;" />');
          $('#musicalbum-form-ticket-image-id').val(imageId);
        } else {
          $('#musicalbum-form-ticket-preview').empty();
          $('#musicalbum-form-ticket-image-id').val('');
        }
        
        $('#musicalbum-form-title').text('编辑观演记录');
        $('.musicalbum-tab-btn[data-tab="manual"]').click();
        $('#musicalbum-form-modal').show();
      }
    }).fail(function() {
      alert('加载记录失败，请稍后重试');
    });
  }

  // 删除观演记录
  function deleteViewing(id) {
    $.ajax({
      url: ViewingRecords.rest.viewings + '/' + id,
      method: 'DELETE',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce }
    }).done(function() {
      alert('记录删除成功');
      loadListView();
      if (window.viewingCalendar) {
        window.viewingCalendar.refetchEvents();
      }
    }).fail(function() {
      alert('删除失败，请稍后重试');
    });
  }

  // 重置表单
  function resetForm() {
    // 重置日期和时间输入框
    $('#musicalbum-form-date, #musicalbum-ocr-date').val('');
    $('#musicalbum-form-date-picker, #musicalbum-ocr-date-picker').val('');
        $('#musicalbum-form-time-start, #musicalbum-ocr-time-start').val('');
        $('#musicalbum-form-time-end, #musicalbum-ocr-time-end').val('');
        $('#musicalbum-edit-id').val('');
        $('#musicalbum-manual-form')[0].reset();
        $('#musicalbum-ocr-form')[0].reset();
        $('#musicalbum-ocr-form').hide();
        $('#musicalbum-ocr-preview').empty();
        // 清空图片预览和ID
        $('#musicalbum-form-ticket-preview, #musicalbum-ocr-ticket-preview').empty();
        $('#musicalbum-form-ticket-image-id, #musicalbum-ocr-ticket-image-id').val('');
        $('.musicalbum-tab-btn[data-tab="manual"]').click();
  }

  // HTML转义
  function escapeHtml(text) {
    if (!text) return '';
    var map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
  }
})(jQuery);
