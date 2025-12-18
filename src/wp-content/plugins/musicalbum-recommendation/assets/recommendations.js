(function ($) {
    $(function () {
        $('.musicalbum-recommendation-block').each(function () {
            var $container = $(this);
            var params = $container.data('params') || {};
            var api = window.MusicalbumRecs;

            if (!api || !api.rest_url) {
                $container.find('.musicalbum-recs-loading')
                    .text('推荐服务不可用');
                return;
            }

            $.ajax({
                url: api.rest_url,
                method: 'GET',
                data: params,
                headers: {
                    'X-WP-Nonce': api.nonce
                }
            })
            .done(function (response) {
                $container.find('.musicalbum-recs-loading').remove();

                if (response && response.html) {
                    $container.append(response.html);
                } else {
                    $container.append(
                        '<div class="musicalbum-recs-error">暂无推荐内容</div>'
                    );
                }
            })
            .fail(function () {
                $container.find('.musicalbum-recs-loading')
                    .text('推荐加载失败');
            });
        });
    });
})(jQuery);
