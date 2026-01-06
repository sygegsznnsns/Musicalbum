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
    });

})(jQuery);
