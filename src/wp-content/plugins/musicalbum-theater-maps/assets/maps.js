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
                var errMsg = res.data || '未知错误';
                jQuery(resultsContainerId).html('<p style="color:red; padding:10px; background:#ffebeb;">搜索失败: ' + errMsg + '</p>');
                console.error('Map Search Error:', errMsg);
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
    
    nativeMarkers: [], // 缓存原生标记

    // 切换原生（数据库）标记的可见性
    toggleNativeMarkers: function(visible) {
        if (typeof WPGMZA !== 'undefined' && WPGMZA.maps && WPGMZA.maps.length > 0) {
            var map = WPGMZA.maps[0];
            
            // 首次运行时，缓存当前地图上的所有非临时标记
            if (this.nativeMarkers.length === 0 && map.markers && map.markers.length > 0) {
                var self = this;
                map.markers.forEach(function(m) {
                    if (!m.isTemp) {
                        self.nativeMarkers.push(m);
                    }
                });
                console.log('MusicalbumMap: Cached ' + this.nativeMarkers.length + ' native markers.');
            }
            
            // 如果缓存为空，尝试重新获取（应对异步加载情况）
            if (this.nativeMarkers.length === 0 && map.markers) {
                 var self = this;
                 map.markers.forEach(function(m) {
                    if (!m.isTemp && self.nativeMarkers.indexOf(m) === -1) {
                        self.nativeMarkers.push(m);
                    }
                });
            }

            console.log('MusicalbumMap: Toggling native markers to ' + visible);

            this.nativeMarkers.forEach(function(marker) {
                if (visible) {
                    // === 显示逻辑 ===
                    if (marker.setVisible) marker.setVisible(true);
                    
                    // 如果被移除了，加回来
                    // Google Maps check
                    if (marker.getMap && !marker.getMap()) {
                        if (marker.setMap) marker.setMap(map);
                    }
                    // OpenLayers check (hacky)
                    else if (map.markers.indexOf(marker) === -1) {
                        if (map.addMarker) map.addMarker(marker);
                    }
                    
                    // OL Style Restore
                    if (marker.feature && marker.feature.setStyle && typeof ol !== 'undefined') {
                        marker.feature.setStyle(null);
                    }
                    
                    // Opacity
                    if (marker.setOpacity) marker.setOpacity(1);

                } else {
                    // === 隐藏逻辑 ===
                    if (marker.setVisible) marker.setVisible(false);
                    
                    // 彻底移除 (OpenLayers 有时 setVisible 不起作用)
                    if (map.removeMarker) {
                         // 注意：removeMarker 会从 map.markers 移除，但不销毁对象
                         map.removeMarker(marker);
                    } else if (marker.setMap) {
                        marker.setMap(null);
                    }

                    // OL Style Hide
                    if (marker.feature && marker.feature.setStyle && typeof ol !== 'undefined') {
                        marker.feature.setStyle(new ol.style.Style({}));
                    }
                    
                    // Opacity
                    if (marker.setOpacity) marker.setOpacity(0);
                }
            });
        }
    },
    
    // 添加临时标记（需适配 WP Go Maps API）
    addTempMarkers: function(pois) {
        // 先清除旧的临时标记
        this.clearTempMarkers();

        if (typeof WPGMZA === 'undefined' || !WPGMZA.maps || WPGMZA.maps.length === 0) return;
        var map = WPGMZA.maps[0];
        var self = this;
        
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
            var marker;
            if (typeof WPGMZA.Marker !== 'undefined') {
                marker = new WPGMZA.Marker(markerData);
            } else {
                return; 
            }
            
            marker.isTemp = true; // 标记为临时

            if (map.addMarker) {
                map.addMarker(marker);
            } else {
                map.markers.push(marker);
                marker.map = map;
            }
            
            self.tempMarkers.push(marker);
        });

        // 自动缩放以显示新标记
        // 简单的自动缩放逻辑：收集所有点，计算 bounds
        // 由于 WPGMZA API 差异，这里暂时不做复杂的 bounds 计算，或者依赖 fitBoundsToMarkers 但那会包含隐藏的 native markers
        // 尝试只聚焦到第一个结果
        if (pois.length > 0) {
            this.centerMap(pois[0].lat, pois[0].lng);
        }
    }
};

jQuery(document).ready(function($) {
    // 初始化逻辑
});
