/**
 * Musicalbum Community Integration JavaScript
 * 社区集成插件前端脚本
 */

(function($) {
    'use strict';
    
    /**
     * 初始化
     */
    $(document).ready(function() {
        // 安全检查：确保配置对象存在
        if (typeof MusicalbumCommunity === 'undefined') {
            console.warn('Musicalbum Community: Configuration object not found. Some features may be disabled.');
            return;
        }

        try {
            initShareForm();
            initResourceUpload();
            initForumToggle();
            initKnowledgeConvert();
        } catch (e) {
            console.error('Musicalbum Community: Initialization failed', e);
        }
    });
    
    /**
     * 初始化知识库收录功能
     */
    function initKnowledgeConvert() {
        $(document).on('click', '.musicalbum-convert-btn', function(e) {
            e.preventDefault();
            var $btn = $(this);
            var objectId = $btn.data('id');
            var objectType = $btn.data('type'); // 'topic' or 'reply'
            var typeName = objectType === 'reply' ? '回复' : '话题';
            
            if (!confirm('确定要将此' + typeName + '收录到知识库吗？')) {
                return;
            }
            
            $btn.text('收录中...');
            
            $.ajax({
                url: MusicalbumCommunity.ajax_url,
                type: 'POST',
                data: {
                    action: 'musicalbum_convert_topic_to_knowledge',
                    nonce: MusicalbumCommunity.nonce,
                    object_id: objectId,
                    object_type: objectType
                },
                success: function(response) {
                    if (response.success) {
                        $btn.replaceWith('<span class="musicalbum-converted-badge" style="color:green;">✅ 已收录</span>');
                        alert(response.data.message);
                    } else {
                        alert(response.data || '收录失败');
                        $btn.text('📥 收录到知识库');
                    }
                },
                error: function() {
                    alert('网络错误');
                    $btn.text('📥 收录到知识库');
                }
            });
        });
    }
    
    /**
     * 初始化论坛话题/回复表单折叠
     */
    function initForumToggle() {
        // 针对 bbPress 的新建话题表单 (#new-post) 和回复表单 (#new-reply-1 等)
        // 通常表单容器ID是 #new-post (用于新建话题) 或 .bbp-reply-form (用于回复)
        
        // 处理所有符合条件的表单
        var $forms = $('#new-post, .bbp-reply-form form, .bbp-topic-form form');
        
        $forms.each(function() {
            var $form = $(this);
            var formId = $form.attr('id') || 'bbp-form-' + Math.floor(Math.random() * 1000);
            
            // 判断是“新建话题”还是“回复”
            // #new-post 可能是新建话题，也可能是回复（在某些模板中）
            // 更准确的方法是检查 form 内的 action input
            var isReply = $form.closest('.bbp-reply-form').length > 0 || $form.find('input[name="bbp_reply_to"]').length > 0;
            var isTopic = $form.closest('.bbp-topic-form').length > 0 || $form.find('input[name="bbp_topic_id"]').length === 0; // 没有 topic_id 通常意味着是新建 topic
            
            // 修正判断逻辑：如果既像 reply 又像 topic，优先认为是 reply (因为 reply 也是一种 post)
            if ($form.attr('id') === 'new-post' && window.location.href.indexOf('/topic/') !== -1) {
                isReply = true;
                isTopic = false;
            }
            
            var btnText = isReply ? "+ 回复帖子" : "+ 新建话题";
            var btnTextActive = isReply ? "× 收起回复" : "× 收起表单";
            
            // 默认隐藏表单
            $form.hide();
            
            // 创建切换按钮
            var $toggleBtn = $('<button class="button musicalbum-btn" style="margin-bottom:20px; display:block;">' + btnText + '</button>');
            
            // 插入按钮到表单前面
            $form.before($toggleBtn);
            
            // 绑定点击事件
            $toggleBtn.on('click', function(e) {
                e.preventDefault();
                $form.slideToggle();
                $(this).text(function(i, text) {
                    return text === btnText ? btnTextActive : btnText;
                });
            });

            // 特殊处理：如果用户点击了楼层中的"回复"链接（嵌套回复）
            // bbPress 会把表单移动到该楼层下，我们需要确保表单展开
            $('.bbp-reply-to-link').on('click', function() {
                if (isReply) {
                    $form.slideDown();
                    $toggleBtn.text(btnTextActive);
                    // 滚动到表单位置
                    $('html, body').animate({
                        scrollTop: $form.offset().top - 100
                    }, 500);
                }
            });
        });
    }
    
    /**
     * 初始化分享表单
     */
    function initShareForm() {
        $('.musicalbum-share-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $message = $form.find('.share-message');
            var viewingId = $form.data('viewing-id');
            var message = $form.find('textarea[name="message"]').val();
            var shareToForum = $form.find('input[name="share_to_forum"]').is(':checked');
            var shareToActivity = $form.find('input[name="share_to_activity"]').is(':checked');
            
            // 验证至少选择一个分享目标
            if (!shareToForum && !shareToActivity) {
                showMessage($message, '请至少选择一个分享目标', 'error');
                return;
            }
            
            // 显示加载状态
            showMessage($message, '分享中...', 'info');
            $form.find('button[type="submit"]').prop('disabled', true);
            
            // 发送 AJAX 请求
            $.ajax({
                url: MusicalbumCommunity.ajax_url,
                type: 'POST',
                data: {
                    action: 'musicalbum_share_viewing',
                    nonce: MusicalbumCommunity.nonce,
                    viewing_id: viewingId,
                    message: message,
                    share_to_forum: shareToForum ? 1 : 0,
                    share_to_activity: shareToActivity ? 1 : 0,
                },
                success: function(response) {
                    if (response.success) {
                        showMessage($message, '分享成功！', 'success');
                        
                        // 3秒后重置表单
                        setTimeout(function() {
                            $form[0].reset();
                            $message.hide();
                            $form.find('button[type="submit"]').prop('disabled', false);
                        }, 3000);
                    } else {
                        showMessage($message, response.data.message || '分享失败', 'error');
                        $form.find('button[type="submit"]').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    showMessage($message, '网络错误，请重试', 'error');
                    $form.find('button[type="submit"]').prop('disabled', false);
                }
            });
        });
    }
    
    /**
     * 显示消息
     */
    function showMessage($element, message, type) {
        $element
            .removeClass('success error info')
            .addClass(type)
            .text(message)
            .show();
    }
    
    /**
     * 初始化资源上传
     */
    function initResourceUpload() {
        $('#musicalbum-resource-upload-form').on('submit', function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var formData = new FormData($form[0]);
            formData.append('action', 'musicalbum_upload_resource');
            formData.append('nonce', MusicalbumCommunity.nonce);
            
            var $message = $form.find('.upload-message');
            showMessage($message, '上传中...', 'info');
            $form.find('button[type="submit"]').prop('disabled', true);
            
            $.ajax({
                url: MusicalbumCommunity.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        showMessage($message, '上传成功！', 'success');
                        setTimeout(function() {
                            $form[0].reset();
                            $message.hide();
                            $form.find('button[type="submit"]').prop('disabled', false);
                            
                            // 如果提供了回调，执行它
                            if (typeof window.musicalbumResourceUploaded === 'function') {
                                window.musicalbumResourceUploaded(response.data);
                            }
                        }, 2000);
                    } else {
                        showMessage($message, response.data.message || '上传失败', 'error');
                        $form.find('button[type="submit"]').prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    showMessage($message, '网络错误，请重试', 'error');
                    $form.find('button[type="submit"]').prop('disabled', false);
                }
            });
        });
    }
    
    /**
     * REST API 辅助函数
     */
    window.MusicalbumCommunityAPI = {
        /**
         * 分享观演记录
         */
        shareViewing: function(viewingId, options) {
            options = options || {};
            
            return $.ajax({
                url: MusicalbumCommunity.rest_url + 'share-viewing',
                type: 'POST',
                data: {
                    viewing_id: viewingId,
                    message: options.message || '',
                    share_to_forum: options.shareToForum !== false,
                    share_to_activity: options.shareToActivity !== false,
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', MusicalbumCommunity.nonce);
                }
            });
        },
        
        /**
         * 获取用户统计
         */
        getUserStats: function(userId) {
            return $.ajax({
                url: MusicalbumCommunity.rest_url + 'user-stats/' + userId,
                type: 'GET',
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', MusicalbumCommunity.nonce);
                }
            });
        },
        
        /**
         * 获取资源列表
         */
        getResources: function(options) {
            options = options || {};
            
            return $.ajax({
                url: MusicalbumCommunity.rest_url + 'resources',
                type: 'GET',
                data: {
                    per_page: options.perPage || 12,
                    page: options.page || 1,
                    category: options.category || '',
                },
                beforeSend: function(xhr) {
                    xhr.setRequestHeader('X-WP-Nonce', MusicalbumCommunity.nonce);
                }
            });
        }
    };
    
})(jQuery);

