<?php
/**
 * Plugin Name: Musicalbum Theater Maps
 * Description: 剧院地图与周边导航模块，集成 WP Go Maps
 * Version: 1.0.0
 * Author: Musicalbum Team
 * Text Domain: musicalbum-theater-maps
 */

defined('ABSPATH') || exit;

final class Musicalbum_Theater_Maps {

    /**
     * WP Go Maps 的 Marker XML 表名（通常是 wp_wpgmza）
     * 需在初始化时检测
     */
    private static $wpgmza_table = 'wpgmza';

    public static function init() {
        // 注册短码
        add_shortcode('musicalbum_theater_map', [__CLASS__, 'shortcode_theater_map']);
        
        // 注册脚本
        add_action('wp_enqueue_scripts', [__CLASS__, 'enqueue_assets']);

        // 注册 AJAX 操作
        add_action('wp_ajax_musicalbum_batch_geocode', [__CLASS__, 'ajax_batch_geocode']);
        add_action('wp_ajax_musicalbum_search_nearby_theaters', [__CLASS__, 'ajax_search_nearby_theaters']); // 开放给前端（登录用户）
        add_action('wp_ajax_nopriv_musicalbum_search_nearby_theaters', [__CLASS__, 'ajax_search_nearby_theaters']); // 开放给前端（未登录用户）
        
        // 添加后台菜单：地图管理
        add_action('admin_menu', [__CLASS__, 'add_admin_menu']);
        
        // 注册设置
        add_action('admin_init', [__CLASS__, 'register_settings']);
    }

    public static function enqueue_assets() {
        $version = '1.0.3-' . time(); // 强制刷新缓存
        wp_register_style('musicalbum-theater-maps', plugins_url('assets/maps.css', __FILE__), [], $version);
        wp_register_script('musicalbum-theater-maps', plugins_url('assets/maps.js', __FILE__), ['jquery'], $version, true);
        
        // 注入 AJAX URL
        wp_localize_script('musicalbum-theater-maps', 'MusicalbumMapConfig', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('musicalbum_maps_frontend')
        ]);
    }

    public static function add_admin_menu() {
        add_submenu_page(
            'edit.php?post_type=viewing_record', // 挂在观演记录菜单下
            '剧院地图管理',
            '剧院地图',
            'manage_options',
            'musicalbum-theater-maps',
            [__CLASS__, 'render_admin_page']
        );
    }

    public static function register_settings() {
        register_setting('musicalbum_theater_maps', 'musicalbum_amap_key'); // 高德/百度 Key 用于地理编码
        register_setting('musicalbum_theater_maps', 'musicalbum_target_map_id'); // WP Go Maps 的目标地图 ID
    }

    /**
     * 后台管理页面：触发批量地理编码
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) return;
        
        $map_id = get_option('musicalbum_target_map_id', 1);
        $amap_key = get_option('musicalbum_amap_key', '');
        
        ?>
        <div class="wrap">
            <h1>剧院地图集成管理</h1>
            <p>将观演记录中的“剧院”字段自动转换为坐标，并添加到 WP Go Maps 地图中。</p>
            
            <form method="post" action="options.php">
                <?php settings_fields('musicalbum_theater_maps'); ?>
                <?php do_settings_sections('musicalbum_theater_maps'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">目标地图 ID</th>
                        <td><input type="number" name="musicalbum_target_map_id" value="<?php echo esc_attr($map_id); ?>" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">高德地图 Web服务 Key</th>
                        <td>
                            <input type="text" name="musicalbum_amap_key" value="<?php echo esc_attr($amap_key); ?>" class="regular-text" />
                            <p class="description">用于将剧院名称转换为经纬度（地理编码）。请前往高德开放平台申请。</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
            
            <hr />
            
            <h2>批量处理</h2>
            <p>点击下方按钮，扫描所有已发布观演记录的剧院，调用接口获取坐标，并写入 WP Go Maps。</p>
            <button id="musicalbum-sync-markers" class="button button-primary">同步剧院到地图</button>
            <div id="musicalbum-sync-result" style="margin-top:10px; padding:10px; background:#fff; border:1px solid #ccd0d4; display:none;"></div>

            <hr />
            <h2>已同步的剧院标记</h2>
            <p>以下是当前地图（ID: <?php echo esc_html($map_id); ?>）中已存在的标记。如果这里有数据但前台不显示，请尝试点击 WP Go Maps 的“Save Map”以刷新缓存。</p>
            <div id="musicalbum-markers-list">
                <?php 
                global $wpdb;
                $table_name = $wpdb->prefix . 'wpgmza';
                // 检查表是否存在
                if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
                    $markers = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE map_id = %d ORDER BY id DESC LIMIT 50", $map_id));
                    if ($markers) {
                        echo '<table class="widefat fixed striped">';
                        echo '<thead><tr><th>ID</th><th>标题 (剧院名)</th><th>地址</th><th>经纬度</th></tr></thead>';
                        echo '<tbody>';
                        foreach ($markers as $marker) {
                            echo '<tr>';
                            echo '<td>' . esc_html($marker->id) . '</td>';
                            echo '<td>' . esc_html($marker->title) . '</td>';
                            echo '<td>' . esc_html($marker->address) . '</td>';
                            echo '<td>' . esc_html($marker->lat) . ', ' . esc_html($marker->lng) . '</td>';
                            echo '</tr>';
                        }
                        echo '</tbody></table>';
                        if (count($markers) >= 50) echo '<p><em>仅显示最近 50 条...</em></p>';
                    } else {
                        echo '<p>当前地图没有标记。</p>';
                    }
                } else {
                    echo '<p style="color:red">WP Go Maps 数据表不存在。</p>';
                }
                ?>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('#musicalbum-sync-markers').click(function() {
                    var btn = $(this);
                    btn.prop('disabled', true).text('正在同步...');
                    $('#musicalbum-sync-result').show().html('正在扫描剧院数据...');
                    
                    $.post(ajaxurl, {
                        action: 'musicalbum_batch_geocode',
                        nonce: '<?php echo wp_create_nonce('musicalbum_maps_sync'); ?>'
                    }, function(res) {
                        btn.prop('disabled', false).text('同步剧院到地图');
                        if (res.success) {
                            console.log('Sync response:', res); // Debug log
                            var errorHtml = '';
                            if (res.data.errors && res.data.errors.length > 0) {
                                errorHtml = '<div style="margin-top:10px; color:red; max-height:100px; overflow-y:auto; background:#fff0f0; padding:5px; border:1px solid red;"><strong>错误详情：</strong><br/>' + res.data.errors.join('<br/>') + '</div>';
                            } else {
                                // 如果没有 errors 字段，可能是旧结构，强制显示
                                errorHtml = '<div style="margin-top:10px; color:blue; background:#f0f8ff; padding:5px;"><strong>调试信息：</strong><br/>Total: ' + res.data.total + '<br/>Success: ' + res.data.success + '<br/>Skipped: ' + res.data.skipped + '</div>';
                            }
                            
                            $('#musicalbum-sync-result').html(
                                '<p style="color:green">同步完成！</p>' + 
                                '<ul>' +
                                '<li>发现剧院：' + res.data.total + ' 个</li>' +
                                '<li>成功编码：' + res.data.success + ' 个</li>' +
                                '<li>跳过/失败：' + res.data.skipped + ' 个</li>' +
                                '</ul>' + 
                                errorHtml +
                                '<p><strong>重要：</strong>请刷新本页查看下方已同步列表。如果列表中有数据但前台不显示，请务必去 Maps -> Edit -> Save Map 刷新缓存。</p>'
                            );
                        } else {
                            $('#musicalbum-sync-result').html('<p style="color:red">错误：' + res.data + '</p>');
                        }
                    }).fail(function() {
                        btn.prop('disabled', false).text('同步剧院到地图');
                        $('#musicalbum-sync-result').html('<p style="color:red">请求失败，请检查网络或服务器日志。</p>');
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    /**
     * AJAX 处理：批量地理编码与标记写入
     */
    public static function ajax_batch_geocode() {
        if (!current_user_can('manage_options')) wp_send_json_error('权限不足');
        check_ajax_referer('musicalbum_maps_sync', 'nonce');
        
        global $wpdb;
        $map_id = get_option('musicalbum_target_map_id', 1);
        $amap_key = get_option('musicalbum_amap_key', '');
        
        if (empty($amap_key)) wp_send_json_error('请先配置高德地图 Key');
        
        // 1. 获取所有唯一剧院名称
        // 这里假设 'theater' 是 ACF 字段，存储在 postmeta 中
        // 为了兼容性，先查所有 view_record / musicalbum_viewing
        $theaters = $wpdb->get_col("
            SELECT DISTINCT meta_value 
            FROM {$wpdb->postmeta} pm
            JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type IN ('viewing_record', 'musicalbum_viewing') 
            AND p.post_status = 'publish'
            AND pm.meta_key = 'theater'
            AND pm.meta_value != ''
        ");
        
        if (empty($theaters)) wp_send_json_error('未找到任何剧院数据');
        
        // WP Go Maps 表名 (通常是 wp_wpgmza)
        $table_name = $wpdb->prefix . 'wpgmza';
        
        // 检查表是否存在
        if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
            wp_send_json_error("WP Go Maps 数据表 ($table_name) 不存在，请确认插件已激活");
        }
        
        // 关键修正：在同步前，先清理该地图ID下的所有旧标记
        // 避免“加州默认标记”或重复旧数据干扰
        $wpdb->delete($table_name, ['map_id' => $map_id]);
        
        $stats = ['total' => count($theaters), 'success' => 0, 'skipped' => 0, 'errors' => []];
        
        foreach ($theaters as $theater) {
            // 检查是否已存在该名称的标记（避免重复）
            $exists = $wpdb->get_var($wpdb->prepare("SELECT id FROM $table_name WHERE map_id = %d AND title = %s", $map_id, $theater));
            if ($exists) {
                $stats['skipped']++;
                continue;
            }
            
            // 调用高德 API 进行地理编码
            $geo = self::geocode_amap($theater, $amap_key);
            if ($geo) {
                // 写入 WP Go Maps 表
                // 注意：新版 WP Go Maps (9.x+) 使用 Spatial 字段 (latlng/geom)，需要使用 ST_GeomFromText
                // 但直接使用 $wpdb->insert 不支持 MySQL 函数
                // 所以需要改用 $wpdb->query
                
                // 1. 尝试检测是否存在 'latlng' 或 'geom' 字段
                // 简单起见，直接使用 raw SQL 插入，兼容 Spatial 字段
                
                $sql = $wpdb->prepare(
                    "INSERT INTO $table_name 
                    (map_id, address, lat, lng, title, description, link, anim, infoopen, category, approved, retina, type, did, sticky, other_data, latlng) 
                    VALUES 
                    (%d, %s, %f, %f, %s, %s, %s, %d, %d, %d, %d, %d, %d, %s, %d, %s, ST_GeomFromText(%s))",
                    $map_id,
                    $theater,
                    $geo['lat'],
                    $geo['lng'],
                    $theater,
                    '我的观演足迹',
                    '',
                    0, 0, 0, 1, 0, 0, '', 0, '',
                    "POINT(" . $geo['lat'] . " " . $geo['lng'] . ")" // 注意：WPGMZA 可能期望 POINT(lat lng) 或 POINT(lng lat)，通常是 POINT(lat lng)
                );
                
                $inserted = $wpdb->query($sql);
                
                if ($inserted) {
                    $stats['success']++;
                } else {
                    // 如果第一次插入失败（可能是因为没有 latlng 字段，即旧版本），则尝试普通插入
                    $wpdb->last_error = ''; // 清除错误
                    $inserted_fallback = $wpdb->insert(
                        $table_name,
                        [
                            'map_id' => $map_id,
                            'address' => $theater,
                            'lat' => $geo['lat'],
                            'lng' => $geo['lng'],
                            'title' => $theater,
                            'description' => '我的观演足迹',
                            'link' => '',
                            'anim' => 0,
                            'infoopen' => 0,
                            'category' => 0,
                            'approved' => 1,
                            'retina' => 0,
                            'type' => 0,
                            'did' => '',
                            'sticky' => 0,
                            'other_data' => ''
                        ]
                    );
                    
                    if ($inserted_fallback) {
                        $stats['success']++;
                    } else {
                        $stats['skipped']++;
                        $stats['errors'][] = "Insert failed for $theater: " . $wpdb->last_error;
                    }
                }
            } else {
                $stats['skipped']++;
                $stats['errors'][] = "Geocoding failed for $theater";
            }
            
            // 避免 API 速率限制
            usleep(200000); // 0.2s
        }
        
        wp_send_json_success($stats);
    }
    
    /**
     * 高德地图地理编码
     */
    private static function geocode_amap($address, $key) {
        $url = 'https://restapi.amap.com/v3/geocode/geo?key=' . $key . '&address=' . urlencode($address);
        $resp = wp_remote_get($url);
        if (is_wp_error($resp)) return false;
        
        $body = wp_remote_retrieve_body($resp);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] == '1' && !empty($data['geocodes'])) {
            $location = $data['geocodes'][0]['location'];
            list($lng, $lat) = explode(',', $location);
            return ['lat' => $lat, 'lng' => $lng];
        }
        
        return false;
    }
    
    /**
     * AJAX: 调用高德周边搜索 API
     */
    public static function ajax_search_nearby_theaters() {
        check_ajax_referer('musicalbum_maps_frontend', 'nonce');
        
        $lat = isset($_POST['lat']) ? floatval($_POST['lat']) : 0;
        $lng = isset($_POST['lng']) ? floatval($_POST['lng']) : 0;
        $radius = isset($_POST['radius']) ? intval($_POST['radius']) : 5000; // 默认5公里
        $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
        
        $amap_key = get_option('musicalbum_amap_key', '');
        if (empty($amap_key)) wp_send_json_error('服务端未配置高德 Key');
        
        // 如果有关键词，调用关键词搜索；否则调用周边搜索
        if (!empty($keyword)) {
            // 关键词周边搜索
            $url = 'https://restapi.amap.com/v3/place/around?key=' . $amap_key . 
                   '&location=' . $lng . ',' . $lat . 
                   '&radius=' . $radius . 
                   '&keywords=' . urlencode($keyword) .
                   '&offset=20&page=1&extensions=all';
        } else {
            // 纯周边搜索（剧院）
            // 优化：使用关键词搜索 "剧院|剧场|音乐厅|大剧院" 替代纯分类搜索
            // 因为 types=140000 包含了很多非剧院的文体设施（如健身房、彩票店）
            $url = 'https://restapi.amap.com/v3/place/around?key=' . $amap_key . 
                   '&location=' . $lng . ',' . $lat . 
                   '&radius=' . $radius . 
                   '&keywords=' . urlencode('剧院|剧场|音乐厅|大剧院|演艺中心') . 
                   '&types=140100' . // 配合分类限制
                   '&offset=20&page=1&extensions=all';
        }
               
        $resp = wp_remote_get($url);
        if (is_wp_error($resp)) wp_send_json_error('高德 API 请求失败');
        
        $body = wp_remote_retrieve_body($resp);
        $data = json_decode($body, true);
        
        if (isset($data['status']) && $data['status'] == '1') {
            $pois = [];
            if (!empty($data['pois'])) {
                foreach ($data['pois'] as $poi) {
                    $location = explode(',', $poi['location']);
                    $pois[] = [
                        'name' => $poi['name'],
                        'address' => isset($poi['address']) ? $poi['address'] : '',
                        'lat' => $location[1],
                        'lng' => $location[0],
                        'distance' => isset($poi['distance']) ? $poi['distance'] : 0,
                        'photos' => isset($poi['photos']) ? $poi['photos'] : []
                    ];
                }
            }
            wp_send_json_success($pois);
        } else {
            wp_send_json_error('API返回错误: ' . (isset($data['info']) ? $data['info'] : '未知错误'));
        }
    }

    /**
     * 前端短码：显示地图
     * 实际上是 WP Go Maps 短码的包装，但可以添加自定义容器或样式
     */
    public static function shortcode_theater_map($atts) {
        $atts = shortcode_atts(['map_id' => get_option('musicalbum_target_map_id', 1)], $atts);
        $map_id = $atts['map_id'];
        
        wp_enqueue_style('musicalbum-theater-maps');
        wp_enqueue_script('musicalbum-theater-maps');
        
        ob_start();
        echo '<div class="musicalbum-theater-map-container">';
        echo '<h2>剧院服务地图</h2>';
        
        // 功能导航栏
        echo '<div class="musicalbum-map-nav">';
        echo '<button class="musicalbum-map-tab active" data-tab="footprints" onclick="MusicalbumMap.switchTab(\'footprints\')">观演足迹</button>';
        echo '<button class="musicalbum-map-tab" data-tab="nearby" onclick="MusicalbumMap.switchTab(\'nearby\')">查找附近剧院</button>';
        echo '<button class="musicalbum-map-tab" data-tab="services" onclick="MusicalbumMap.switchTab(\'services\')">剧院周边服务</button>';
        echo '<button class="musicalbum-map-tab" data-tab="navigation" onclick="MusicalbumMap.switchTab(\'navigation\')">路线导航</button>';
        echo '</div>';
        
        echo '<div class="musicalbum-map-wrapper">';
        echo do_shortcode('[wpgmza id="' . esc_attr($map_id) . '"]');
        echo '</div>';
        
        // 控制面板区域
        echo '<div class="musicalbum-map-controls-panel">';
        
        // 1. 观演足迹面板
        echo '<div id="panel-footprints" class="musicalbum-map-panel active">';
        echo '<p>展示您所有观演记录中的剧院分布。点击标记可查看剧院详情。</p>';
        echo '<button class="musicalbum-btn" onclick="MusicalbumMap.showAllMarkers()">显示所有足迹</button>';
        echo '</div>';
        
        // 2. 查找附近剧院面板
        echo '<div id="panel-nearby" class="musicalbum-map-panel" style="display:none;">';
        echo '<p>查找您当前位置周边的真实剧院。</p>';
        echo '<button class="musicalbum-btn musicalbum-nearby-btn" onclick="MusicalbumMap.findNearby()">开始搜索周边剧院</button>';
        echo '<div id="nearby-results-list" class="musicalbum-results-list"></div>';
        echo '</div>';
        
        // 3. 剧院周边服务面板
        echo '<div id="panel-services" class="musicalbum-map-panel" style="display:none;">';
        echo '<p>选择一个剧院，查找周边的餐饮、交通设施。</p>';
        echo '<div class="musicalbum-form-inline">';
        echo '<input type="text" id="service-search-keyword" placeholder="例如：咖啡、地铁站、停车场" />';
        echo '<button class="musicalbum-btn" onclick="MusicalbumMap.searchServices()">搜索周边</button>';
        echo '</div>';
        echo '<div id="services-results-list" class="musicalbum-results-list"></div>';
        echo '</div>';
        
        // 4. 路线导航面板
        echo '<div id="panel-navigation" class="musicalbum-map-panel" style="display:none;">';
        echo '<p>规划前往目标剧院的路线。</p>';
        echo '<div class="musicalbum-nav-form">';
        echo '<div class="form-row"><label>起点：</label><span id="nav-start">我的位置</span></div>';
        echo '<div class="form-row"><label>终点：</label><input type="text" id="nav-end" placeholder="点击地图选择或输入剧院名" /></div>';
        echo '<button class="musicalbum-btn" onclick="MusicalbumMap.startNavigation()">开始导航</button>';
        echo '</div>';
        echo '<div id="nav-instructions" class="musicalbum-nav-instructions"></div>';
        echo '</div>';
        
        echo '</div>'; // end controls panel
        echo '</div>'; // end container
        return ob_get_clean();
    }
}

Musicalbum_Theater_Maps::init();
