(function ($) {
    'use strict';

    $(document).ready(function () {
        // 处理"不感兴趣"按钮点击事件
        $('.msr-btn-secondary[name="musicalbum_not_interested"]').on('click', function(e) {
            e.preventDefault();
            
            var $button = $(this);
            var $card = $button.closest('.msr-card');
            var musicalTitle = $button.closest('form').find('input[name="musical_title"]').val();
            
            // 显示加载状态
            $button.text('处理中...');
            $button.prop('disabled', true);
            
            // 发送AJAX请求
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'musicalbum_not_interested',
                    musical_title: musicalTitle
                },
                success: function(response) {
                    if (response.success) {
                        // 隐藏当前卡片
                        $card.fadeOut('slow', function() {
                            $(this).remove();
                            
                            // 检查是否需要补充新的推荐
                            checkAndLoadMoreRecommendations();
                        });
                    } else {
                        // 恢复按钮状态
                        $button.text('不感兴趣');
                        $button.prop('disabled', false);
                        alert('操作失败，请重试。');
                    }
                },
                error: function() {
                    // 恢复按钮状态
                    $button.text('不感兴趣');
                    $button.prop('disabled', false);
                    alert('网络错误，请重试。');
                }
            });
        });
        
        // 检查是否需要加载更多推荐
        function checkAndLoadMoreRecommendations() {
            // 这个函数可以扩展为加载更多推荐内容
            // 目前简单实现为检查是否有足够的推荐项
            console.log('已隐藏一个不感兴趣的音乐剧');
        }
        
        // 处理AI推荐换一换按钮
        $('#msr-ai-refresh').on('click', function() {
            var $button = $(this);
            var $container = $('#msr-ai-recommendations');
            
            // 获取当前推荐列表
            var currentRecommendations = [];
            $container.find('.msr-card').each(function() {
                var title = $(this).find('.msr-card-title a').text().trim();
                var desc = $(this).find('.msr-card-text').text().trim();
                currentRecommendations.push({title: title, desc: desc});
            });
            
            // 显示加载状态
            $button.text('加载中...');
            $button.prop('disabled', true);
            
            // 添加加载动画
            $container.css('opacity', '0.7');
            
            // 发送AJAX请求
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'musicalbum_refresh_ai_recommendations',
                    current_recommendations: currentRecommendations
                },
                success: function(response) {
                    if (response.success && response.data && response.data.recommendations) {
                        var recommendations = response.data.recommendations;
                        
                        // 清空当前推荐列表
                        $container.empty();
                        
                        // 添加新推荐
                        recommendations.forEach(function(item) {
                            var $card = $('<div class="msr-card">');
                            $card.html(
                                '<h5 class="msr-card-title">' +
                                '<a href="javascript:void(0);" class="msr-musical-link" data-musical="' + 
                                item.title + '">' + item.title + '</a></h5>' +
                                '<p class="msr-card-text">' + item.desc + '</p>'
                            );
                            $container.append($card);
                        });
                        
                        // 重新绑定音乐剧链接事件
                        bindMusicalLinks();
                    } else {
                        alert(response.data && response.data.message ? response.data.message : '获取新推荐失败');
                    }
                },
                error: function() {
                    alert('网络错误，请重试');
                },
                complete: function() {
                    // 恢复按钮状态
                    $button.text('换一换');
                    $button.prop('disabled', false);
                    
                    // 恢复容器显示
                    $container.css('opacity', '1');
                }
            });
        });
        
        // 绑定音乐剧链接点击事件
        function bindMusicalLinks() {
            // 这里可以添加音乐剧链接的点击事件处理
            // 如果已有其他地方处理，可以保持为空
        }
    });

})(jQuery);
