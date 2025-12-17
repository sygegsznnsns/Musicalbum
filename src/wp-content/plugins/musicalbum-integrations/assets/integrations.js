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
        url: MusicalbumIntegrations.rest.ocr,
        method: 'POST',
        headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce },
        data: fd,
        processData: false,
        contentType: false
      }).done(function(res){
        try {
          if (window.acf && res) {
            if (res.title) $('input[name="post_title"]').val(res.title);
            if (res.theater && acf.getField('field_malbum_theater')) acf.getField('field_malbum_theater').val(res.theater);
            if (res.cast && acf.getField('field_malbum_cast')) acf.getField('field_malbum_cast').val(res.cast);
            if (res.price && acf.getField('field_malbum_price')) acf.getField('field_malbum_price').val(res.price);
            if (res.view_date && acf.getField('field_malbum_date')) acf.getField('field_malbum_date').val(res.view_date);
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
      url: MusicalbumIntegrations.rest.statistics,
      method: 'GET',
      headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce }
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
      url: MusicalbumIntegrations.rest.statisticsDetails,
      method: 'GET',
      headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce },
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
        if (MusicalbumIntegrations && MusicalbumIntegrations.rest && MusicalbumIntegrations.rest.statisticsExport) {
          var url = MusicalbumIntegrations.rest.statisticsExport + '?format=' + format + '&_wpnonce=' + MusicalbumIntegrations.rest.nonce;
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
      var file = $('#musicalbum-ocr-manager-file')[0].files[0];
      if (!file) {
        alert('请先选择图片文件');
        return;
      }
      
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
      $.ajax({
        url: MusicalbumIntegrations.rest.ocr,
        method: 'POST',
        headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce },
        data: fd,
        processData: false,
        contentType: false
      }).done(function(res) {
        $btn.prop('disabled', false).text(originalText);
        if (res) {
          // 填充表单字段
          if (res.title) $('#musicalbum-ocr-title').val(res.title);
          if (res.theater) $('#musicalbum-ocr-theater').val(res.theater);
          if (res.cast) $('#musicalbum-ocr-cast').val(res.cast);
          if (res.price) $('#musicalbum-ocr-price').val(res.price);
          if (res.view_date) $('#musicalbum-ocr-date').val(res.view_date);
          $('#musicalbum-ocr-form').show();
          
          // 如果识别到数据，显示提示
          if (res.title || res.theater || res.cast || res.price || res.view_date) {
            // 识别成功，不显示提示
            console.log('OCR识别成功:', res);
            // 如果有调试信息，也显示
            if (res._debug_text) {
              console.log('OCR原始文本:', res._debug_text);
            }
          } else {
            // 显示更详细的错误信息
            var errorMsg = '未能识别到有效信息，请检查图片或手动填写';
            if (res._debug_text) {
              errorMsg += '\n\n识别到的原始文本：\n' + res._debug_text;
              console.log('OCR原始文本:', res._debug_text);
            }
            alert(errorMsg);
          }
        } else {
          alert('识别失败，请检查图片或稍后重试');
        }
      }).fail(function(xhr) {
        $btn.prop('disabled', false).text(originalText);
        var errorMsg = '识别失败';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.status) {
          errorMsg = '识别失败 (状态码: ' + xhr.responseJSON.data.status + ')';
        }
        console.error('OCR识别错误:', xhr);
        alert(errorMsg);
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
      url: MusicalbumIntegrations.rest.viewings,
      method: 'GET',
      headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce },
      data: params
    }).done(function(data) {
      if (data && data.length > 0) {
        var html = '<div class="musicalbum-list-items">';
        data.forEach(function(item) {
          html += '<div class="musicalbum-list-item" data-id="' + item.id + '">';
          html += '<div class="musicalbum-item-header">';
          html += '<h4><a href="' + item.url + '" target="_blank">' + escapeHtml(item.title) + '</a></h4>';
          html += '<div class="musicalbum-item-actions">';
          html += '<button type="button" class="musicalbum-btn-icon musicalbum-btn-edit" data-id="' + item.id + '" title="编辑">✏️</button>';
          html += '<button type="button" class="musicalbum-btn-icon musicalbum-btn-delete" data-id="' + item.id + '" title="删除">🗑️</button>';
          html += '</div></div>';
          html += '<div class="musicalbum-item-meta">';
          if (item.category) html += '<span class="musicalbum-meta-tag">' + escapeHtml(item.category) + '</span>';
          if (item.theater) html += '<span>剧院：' + escapeHtml(item.theater) + '</span>';
          if (item.cast) html += '<span>卡司：' + escapeHtml(item.cast) + '</span>';
          if (item.price) html += '<span>票价：' + escapeHtml(item.price) + '</span>';
          if (item.view_date) html += '<span>日期：' + escapeHtml(item.view_date) + '</span>';
          html += '</div>';
          if (item.notes) {
            html += '<div class="musicalbum-item-notes">' + escapeHtml(item.notes) + '</div>';
          }
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
    if (window.musicalbumCalendar) {
      window.musicalbumCalendar.destroy();
    }

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
      events: function(fetchInfo, successCallback, failureCallback) {
        $.ajax({
          url: MusicalbumIntegrations.rest.viewings,
          method: 'GET',
          headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce }
        }).done(function(data) {
          var events = [];
          if (data && data.length > 0) {
            data.forEach(function(item) {
              if (item.view_date) {
                events.push({
                  id: item.id,
                  title: item.title,
                  start: item.view_date,
                  extendedProps: {
                    category: item.category,
                    theater: item.theater,
                    cast: item.cast,
                    price: item.price,
                    url: item.url
                  }
                });
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
      }
    });
    calendar.render();
    
    // 保存日历实例以便刷新
    window.musicalbumCalendar = calendar;
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
  }

  // 保存观演记录
  function saveViewing($form) {
    var formData = {};
    $form.find('input, select, textarea').each(function() {
      var $el = $(this);
      if ($el.attr('name') && $el.attr('name') !== 'id') {
        formData[$el.attr('name')] = $el.val();
      }
    });

    var id = $('#musicalbum-edit-id').val();
    var url = MusicalbumIntegrations.rest.viewings;
    var method = 'POST';

    if (id) {
      url += '/' + id;
      method = 'PUT';
    }

    $.ajax({
      url: url,
      method: method,
      headers: {
        'X-WP-Nonce': MusicalbumIntegrations.rest.nonce,
        'Content-Type': 'application/json'
      },
      data: JSON.stringify(formData)
    }).done(function(res) {
      alert(id ? '记录更新成功' : '记录创建成功');
      $('#musicalbum-form-modal').hide();
      resetForm();
      loadListView();
      if (window.musicalbumCalendar) {
        window.musicalbumCalendar.refetchEvents();
      }
    }).fail(function(xhr) {
      var msg = '保存失败';
      if (xhr.responseJSON && xhr.responseJSON.message) {
        msg = xhr.responseJSON.message;
      }
      alert(msg);
    });
  }

  // 编辑观演记录
  function editViewing(id) {
    $.ajax({
      url: MusicalbumIntegrations.rest.viewings,
      method: 'GET',
      headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce }
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
        $('#musicalbum-form-notes').val(item.notes || '');
        
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
      url: MusicalbumIntegrations.rest.viewings + '/' + id,
      method: 'DELETE',
      headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce }
    }).done(function() {
      alert('记录删除成功');
      loadListView();
      if (window.musicalbumCalendar) {
        window.musicalbumCalendar.refetchEvents();
      }
    }).fail(function() {
      alert('删除失败，请稍后重试');
    });
  }

  // 重置表单
  function resetForm() {
    $('#musicalbum-edit-id').val('');
    $('#musicalbum-manual-form')[0].reset();
    $('#musicalbum-ocr-form')[0].reset();
    $('#musicalbum-ocr-form').hide();
    $('#musicalbum-ocr-preview').empty();
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
