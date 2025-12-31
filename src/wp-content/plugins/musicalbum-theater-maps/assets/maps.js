var MusicalbumMap = {
    // 查找用户附近的真实剧院（调用高德周边 API）
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
            
            // 1. 移动地图中心
            MusicalbumMap.centerMap(lat, lng);
            
            // 2. 调用后端 API 搜索周边真实剧院
            btn.innerText = '搜索周边剧院...';
            
            jQuery.post(MusicalbumMapConfig.ajaxurl, {
                action: 'musicalbum_search_nearby_theaters',
                nonce: MusicalbumMapConfig.nonce,
                lat: lat,
                lng: lng,
                radius: 5000 // 5km
            }, function(res) {
                if (res.success) {
                    var count = res.data.length;
                    btn.innerText = '发现 ' + count + ' 个剧院';
                    
                    // 将结果添加为临时标记（蓝色）
                    MusicalbumMap.addTempMarkers(res.data);
                } else {
                    btn.innerText = '未找到结果';
                    alert(res.data);
                }
            }).fail(function() {
                btn.innerText = '搜索失败';
            }).always(function() {
                setTimeout(function() {
                    btn.disabled = false;
                    btn.innerText = originalText;
                }, 3000);
            });

        }, function(error) {
            alert('定位失败：' + error.message);
            btn.disabled = false;
            btn.innerText = originalText;
        });
    },
    
    // 移动 WP Go Maps 地图中心
    centerMap: function(lat, lng) {
        if (typeof WPGMZA !== 'undefined' && WPGMZA.maps && WPGMZA.maps.length > 0) {
            var map = WPGMZA.maps[0];
            if (map.setCenter) {
                map.setCenter({lat: lat, lng: lng});
                map.setZoom(14);
            } else if (map.googleMap && map.googleMap.setCenter) {
                map.googleMap.setCenter(new google.maps.LatLng(lat, lng));
                map.googleMap.setZoom(14);
            }
        }
    },
    
    // 添加临时标记（需适配 WP Go Maps API）
    addTempMarkers: function(pois) {
        if (typeof WPGMZA === 'undefined' || !WPGMZA.maps || WPGMZA.maps.length === 0) return;
        var map = WPGMZA.maps[0];
        
        pois.forEach(function(poi) {
            // 创建标记数据
            var markerData = {
                lat: poi.lat,
                lng: poi.lng,
                title: poi.name,
                address: poi.address,
                description: '距离: ' + poi.distance + '米',
                link: '',
                icon: '' // 使用默认图标，或指定一个蓝色图标 URL
            };
            
            // 调用 WPGMZA 的添加标记方法
            // 注意：API 随版本变动，尝试通用方法
            if (map.addMarker) {
                map.addMarker(markerData);
            } else if (typeof WPGMZA.Marker !== 'undefined') {
                var marker = new WPGMZA.Marker(markerData);
                map.markers.push(marker);
                marker.map = map;
            }
        });
    }
};

jQuery(document).ready(function($) {
    // 初始化逻辑
});
