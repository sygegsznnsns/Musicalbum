var MusicalbumMap = {
    // 查找用户附近的剧院
    findNearby: function() {
        if (!navigator.geolocation) {
            alert('您的浏览器不支持地理位置服务');
            return;
        }

        var btn = document.querySelector('.musicalbum-nearby-btn');
        var originalText = btn.innerText;
        btn.innerText = '定位中...';
        btn.disabled = true;

        navigator.geolocation.getCurrentPosition(function(position) {
            var lat = position.coords.latitude;
            var lng = position.coords.longitude;
            
            // 尝试调用 WP Go Maps 的 API 来定位
            // 注意：不同版本的 WPGMZA API 可能不同，这里尝试通用方法
            // 如果是 Pro 版，通常有 WPGMZA.maps[0].setCenter({lat: lat, lng: lng});
            
            if (typeof WPGMZA !== 'undefined' && WPGMZA.maps && WPGMZA.maps.length > 0) {
                var map = WPGMZA.maps[0]; // 假设页面只有一个地图
                
                // 设置中心点
                if (map.setCenter) {
                    map.setCenter({lat: lat, lng: lng});
                    map.setZoom(13);
                } else if (map.googleMap && map.googleMap.setCenter) {
                    // Google Maps 原生对象
                    map.googleMap.setCenter(new google.maps.LatLng(lat, lng));
                    map.googleMap.setZoom(13);
                }
                
                // 添加“我的位置”标记（可选）
                if (map.addMarker) {
                    // 这取决于 WPGMZA 的 API 实现
                    // 简单起见，仅定位
                }
                
                btn.innerText = '已定位到您附近';
            } else {
                console.warn('未找到 WP Go Maps 实例');
                btn.innerText = originalText;
            }
            
            setTimeout(function() {
                btn.disabled = false;
                btn.innerText = originalText;
            }, 3000);

        }, function(error) {
            alert('定位失败：' + error.message);
            btn.disabled = false;
            btn.innerText = originalText;
        });
    }
};

jQuery(document).ready(function($) {
    // 初始化逻辑
});
