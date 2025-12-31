var MusicalbumMap = {
    // 切换标签页
    switchTab: function(tabName) {
        // 更新按钮状态
        jQuery('.musicalbum-map-tab').removeClass('active');
        jQuery('.musicalbum-map-tab[data-tab="' + tabName + '"]').addClass('active');
        
        // 更新面板显示
        jQuery('.musicalbum-map-panel').hide();
        jQuery('#panel-' + tabName).show();
        
        // 根据不同标签页执行特定逻辑
        if (tabName === 'footprints') {
            this.showAllMarkers();
        } else if (tabName === 'nearby') {
            // 自动触发定位？可选，这里先让用户手动点
        }
    },
    
    // 显示所有足迹（恢复默认视图）
    showAllMarkers: function() {
        // 尝试强制刷新 WPGMZA 标记
        if (typeof WPGMZA !== 'undefined') {
            // 方法1: 如果有 reset 接口
            if (WPGMZA.maps && WPGMZA.maps.length > 0) {
                var map = WPGMZA.maps[0];
                if (map.markers) {
                    map.markers.forEach(function(marker) {
                        if (marker.setVisible) marker.setVisible(true);
                    });
                }
                // 重新调整视野以包含所有标记
                if (map.fitBoundsToMarkers) {
                    map.fitBoundsToMarkers();
                }
            }
        } else {
            console.warn('WPGMZA not ready');
        }
    },
    
    // 搜索周边服务（餐饮/交通）
    searchServices: function() {
        var keyword = jQuery('#service-search-keyword').val();
        if (!keyword) {
            alert('请输入关键词');
            return;
        }
        
        // 这里需要获取“当前选中的剧院”位置，或者让用户先在地图上选点
        // 简化版：先用当前地图中心点作为搜索中心
        var center = this.getMapCenter();
        if (!center) {
            alert('无法获取地图中心点');
            return;
        }
        
        this.performSearch(center.lat, center.lng, keyword, '#services-results-list');
    },
    
    // 开始导航（调用高德/百度地图网页版）
    startNavigation: function() {
        var end = jQuery('#nav-end').val();
        if (!end) {
            alert('请输入终点（剧院名称）');
            return;
        }
        
        // 跳转到高德地图 Web 导航
        var url = 'https://uri.amap.com/navigation?to=,,' + encodeURIComponent(end) + '&mode=car&policy=1&src=musicalbum';
        window.open(url, '_blank');
    },

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
            
            MusicalbumMap.performSearch(lat, lng, '', '#nearby-results-list', function() {
                 btn.disabled = false;
                 btn.innerText = originalText;
            });

        }, function(error) {
            alert('定位失败：' + error.message);
            btn.disabled = false;
            btn.innerText = originalText;
        });
    },
    
    // 通用搜索逻辑
    performSearch: function(lat, lng, keyword, resultsContainerId, callback) {
        jQuery(resultsContainerId).html('加载中...');
        
        jQuery.post(MusicalbumMapConfig.ajaxurl, {
            action: 'musicalbum_search_nearby_theaters',
            nonce: MusicalbumMapConfig.nonce,
            lat: lat,
            lng: lng,
            radius: 3000, // 3km
            keyword: keyword // 传空则搜索剧院，传词则搜索服务
        }, function(res) {
            if (res.success) {
                var html = '<ul>';
                res.data.forEach(function(poi) {
                    html += '<li><strong>' + poi.name + '</strong><br/>' + 
                            '<small>' + poi.address + ' (距此' + poi.distance + '米)</small></li>';
                });
                html += '</ul>';
                jQuery(resultsContainerId).html(html);
                
                // 标记地图
                MusicalbumMap.addTempMarkers(res.data);
            } else {
                jQuery(resultsContainerId).html('未找到结果');
            }
            if (callback) callback();
        });
    },
    
    getMapCenter: function() {
        if (typeof WPGMZA !== 'undefined' && WPGMZA.maps && WPGMZA.maps.length > 0) {
            var map = WPGMZA.maps[0];
            if (map.getCenter) {
                var c = map.getCenter();
                // 兼容 OpenLayers 与 Google Maps
                // Google Maps: c.lat(), c.lng() 是函数
                // OpenLayers (WPGMZA): c.lat, c.lng 是属性
                var lat = (typeof c.lat === 'function') ? c.lat() : c.lat;
                var lng = (typeof c.lng === 'function') ? c.lng() : c.lng;
                return {lat: lat, lng: lng};
            }
        }
        return null;
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
