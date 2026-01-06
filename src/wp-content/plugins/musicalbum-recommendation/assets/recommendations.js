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
        
        // 处理音乐剧名称点击事件 - 显示详细信息
        $(document).on('click', '.msr-musical-link', function(e) {
            e.preventDefault();
            
            var musicalTitle = $(this).data('musical');
            
            // 使用已有的模态框结构
            var $modal = $('#msr-modal');
            var $modalBody = $('#msr-modal-body');
            
            // 显示模态框
            $modal.css('display', 'block');
            $modalBody.html('<p>正在加载"' + musicalTitle + '"的详细信息...</p>');
            
            // 发送AJAX请求获取详细信息
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'musicalbum_get_musical_details',
                    musical_title: musicalTitle
                },
                success: function(response) {
                    if (response.success && response.data) {
                        $modalBody.html(response.data);
                    } else {
                        $modalBody.html('<p>暂无"' + musicalTitle + '"的详细信息。</p>');
                    }
                },
                error: function() {
                    $modalBody.html('<p>加载失败，请重试。</p>');
                }
            });
        });
        
        // 设置模态框关闭事件
        $(document).on('click', '.msr-modal-close', function() {
            $('#msr-modal').css('display', 'none');
        });
        
        // 点击模态框外部关闭
        $(window).on('click', function(e) {
            if (e.target.id === 'msr-modal') {
                $('#msr-modal').css('display', 'none');
            }
        });
    });

})(jQuery);
