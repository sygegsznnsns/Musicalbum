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
    if ($('.musicalbum-statistics-container').length > 0 && typeof Chart !== 'undefined') {
      loadStatistics();
      
      // 使用事件委托，确保按钮存在后再绑定
      $(document).on('click', '#musicalbum-refresh-btn', function() {
        var btn = $(this);
        btn.prop('disabled', true).find('.dashicons').addClass('spin');
        loadStatistics(function() {
          btn.prop('disabled', false).find('.dashicons').removeClass('spin');
        });
      });
      
      $(document).on('click', '#musicalbum-export-btn', function() {
        exportStatistics();
      });
    }
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
    // 创建导出选项菜单
    var menu = $('<div class="musicalbum-export-menu"><a href="#" data-format="csv">导出为 CSV</a><a href="#" data-format="json">导出为 JSON</a></div>');
    menu.css({
      position: 'absolute',
      top: $('#musicalbum-export-btn').offset().top + $('#musicalbum-export-btn').outerHeight() + 5,
      left: $('#musicalbum-export-btn').offset().left,
      background: '#fff',
      border: '1px solid #ddd',
      borderRadius: '4px',
      boxShadow: '0 2px 8px rgba(0,0,0,0.1)',
      padding: '8px 0',
      zIndex: 1000,
      minWidth: '150px'
    });
    
    menu.find('a').on('click', function(e) {
      e.preventDefault();
      var format = $(this).data('format');
      var url = MusicalbumIntegrations.rest.statisticsExport + '?format=' + format + '&_wpnonce=' + MusicalbumIntegrations.rest.nonce;
      window.open(url, '_blank');
      menu.remove();
    });
    
    // 点击外部关闭
    $(document).one('click', function(e) {
      if (!$(e.target).closest('.musicalbum-export-menu, #musicalbum-export-btn').length) {
        menu.remove();
      }
    });
    
    $('body').append(menu);
  }
})(jQuery);
