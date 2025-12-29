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
      function initStatistics() {
        if (typeof Chart !== 'undefined') {
          loadStatistics();
        } else {
          // 如果Chart.js还没加载，继续等待
          setTimeout(initStatistics, 100);
        }
      }
      initStatistics();
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
    price: null,
    main: null
  };
  
  // 存储统计数据
  var statisticsData = {};

  /**
   * 加载统计数据并渲染固定图表
   */
  function loadStatistics(callback) {
    // 检查容器是否存在
    if ($('.musicalbum-statistics-container').length === 0) {
      if (callback) callback();
      return;
    }
    
    var loadingEl = $('#musicalbum-statistics-loading');
    if (loadingEl.length > 0) {
      loadingEl.show();
    }

    $.ajax({
      url: ViewingRecords.rest.statistics,
      method: 'GET',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce }
    }).done(function(data) {
      if (loadingEl.length > 0) {
        loadingEl.hide();
      }
      
      // 保存统计数据
      statisticsData = data;
      
      // 销毁旧图表
      if (chartInstances.category) {
        try {
          chartInstances.category.destroy();
        } catch(e) {}
        chartInstances.category = null;
      }
      if (chartInstances.cast) {
        try {
          chartInstances.cast.destroy();
        } catch(e) {}
        chartInstances.cast = null;
      }
      if (chartInstances.price) {
        try {
          chartInstances.price.destroy();
        } catch(e) {}
        chartInstances.price = null;
      }
      
      // 渲染固定的三个图表（确保Chart.js已加载）
      if (typeof Chart !== 'undefined') {
        renderCategoryChart(data.category || {});
        renderCastChart(data.cast || {});
        renderPriceChart(data.price || {});
      }
      
      if (callback) callback();
    }).fail(function(xhr, status, error) {
      if (loadingEl.length > 0) {
        loadingEl.html('加载数据失败，请稍后重试').css('color', '#dc2626');
      }
      console.error('加载统计数据失败:', error);
      if (callback) callback();
    });
  }
  
  /**
   * 生成图表（根据用户选择的数据类型和图表类型）
   * @param {string} dataType - 数据类型
   * @param {string} chartType - 图表类型
   * @param {string} instanceId - 实例ID（用于支持多个图表实例）
   */
  function generateChart(dataType, chartType, instanceId) {
    instanceId = instanceId || '';
    
    if (!statisticsData || Object.keys(statisticsData).length === 0) {
      loadStatistics(function() {
        generateChart(dataType, chartType, instanceId);
      });
      return;
    }
    
    // 获取对应的数据
    var data = statisticsData[dataType] || {};
    
    if (Object.keys(data).length === 0) {
      alert('所选数据类型暂无数据');
      return;
    }
    
    // 销毁旧图表（使用实例ID）
    var chartKey = instanceId ? 'main_' + instanceId : 'main';
    if (chartInstances[chartKey]) {
      chartInstances[chartKey].destroy();
      chartInstances[chartKey] = null;
    }
    
    // 获取图表标题
    var titles = {
      'category': '剧目类别',
      'theater': '剧院',
      'cast': '演员出场频率',
      'price': '票价区间'
    };
    var chartTitle = titles[dataType] || '数据统计';
    
    // 更新标题（使用实例ID）
    var titleSelector = instanceId ? '#musicalbum-chart-title-' + instanceId : '#musicalbum-chart-title';
    $(titleSelector).text(chartTitle);
    
    // 渲染图表
    renderDynamicChart(data, chartType, dataType, instanceId);
  }
  
  /**
   * 动态渲染图表
   * @param {object} data - 图表数据
   * @param {string} chartType - 图表类型
   * @param {string} dataType - 数据类型
   * @param {string} instanceId - 实例ID
   */
  function renderDynamicChart(data, chartType, dataType, instanceId) {
    instanceId = instanceId || '';
    var canvasId = instanceId ? 'musicalbum-chart-main-' + instanceId : 'musicalbum-chart-main';
    var ctx = document.getElementById(canvasId);
    if (!ctx) return;

    var labels = Object.keys(data);
    var values = Object.values(data);
    
    // 对于票价数据，需要排序
    if (dataType === 'price') {
      var sorted = labels.map(function(label, index) {
        return {
          label: label,
          value: values[index],
          sortKey: parseFloat(label.split('-')[0]) || 0
        };
      }).sort(function(a, b) {
        return a.sortKey - b.sortKey;
      });
      labels = sorted.map(function(item) { return item.label; });
      values = sorted.map(function(item) { return item.value; });
    }
    
    // 根据图表类型配置
    var chartConfig = {
      type: chartType,
      data: {
        labels: labels,
        datasets: [{
          label: getDatasetLabel(dataType),
          data: values,
          backgroundColor: generateColors(chartType, labels.length),
          borderColor: chartType === 'line' ? 'rgba(16, 185, 129, 1)' : '#fff',
          borderWidth: chartType === 'line' ? 2 : 2,
          fill: chartType === 'line',
          tension: chartType === 'line' ? 0.4 : 0,
          pointRadius: chartType === 'line' ? 4 : 0,
          pointHoverRadius: chartType === 'line' ? 6 : 0
        }]
      },
      options: getChartOptions(chartType, dataType)
    };
    
    // 保存图表实例（使用实例ID）
    var chartKey = instanceId ? 'main_' + instanceId : 'main';
    chartInstances[chartKey] = new Chart(ctx, chartConfig);
  }
  
  /**
   * 获取数据集标签
   */
  function getDatasetLabel(dataType) {
    var labels = {
      'category': '场次',
      'theater': '场次',
      'cast': '出场次数',
      'price': '场次数量'
    };
    return labels[dataType] || '数量';
  }
  
  /**
   * 获取图表配置选项
   */
  function getChartOptions(chartType, dataType) {
    var baseOptions = {
      responsive: true,
      maintainAspectRatio: true,
      onClick: function(event, elements) {
        if (elements.length > 0) {
          var index = elements[0].index;
          // 通过 this 访问图表实例，获取标签
          var labels = this.data.labels;
          if (labels && labels[index] !== undefined) {
            var value = labels[index];
            showDetails(dataType, value);
          }
        }
      },
      plugins: {
        legend: {
          position: chartType === 'pie' || chartType === 'doughnut' ? 'bottom' : 'top',
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
              var value = context.parsed ? (context.parsed.y || context.parsed) : context.raw || 0;
              
              if (chartType === 'pie' || chartType === 'doughnut') {
                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                var percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                return label + ': ' + value + ' (' + percentage + '%)';
              } else {
                return label + ': ' + value;
              }
            }
          }
        }
      }
    };
    
    // 添加坐标轴配置（非饼图/环形图）
    if (chartType !== 'pie' && chartType !== 'doughnut') {
      baseOptions.scales = {
        y: {
          beginAtZero: true,
          ticks: {
            stepSize: 1
          }
        },
        x: {
          ticks: {
            maxRotation: dataType === 'cast' || dataType === 'theater' ? 45 : 0,
            minRotation: dataType === 'cast' || dataType === 'theater' ? 45 : 0
          }
        }
      };
    }
    
    return baseOptions;
  }
  
  /**
   * 生成颜色（根据图表类型）
   */
  function generateColors(chartType, count) {
    if (chartType === 'pie' || chartType === 'doughnut') {
      return generatePieColors(count);
    } else if (chartType === 'bar') {
      return 'rgba(59, 130, 246, 0.6)';
    } else if (chartType === 'line') {
      return 'rgba(16, 185, 129, 0.1)';
    }
    return generatePieColors(count);
  }
  
  /**
   * 生成饼图颜色
   */
  function generatePieColors(count) {
    var colors = [
      'rgba(59, 130, 246, 0.8)',   // 蓝色
      'rgba(16, 185, 129, 0.8)',   // 绿色
      'rgba(245, 158, 11, 0.8)',    // 黄色
      'rgba(239, 68, 68, 0.8)',     // 红色
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
  
  
  // 生成图表按钮事件（支持多个实例）
  $(document).on('click', '.musicalbum-generate-chart-btn', function(e) {
    e.preventDefault();
    var btn = $(this);
    var instanceId = btn.data('instance-id') || '';
    
    // 获取该实例的选择框
    var dataTypeSelect = $('#musicalbum-data-type-' + instanceId);
    var chartTypeSelect = $('#musicalbum-chart-type-' + instanceId);
    
    var dataType = dataTypeSelect.val();
    var chartType = chartTypeSelect.val();
    
    if (!dataType || !chartType) {
      alert('请选择数据类型和图表类型');
      return;
    }
    
    generateChart(dataType, chartType, instanceId);
  });

  /**
   * 渲染剧目类别分布饼图
   */
  function renderCategoryChart(data) {
    var ctx = document.getElementById('musicalbum-chart-category');
    if (!ctx) return;

    var labels = Object.keys(data);
    var values = Object.values(data);
    
    // 生成颜色
    var colors = generatePieColors(labels.length);

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
   * 生成颜色数组（旧版本，用于兼容旧的图表渲染函数）
   */
  function generateColorsOld(count) {
    return generatePieColors(count);
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
    else if (type === 'theater') title = '剧院：' + value;
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
      '<div class="musicalbum-export-section"><strong>导出固定图表</strong></div>' +
      '<a href="#" data-type="chart" data-chart="category">导出类别分布图</a>' +
      '<a href="#" data-type="chart" data-chart="cast">导出演员频率图</a>' +
      '<a href="#" data-type="chart" data-chart="price">导出票价分布图</a>' +
      '<a href="#" data-type="chart" data-chart="all">导出所有固定图表</a>' +
      '<div class="musicalbum-export-section"><strong>导出自定义图表</strong></div>' +
      '<a href="#" data-type="chart" data-chart="current">导出当前自定义图表</a>' +
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
        if (chart === 'current') {
          exportChart('current');
        } else {
          exportChart(chart);
        }
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
    
    // 导出当前显示的自定义图表（查找第一个存在的实例）
    if (chartType === 'current') {
      var mainChart = chartInstances.main;
      var instanceId = '';
      
      // 查找第一个存在的图表实例
      if (!mainChart) {
        for (var key in chartInstances) {
          if (key.startsWith('main_') && chartInstances[key]) {
            mainChart = chartInstances[key];
            instanceId = key.replace('main_', '');
            break;
          }
        }
      }
      
      if (mainChart) {
        var dataTypeSelect = instanceId ? $('#musicalbum-data-type-' + instanceId) : $('#musicalbum-data-type');
        var chartTypeSelect = instanceId ? $('#musicalbum-chart-type-' + instanceId) : $('#musicalbum-chart-type');
        var dataType = dataTypeSelect.val() || 'category';
        var chartTypeName = chartTypeSelect.val() || 'pie';
        var titles = {
          'category': '剧目类别',
          'theater': '剧院',
          'cast': '演员出场频率',
          'price': '票价区间'
        };
        var chartTitle = titles[dataType] || '数据统计';
        var fileName = chartTitle + '_' + chartTypeName + '_' + new Date().getTime() + '.png';
        
        try {
          var url = mainChart.toBase64Image();
          var link = document.createElement('a');
          link.download = fileName;
          link.href = url;
          document.body.appendChild(link);
          link.click();
          document.body.removeChild(link);
        } catch (e) {
          alert('导出失败：' + e.message);
        }
        return;
      }
    }
    
    // 导出固定图表
    if (chartType === 'all') {
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
    } else if (chartType === 'category' || chartType === 'cast' || chartType === 'price') {
      // 导出单个固定图表
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
      alert('导出图表失败，请稍后重试');
    }
  }

  // ==================== 观演记录管理模块 ====================
  
  // 初始化管理界面
  // 全局事件委托：关闭模态框（支持动态添加的模态框，包括详情页）
  $(document).on('click', '.musicalbum-modal-close, #musicalbum-form-cancel, #musicalbum-ocr-cancel', function() {
    $('#musicalbum-form-modal').hide();
    if (typeof resetForm === 'function') {
      resetForm();
    }
  });

  // 全局事件委托：点击模态框外部关闭
  $(document).on('click', '#musicalbum-form-modal', function(e) {
    if ($(e.target).is('#musicalbum-form-modal')) {
      $(this).hide();
      if (typeof resetForm === 'function') {
        resetForm();
      }
    }
  });

  // 全局事件委托：手动录入表单提交（支持动态添加的表单，包括详情页）
  $(document).on('submit', '#musicalbum-manual-form', function(e) {
    e.preventDefault();
    if (typeof saveViewing === 'function') {
      saveViewing($(this));
    }
  });

  // 全局事件委托：图片上传功能
  $(document).on('change', '#musicalbum-form-ticket-image', function() {
    if (typeof handleImageUpload === 'function') {
      handleImageUpload(this, '#musicalbum-form-ticket-preview', '#musicalbum-form-ticket-image-id');
    }
  });

  // 详情页编辑按钮点击事件（使用事件委托，支持动态添加的按钮）
  $(document).on('click', '.viewing-record-details .musicalbum-btn-edit', function() {
    var id = $(this).data('id');
    if (id && typeof editViewing === 'function') {
      editViewing(id);
      
      // 确保日期输入框初始化（详情页的模态框可能是动态添加的）
      setTimeout(function() {
        if (typeof initDateInput === 'function') {
          initDateInput('#musicalbum-form-date', '#musicalbum-form-date-picker');
        }
        if (typeof initTimeValidation === 'function') {
          initTimeValidation('#musicalbum-form-time-start', '#musicalbum-form-time-end');
        }
      }, 100);
    }
  });

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

    // 注意：关闭模态框和表单提交的事件委托已在全局定义（initViewingManager函数外部）
    // 这里不再重复定义，避免重复绑定

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
          }
          if (res.theater) {
            $('#musicalbum-ocr-theater').val(res.theater);
          }
          if (res.cast) {
            $('#musicalbum-ocr-cast').val(res.cast);
          }
          if (res.price) {
            $('#musicalbum-ocr-price').val(res.price);
          }
          if (res.view_date) {
            $('#musicalbum-ocr-date').val(res.view_date);
            $('#musicalbum-ocr-date-picker').val(res.view_date);
          }
          $('#musicalbum-ocr-form').show();
          
          // 检查是否识别到任何有效数据
          var hasData = !!(res.title || res.theater || res.cast || res.price || res.view_date);
          
          if (!hasData) {
            // 显示更详细的错误信息
            var errorMsg = '未能识别到有效信息，请检查图片或手动填写';
            if (res._debug_text) {
              errorMsg += '\n\n识别到的原始文本：\n' + res._debug_text;
            }
            alert(errorMsg);
          }
        } else {
          alert('识别失败，请检查图片或稍后重试');
        }
      }).fail(function(xhr, status, error) {
        $btn.prop('disabled', false).text(originalText);
        var errorMsg = '识别失败';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.status) {
          errorMsg = '识别失败 (状态码: ' + xhr.responseJSON.data.status + ')';
        }
        alert(errorMsg);
      });
    });

    // OCR表单提交
    $('#musicalbum-ocr-form').on('submit', function(e) {
      e.preventDefault();
      saveViewing($(this));
    });

    // CSV导入
    $('#musicalbum-csv-import-btn').on('click', function() {
      var file = $('#musicalbum-csv-file')[0].files[0];
      if (!file) {
        alert('请先选择CSV文件');
        return;
      }
      
      var $btn = $(this);
      var originalText = $btn.text();
      $btn.prop('disabled', true).text('导入中...');
      
      // 显示进度条
      $('#musicalbum-csv-progress').show();
      $('#musicalbum-csv-result').hide();
      $('#musicalbum-csv-progress-bar').css('width', '0%');
      $('#musicalbum-csv-progress-text').text('0/0');
      
      var fd = new FormData();
      fd.append('csv_file', file);
      
      $.ajax({
        url: ViewingRecords.rest.importCsv,
        method: 'POST',
        headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce },
        data: fd,
        processData: false,
        contentType: false,
        xhr: function() {
          var xhr = new window.XMLHttpRequest();
          // 监听上传进度
          xhr.upload.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
              var percentComplete = (e.loaded / e.total) * 100;
              $('#musicalbum-csv-progress-bar').css('width', percentComplete + '%');
              // 更新进度文本：显示上传进度
              $('#musicalbum-csv-progress-text').text('上传中 ' + Math.round(percentComplete) + '%');
            }
          }, false);
          // 监听下载进度（服务器处理阶段）
          xhr.addEventListener('progress', function(e) {
            if (e.lengthComputable) {
              $('#musicalbum-csv-progress-text').text('处理中...');
            }
          }, false);
          return xhr;
        }
      }).done(function(res) {
        $btn.prop('disabled', false).text(originalText);
        
        // 更新进度条到100%
        $('#musicalbum-csv-progress-bar').css('width', '100%');
        
        // 更新进度文本为实际结果
        if (res && res.success && res.total_count !== undefined) {
          $('#musicalbum-csv-progress-text').text(res.success_count + '/' + res.total_count);
        } else {
          $('#musicalbum-csv-progress-text').text('完成');
        }
        
        if (res && res.success) {
          var resultHtml = '<div style="padding:1rem;border-radius:0.5rem;';
          if (res.error_count > 0) {
            resultHtml += 'background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">';
            resultHtml += '<h4 style="margin:0 0 0.5rem 0;color:#991b1b;">导入完成（有错误）</h4>';
          } else {
            resultHtml += 'background:#f0fdf4;border:1px solid #bbf7d0;color:#166534;">';
            resultHtml += '<h4 style="margin:0 0 0.5rem 0;color:#166534;">导入成功</h4>';
          }
          
          resultHtml += '<p style="margin:0.5rem 0;"><strong>成功：</strong>' + res.success_count + ' 条</p>';
          resultHtml += '<p style="margin:0.5rem 0;"><strong>失败：</strong>' + res.error_count + ' 条</p>';
          resultHtml += '<p style="margin:0.5rem 0;"><strong>总计：</strong>' + res.total_count + ' 条</p>';
          
          if (res.errors && res.errors.length > 0) {
            resultHtml += '<div style="margin-top:1rem;padding:0.75rem;background:#fff;border-radius:0.25rem;max-height:200px;overflow-y:auto;">';
            resultHtml += '<strong style="display:block;margin-bottom:0.5rem;">错误详情：</strong>';
            resultHtml += '<ul style="margin:0;padding-left:1.5rem;font-size:0.875rem;">';
            res.errors.forEach(function(error) {
              resultHtml += '<li style="margin:0.25rem 0;">' + error + '</li>';
            });
            resultHtml += '</ul></div>';
          }
          
          resultHtml += '</div>';
          $('#musicalbum-csv-result').html(resultHtml).show();
          
          // 如果导入成功，刷新列表
          if (res.success_count > 0 && typeof loadListView === 'function') {
            setTimeout(function() {
              loadListView();
            }, 1000);
          }
        } else {
          // 更新进度文本为失败状态
          $('#musicalbum-csv-progress-text').text('失败');
          
          var errorMsg = res && res.message ? res.message : '导入失败，请检查CSV文件格式';
          $('#musicalbum-csv-result').html(
            '<div style="padding:1rem;border-radius:0.5rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">' +
            '<h4 style="margin:0 0 0.5rem 0;color:#991b1b;">导入失败</h4>' +
            '<p style="margin:0;">' + errorMsg + '</p>' +
            '</div>'
          ).show();
        }
      }).fail(function(xhr, status, error) {
        $btn.prop('disabled', false).text(originalText);
        
        // 更新进度文本为失败状态
        $('#musicalbum-csv-progress-text').text('失败');
        
        var errorMsg = '导入失败';
        if (xhr.responseJSON && xhr.responseJSON.message) {
          errorMsg = xhr.responseJSON.message;
        } else if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.status) {
          errorMsg = '导入失败 (状态码: ' + xhr.responseJSON.data.status + ')';
        }
        $('#musicalbum-csv-result').html(
          '<div style="padding:1rem;border-radius:0.5rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;">' +
          '<h4 style="margin:0 0 0.5rem 0;color:#991b1b;">导入失败</h4>' +
          '<p style="margin:0;">' + errorMsg + '</p>' +
          '</div>'
        ).show();
      });
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
        preview.html('<img src="' + e.target.result + '" style="max-width: 200px; max-height: 200px; border-radius: 4px; border: 1px solid #e5e7eb;" /><br><small style="color: #6b7280; margin-top: 0.5rem; display: block;">新图片（保存后将替换原图片）</small>');
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
        // 上传成功，保存新图片ID（会替换旧图片）
        var $hiddenInput = $(imageIdSelector);
        if ($hiddenInput.length === 0) {
          // 尝试在模态框内查找
          $hiddenInput = $('#musicalbum-form-modal').find('input[type="hidden"][name="ticket_image_id"]');
        }
        if ($hiddenInput.length > 0) {
          $hiddenInput.val(res.id);
        } else {
        }
      }).fail(function(xhr) {
        alert('图片上传失败，请重试');
        // 上传失败，清空预览和ID
        $(previewSelector).empty();
        var $hiddenInput = $(imageIdSelector);
        if ($hiddenInput.length === 0) {
          $hiddenInput = $('#musicalbum-form-modal').find('input[type="hidden"][name="ticket_image_id"]');
        }
        if ($hiddenInput.length > 0) {
          $hiddenInput.val('');
        }
      });
    } else {
      // 如果没有选择文件，但之前有图片，保留原图片ID
      // 不做任何操作，保持当前状态
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
    
    // 防止重复弹出alert的标志
    var isShowingAlert = false;
    var lastValidatedValue = '';
    
    // 文本输入框：支持直接输入日期
    $textInput.on('change', function() {
      var $input = $(this);
      var dateStr = $input.val();
      
      // 如果值没有变化，不重复验证
      if (dateStr === lastValidatedValue) {
        return;
      }
      
      // 如果正在显示alert，不重复触发
      if (isShowingAlert) {
        return;
      }
      
      if (!dateStr) {
        lastValidatedValue = '';
        return;
      }
      
      var formattedDate = validateAndFormatDate(dateStr);
      
      if (formattedDate) {
        $input.val(formattedDate);
        $datePicker.val(formattedDate);
        lastValidatedValue = formattedDate;
      } else {
        // 格式不正确，显示提示（只显示一次）
        if (!isShowingAlert) {
          isShowingAlert = true;
          alert('日期格式不正确，请使用 YYYY-MM-DD 格式（如：2025-12-17）');
          // 延迟重置标志，确保alert已关闭
          setTimeout(function() {
            isShowingAlert = false;
          }, 300);
        }
        // 不自动聚焦，让用户自己选择是否继续编辑
        lastValidatedValue = dateStr; // 记录已验证的值，避免重复验证相同错误值
      }
    });
    
    // blur事件：只用于格式化，不显示错误提示
    $textInput.on('blur', function() {
      var $input = $(this);
      var dateStr = $input.val();
      
      if (!dateStr) {
        return;
      }
      
      var formattedDate = validateAndFormatDate(dateStr);
      
      if (formattedDate) {
        $input.val(formattedDate);
        $datePicker.val(formattedDate);
        lastValidatedValue = formattedDate;
      }
      // blur时不显示错误提示，让用户可以继续编辑
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

  // 列表视图分页相关变量
  var listViewCurrentPage = 1;
  var listViewItemsPerPage = 5;

  // 加载列表视图
  function loadListView(page) {
    // 如果传入了页码参数，更新当前页码
    if (typeof page !== 'undefined') {
      listViewCurrentPage = page;
    } else {
      // 如果没有传入页码，重置到第一页（用于搜索/筛选时）
      listViewCurrentPage = 1;
    }

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
        // 计算分页信息
        var totalItems = data.length;
        var totalPages = Math.ceil(totalItems / listViewItemsPerPage);
        var startIndex = (listViewCurrentPage - 1) * listViewItemsPerPage;
        var endIndex = Math.min(startIndex + listViewItemsPerPage, totalItems);

        // 添加批量操作工具栏
        var html = '<div class="musicalbum-batch-actions" style="margin-bottom:1rem;padding:0.75rem;background:#f9fafb;border-radius:0.5rem;display:flex;align-items:center;gap:1rem;">';
        html += '<label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">';
        html += '<input type="checkbox" id="musicalbum-select-all" style="cursor:pointer;">';
        html += '<span>全选</span>';
        html += '</label>';
        html += '<button type="button" id="musicalbum-batch-delete-btn" class="musicalbum-btn" style="background:#dc2626;color:#fff;padding:0.5rem 1rem;border:none;border-radius:0.25rem;cursor:pointer;display:none;" disabled>批量删除</button>';
        html += '<span id="musicalbum-selected-count" style="color:#6b7280;font-size:0.875rem;"></span>';
        html += '</div>';
        
        html += '<div class="musicalbum-list-items">';
        data.forEach(function(item, index) {
          // 检查是否有票面图片
          var hasTicketImage = item.ticket_image && item.ticket_image.url;
          var ticketImageUrl = hasTicketImage ? item.ticket_image.url : '';
          var itemClass = 'musicalbum-list-item';
          var itemStyle = '';
          
          // 添加分页控制：只显示当前页的记录
          var isVisible = (index >= startIndex && index < endIndex);
          if (!isVisible) {
            itemClass += ' musicalbum-list-item-hidden';
          }
          
          if (hasTicketImage) {
            itemClass += ' musicalbum-list-item-with-bg';
            // 使用内联样式设置背景图片
            itemStyle = ' style="background-image: url(\'' + escapeHtml(ticketImageUrl) + '\');"';
          }
          
          html += '<div class="' + itemClass + '" data-id="' + item.id + '" data-index="' + index + '"' + itemStyle + '>';
          
          // 主要信息区域（默认显示：标题和类型）
          html += '<div class="musicalbum-item-main">';
          html += '<div class="musicalbum-item-title-row" style="display:flex;align-items:center;gap:0.75rem;">';
          html += '<input type="checkbox" class="musicalbum-item-checkbox" data-id="' + item.id + '" style="cursor:pointer;flex-shrink:0;">';
          html += '<h4 style="margin:0;flex:1;"><a href="' + item.url + '" target="_blank">' + escapeHtml(item.title) + '</a></h4>';
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

        // 添加分页控件
        if (totalPages > 1) {
          html += '<div class="musicalbum-list-pagination">';
          html += '<div class="musicalbum-pagination-info">';
          html += '<span>共 ' + totalItems + ' 条记录，第 ' + listViewCurrentPage + ' / ' + totalPages + ' 页</span>';
          html += '</div>';
          html += '<div class="musicalbum-pagination-controls">';
          
          // 上一页按钮
          if (listViewCurrentPage > 1) {
            html += '<button type="button" class="musicalbum-btn-pagination musicalbum-btn-prev" data-page="' + (listViewCurrentPage - 1) + '">上一页</button>';
          } else {
            html += '<button type="button" class="musicalbum-btn-pagination musicalbum-btn-prev" disabled>上一页</button>';
          }
          
          // 页码按钮（显示当前页前后各2页）
          var pageStart = Math.max(1, listViewCurrentPage - 2);
          var pageEnd = Math.min(totalPages, listViewCurrentPage + 2);
          
          if (pageStart > 1) {
            html += '<button type="button" class="musicalbum-btn-pagination musicalbum-btn-page" data-page="1">1</button>';
            if (pageStart > 2) {
              html += '<span class="musicalbum-pagination-ellipsis">...</span>';
            }
          }
          
          for (var i = pageStart; i <= pageEnd; i++) {
            if (i === listViewCurrentPage) {
              html += '<button type="button" class="musicalbum-btn-pagination musicalbum-btn-page musicalbum-btn-page-active" data-page="' + i + '">' + i + '</button>';
            } else {
              html += '<button type="button" class="musicalbum-btn-pagination musicalbum-btn-page" data-page="' + i + '">' + i + '</button>';
            }
          }
          
          if (pageEnd < totalPages) {
            if (pageEnd < totalPages - 1) {
              html += '<span class="musicalbum-pagination-ellipsis">...</span>';
            }
            html += '<button type="button" class="musicalbum-btn-pagination musicalbum-btn-page" data-page="' + totalPages + '">' + totalPages + '</button>';
          }
          
          // 下一页按钮
          if (listViewCurrentPage < totalPages) {
            html += '<button type="button" class="musicalbum-btn-pagination musicalbum-btn-next" data-page="' + (listViewCurrentPage + 1) + '">下一页</button>';
          } else {
            html += '<button type="button" class="musicalbum-btn-pagination musicalbum-btn-next" disabled>下一页</button>';
          }
          
          html += '</div>';
          html += '</div>';
        }

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

        // 绑定分页按钮
        $('.musicalbum-btn-pagination').on('click', function() {
          var page = $(this).data('page');
          if (page && !$(this).prop('disabled')) {
            loadListView(page);
            // 滚动到列表顶部
            $('html, body').animate({
              scrollTop: $('#musicalbum-list-container').offset().top - 20
            }, 300);
          }
        });
        
        // 更新批量删除按钮状态
        function updateBatchDeleteButton() {
          var selectedCount = $('.musicalbum-item-checkbox:checked').length;
          var $btn = $('#musicalbum-batch-delete-btn');
          var $count = $('#musicalbum-selected-count');
          
          if (selectedCount > 0) {
            $btn.show().prop('disabled', false);
            $count.text('已选择 ' + selectedCount + ' 条记录');
          } else {
            $btn.hide().prop('disabled', true);
            $count.text('');
          }
        }
        
        // 重置选择状态
        $('#musicalbum-select-all').prop('checked', false);
        updateBatchDeleteButton();
        
        // 绑定批量选择功能
        // 全选/取消全选（使用off先移除旧绑定，避免重复绑定）
        $('#musicalbum-select-all').off('change').on('change', function() {
          var isChecked = $(this).prop('checked');
          $('.musicalbum-item-checkbox').prop('checked', isChecked);
          updateBatchDeleteButton();
        });
        
        // 单个复选框变化（使用off先移除旧绑定，避免重复绑定）
        $('.musicalbum-item-checkbox').off('change').on('change', function() {
          updateBatchDeleteButton();
          // 更新全选复选框状态
          var totalCheckboxes = $('.musicalbum-item-checkbox:visible').length;
          var checkedCheckboxes = $('.musicalbum-item-checkbox:visible:checked').length;
          $('#musicalbum-select-all').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
        });
        
        // 批量删除按钮（使用off先移除旧绑定，避免重复绑定）
        $('#musicalbum-batch-delete-btn').off('click').on('click', function() {
          var selectedIds = [];
          $('.musicalbum-item-checkbox:checked').each(function() {
            selectedIds.push($(this).data('id'));
          });
          
          if (selectedIds.length === 0) {
            alert('请选择要删除的记录');
            return;
          }
          
          if (!confirm('确定要删除选中的 ' + selectedIds.length + ' 条记录吗？此操作不可恢复！')) {
            return;
          }
          
          batchDeleteViewings(selectedIds);
        });
      } else {
        // 即使没有数据，也显示批量操作工具栏（但按钮会被禁用）
        var html = '<div class="musicalbum-batch-actions" style="margin-bottom:1rem;padding:0.75rem;background:#f9fafb;border-radius:0.5rem;display:flex;align-items:center;gap:1rem;">';
        html += '<label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">';
        html += '<input type="checkbox" id="musicalbum-select-all" disabled style="cursor:pointer;">';
        html += '<span>全选</span>';
        html += '</label>';
        html += '<button type="button" id="musicalbum-batch-delete-btn" class="musicalbum-btn" style="background:#dc2626;color:#fff;padding:0.5rem 1rem;border:none;border-radius:0.25rem;cursor:pointer;display:none;" disabled>批量删除</button>';
        html += '<span id="musicalbum-selected-count" style="color:#6b7280;font-size:0.875rem;"></span>';
        html += '</div>';
        html += '<div class="musicalbum-empty">暂无记录</div>';
        container.html(html);
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

    // 移除已存在的快速导航容器，避免重复创建
    $('.musicalbum-calendar-nav').remove();

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
    
    // 防止重复弹出alert的标志
    var isShowingAlert = false;
    var lastValidatedValue = '';
    
    // 文本输入框：支持直接输入日期
    dateInput.on('change', function() {
      var $input = $(this);
      var dateStr = $input.val();
      
      // 如果值没有变化，不重复验证
      if (dateStr === lastValidatedValue) {
        return;
      }
      
      // 如果正在显示alert，不重复触发
      if (isShowingAlert) {
        return;
      }
      
      if (!dateStr) {
        lastValidatedValue = '';
        return;
      }
      
      var formattedDate = validateAndFormatDate(dateStr);
      
      if (formattedDate) {
        $input.val(formattedDate);
        datePicker.val(formattedDate);
        calendar.gotoDate(formattedDate);
        lastValidatedValue = formattedDate;
      } else {
        // 格式不正确，显示提示（只显示一次）
        if (!isShowingAlert) {
          isShowingAlert = true;
          alert('日期格式不正确，请使用 YYYY-MM-DD 格式（如：2025-12-17）');
          // 延迟重置标志，确保alert已关闭
          setTimeout(function() {
            isShowingAlert = false;
          }, 300);
        }
        // 不自动聚焦，让用户自己选择是否继续编辑
        lastValidatedValue = dateStr; // 记录已验证的值，避免重复验证相同错误值
      }
    });
    
    // blur事件：只用于格式化，不显示错误提示
    dateInput.on('blur', function() {
      var $input = $(this);
      var dateStr = $input.val();
      
      if (!dateStr) {
        return;
      }
      
      var formattedDate = validateAndFormatDate(dateStr);
      
      if (formattedDate) {
        $input.val(formattedDate);
        datePicker.val(formattedDate);
        calendar.gotoDate(formattedDate);
        lastValidatedValue = formattedDate;
      }
      // blur时不显示错误提示，让用户可以继续编辑
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
      html += '<div class="musicalbum-event-details">';
      if (item.category || props.category) html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">🎭 类别</span><span class="musicalbum-detail-value">' + escapeHtml(item.category || props.category) + '</span></div>';
      if (item.theater || props.theater) html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">🏛️ 剧院</span><span class="musicalbum-detail-value">' + escapeHtml(item.theater || props.theater) + '</span></div>';
      if (item.cast || props.cast) html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">👥 卡司</span><span class="musicalbum-detail-value">' + escapeHtml(item.cast || props.cast) + '</span></div>';
      if (item.price || props.price) html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">💰 票价</span><span class="musicalbum-detail-value">' + escapeHtml(item.price || props.price) + '</span></div>';
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
        html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">📅 观演时间</span><span class="musicalbum-detail-value">' + dateTimeStr + '</span></div>';
      }
      html += '</div>';
      html += '<div class="musicalbum-calendar-actions">';
      html += '<button type="button" class="musicalbum-btn musicalbum-btn-sm musicalbum-btn-edit" data-id="' + id + '">✏️ 编辑</button>';
      html += '<button type="button" class="musicalbum-btn musicalbum-btn-sm musicalbum-btn-delete" data-id="' + id + '">🗑️ 删除</button>';
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
      html += '<div class="musicalbum-event-details">';
      if (props.category) html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">🎭 类别</span><span class="musicalbum-detail-value">' + escapeHtml(props.category) + '</span></div>';
      if (props.theater) html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">🏛️ 剧院</span><span class="musicalbum-detail-value">' + escapeHtml(props.theater) + '</span></div>';
      if (props.cast) html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">👥 卡司</span><span class="musicalbum-detail-value">' + escapeHtml(props.cast) + '</span></div>';
      if (props.price) html += '<div class="musicalbum-detail-item"><span class="musicalbum-detail-label">💰 票价</span><span class="musicalbum-detail-value">' + escapeHtml(props.price) + '</span></div>';
      html += '</div>';
      html += '<div class="musicalbum-calendar-actions">';
      html += '<button type="button" class="musicalbum-btn musicalbum-btn-sm musicalbum-btn-edit" data-id="' + id + '">✏️ 编辑</button>';
      html += '<button type="button" class="musicalbum-btn musicalbum-btn-sm musicalbum-btn-delete" data-id="' + id + '">🗑️ 删除</button>';
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
    // 收集表单数据
    var $formToSearch = $form;
    
    // 如果表单为空或找不到标题输入框，尝试从模态框内查找
    if ($form.length === 0 || $form.find('input[name="title"], #musicalbum-form-title-input').length === 0) {
      $formToSearch = $('#musicalbum-form-modal').find('form');
      if ($formToSearch.length === 0) {
        $formToSearch = $form.closest('.musicalbum-modal-content').find('form');
      }
    }
    
    if ($formToSearch.length === 0) {
      alert('无法找到表单，请刷新页面后重试');
      return;
    }
    
    $formToSearch.find('input, select, textarea').each(function() {
      var $el = $(this);
      var name = $el.attr('name');
      var type = $el.attr('type') || '';
      
      if (!name) return;
      
      // 跳过id字段和文件输入框
      if (name === 'id' || type === 'file') {
        return;
      }
      
      // 收集所有其他字段（包括隐藏字段如 ticket_image_id）
      var value = $el.val();
      formData[name] = value;
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

    // 获取编辑ID（尝试多个位置）
    var id = $('#musicalbum-edit-id').val();
    if (!id) {
      id = $formToSearch.find('input[name="id"]').val();
    }
    if (!id) {
      id = $('#musicalbum-form-modal').find('input[name="id"]').val();
    }
    // 如果还是没有找到，尝试从表单的隐藏字段中获取
    if (!id) {
      id = $formToSearch.find('#musicalbum-edit-id').val();
    }
    
    var url = ViewingRecords.rest.viewings;
    var method = 'POST';

    if (id) {
      url += '/' + id;
      method = 'PUT';
    }

    // 处理图片上传和替换
    // 先尝试从表单内查找，再尝试从模态框内查找
    var ticketImageInput = $form.find('input[type="file"][name="ticket_image"]');
    if (ticketImageInput.length === 0) {
      ticketImageInput = $('#musicalbum-form-modal').find('input[type="file"][name="ticket_image"]');
    }
    if (ticketImageInput.length === 0) {
      ticketImageInput = $form.closest('.musicalbum-modal-content').find('input[type="file"][name="ticket_image"]');
    }
    
    var ticketImageId = $form.find('input[type="hidden"][name="ticket_image_id"]').val();
    if (!ticketImageId) {
      ticketImageId = $('#musicalbum-form-modal').find('input[type="hidden"][name="ticket_image_id"]').val();
    }
    if (!ticketImageId) {
      ticketImageId = $form.closest('.musicalbum-modal-content').find('input[type="hidden"][name="ticket_image_id"]').val();
    }
    
    // 检查是否有新选择的文件（优先检查文件输入框）
    var hasNewFile = false;
    if (ticketImageInput.length > 0) {
      var fileInput = ticketImageInput[0];
      if (fileInput.files && fileInput.files.length > 0) {
        hasNewFile = true;
      }
    }
    
    if (hasNewFile) {
      // 有新文件，先上传图片
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
        // 上传成功，使用新图片ID
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
      // 没有新文件，但已有图片ID，保留原图片
      formData.ticket_image_id = ticketImageId;
      saveViewingData(url, method, formData, id);
    } else {
      // 没有新文件，也没有旧图片ID
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
      // 检查响应是否包含错误
      if (res && res.code && res.code !== 'success') {
        alert(res.message || '保存失败：' + res.code);
        return;
      }
      
      // 检查响应是否包含错误信息（WP_Error格式）
      if (res && res.code && res.message) {
        alert(res.message || '保存失败：' + res.code);
        return;
      }
      
      alert(id ? '记录更新成功' : '记录创建成功');
      
      $('#musicalbum-form-modal').hide();
      if (typeof resetForm === 'function') {
        resetForm();
      }
      
      // 保存成功后自动刷新页面
      setTimeout(function() {
        location.reload();
      }, 500);
    }).fail(function(xhr) {
      var msg = '保存失败';
      if (xhr.responseJSON) {
        if (xhr.responseJSON.message) {
          msg = xhr.responseJSON.message;
        } else if (xhr.responseJSON.code) {
          msg = '错误：' + xhr.responseJSON.code;
        }
      } else if (xhr.responseText) {
        msg = '保存失败，请检查网络连接或刷新页面重试';
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
          var imageUrl = '';
          var imageId = '';
          
          if (typeof item.ticket_image === 'object') {
            imageUrl = item.ticket_image.url || '';
            imageId = item.ticket_image.id || item.ticket_image.ID || '';
          } else if (typeof item.ticket_image === 'string' || typeof item.ticket_image === 'number') {
            // 如果是ID，尝试获取URL
            imageId = item.ticket_image;
            // URL会在后端处理
          }
          
          if (imageUrl) {
            $('#musicalbum-form-ticket-preview').html('<img src="' + imageUrl + '" style="max-width: 200px; max-height: 200px; border-radius: 4px; border: 1px solid #e5e7eb;" /><br><small style="color: #6b7280; margin-top: 0.5rem; display: block;">当前图片（选择新文件可替换）</small>');
          } else {
            $('#musicalbum-form-ticket-preview').html('<small style="color: #6b7280;">已有图片（ID: ' + imageId + '），选择新文件可替换</small>');
          }
          
          $('#musicalbum-form-ticket-image-id').val(imageId);
          // 清空文件输入框，确保可以选择新文件
          $('#musicalbum-form-ticket-image').val('');
        } else {
          $('#musicalbum-form-ticket-preview').empty();
          $('#musicalbum-form-ticket-image-id').val('');
          // 清空文件输入框
          $('#musicalbum-form-ticket-image').val('');
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
  
  // 批量删除观演记录
  function batchDeleteViewings(ids) {
    if (!ids || ids.length === 0) {
      alert('请选择要删除的记录');
      return;
    }
    
    var $btn = $('#musicalbum-batch-delete-btn');
    var originalText = $btn.text();
    $btn.prop('disabled', true).text('删除中...');
    
    $.ajax({
      url: ViewingRecords.rest.batchDelete,
      method: 'POST',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce },
      data: JSON.stringify({ ids: ids }),
      contentType: 'application/json'
    }).done(function(res) {
      $btn.prop('disabled', false).text(originalText);
      
      if (res && res.success) {
        var message = res.message || '批量删除完成';
        if (res.error_count > 0) {
          message += '\n成功：' + res.success_count + ' 条\n失败：' + res.error_count + ' 条';
          if (res.errors && res.errors.length > 0) {
            message += '\n\n错误详情：\n' + res.errors.join('\n');
          }
        }
        alert(message);
        
        // 刷新列表
        loadListView();
        if (window.viewingCalendar) {
          window.viewingCalendar.refetchEvents();
        }
      } else {
        var errorMsg = res && res.message ? res.message : '批量删除失败';
        alert(errorMsg);
      }
    }).fail(function(xhr) {
      $btn.prop('disabled', false).text(originalText);
      var errorMsg = '批量删除失败';
      if (xhr.responseJSON && xhr.responseJSON.message) {
        errorMsg = xhr.responseJSON.message;
      }
      alert(errorMsg);
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
  
  // 数据概览功能 - 支持多个实例
  function loadOverview(instanceEl) {
    var overviewGrid = instanceEl.find('.musicalbum-overview-grid');
    var loadingEl = instanceEl.find('.musicalbum-overview-loading');
    
    if (overviewGrid.length === 0) {
      return;
    }
    
    loadingEl.addClass('show');
    
    $.ajax({
      url: ViewingRecords.rest.overview,
      method: 'GET',
      headers: { 'X-WP-Nonce': ViewingRecords.rest.nonce }
    }).done(function(data) {
      if (data && typeof data === 'object') {
        // 使用 data-field 属性来更新对应的值
        instanceEl.find('[data-field="total-count"]').text(data.total_count || 0);
        instanceEl.find('[data-field="month-count"]').text(data.month_count || 0);
        instanceEl.find('[data-field="total-spending"]').text('¥' + (data.total_spending || 0).toLocaleString('zh-CN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }));
        instanceEl.find('[data-field="favorite-category"]').text(data.favorite_category || '暂无');
      }
      loadingEl.removeClass('show');
    }).fail(function() {
      instanceEl.find('[data-field="total-count"]').text('加载失败');
      instanceEl.find('[data-field="month-count"]').text('加载失败');
      instanceEl.find('[data-field="total-spending"]').text('加载失败');
      instanceEl.find('[data-field="favorite-category"]').text('加载失败');
      loadingEl.removeClass('show');
    });
  }
  
  // 页面加载时自动加载所有概览数据实例
  $('.musicalbum-overview-section').each(function() {
    loadOverview($(this));
  });
  
  // 最近观演记录数量选择器功能
  function initRecentViewingsCountSelector() {
    $('.musicalbum-recent-viewings-count-select').on('change', function() {
      var $select = $(this);
      var instanceId = $select.closest('.musicalbum-recent-viewings').data('instance-id');
      var count = parseInt($select.val(), 10);
      
      // 保存用户选择到localStorage
      var storageKey = 'musicalbum_recent_viewings_count_' + instanceId;
      if (typeof Storage !== 'undefined') {
        localStorage.setItem(storageKey, count);
      }
      
      // 显示/隐藏记录项
      var $container = $select.closest('.musicalbum-recent-viewings');
      $container.find('.musicalbum-recent-viewings-item').each(function(index) {
        var $item = $(this);
        if (index < count) {
          $item.slideDown(200);
        } else {
          $item.slideUp(200);
        }
      });
    });
    
    // 页面加载时恢复用户之前的选择
    $('.musicalbum-recent-viewings').each(function() {
      var $container = $(this);
      var instanceId = $container.data('instance-id');
      var $select = $container.find('.musicalbum-recent-viewings-count-select');
      var defaultCount = parseInt($select.data('default'), 10);
      
      if (typeof Storage !== 'undefined') {
        var storageKey = 'musicalbum_recent_viewings_count_' + instanceId;
        var savedCount = localStorage.getItem(storageKey);
        if (savedCount !== null) {
          var count = parseInt(savedCount, 10);
          if (count >= 0 && count <= 10) {
            $select.val(count);
            // 触发显示/隐藏
            $container.find('.musicalbum-recent-viewings-item').each(function(index) {
              var $item = $(this);
              if (index < count) {
                $item.show();
              } else {
                $item.hide();
              }
            });
          }
        }
      }
    });
  }
  
  // 初始化最近观演记录数量选择器
  initRecentViewingsCountSelector();
  
})(jQuery);
