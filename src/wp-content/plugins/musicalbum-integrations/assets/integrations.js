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
    }
  });

  /**
   * 加载统计数据并渲染图表
   */
  function loadStatistics() {
    var loadingEl = $('#musicalbum-statistics-loading');
    loadingEl.show();

    $.ajax({
      url: MusicalbumIntegrations.rest.statistics,
      method: 'GET',
      headers: { 'X-WP-Nonce': MusicalbumIntegrations.rest.nonce }
    }).done(function(data) {
      loadingEl.hide();
      
      // 渲染剧目类别分布饼图
      renderCategoryChart(data.category || {});
      
      // 渲染演员出场频率柱状图
      renderCastChart(data.cast || {});
      
      // 渲染票价区间折线图
      renderPriceChart(data.price || {});
    }).fail(function() {
      loadingEl.html('加载数据失败，请稍后重试').css('color', '#dc2626');
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

    new Chart(ctx, {
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

    new Chart(ctx, {
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

    new Chart(ctx, {
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
})(jQuery);
