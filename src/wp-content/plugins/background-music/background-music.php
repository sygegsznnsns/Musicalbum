<?php
/*
Plugin Name: Background Music
Description: 网站背景音乐播放器插件，支持音乐选择、上传和管理，可拖拽的播放器控件。
Version: 1.0.0
Author: chen ziang
*/

defined('ABSPATH') || exit;

/**
 * 背景音乐插件主类
 */
final class Background_Music {
    
    private static $instance = null;
    private $plugin_url;
    private $plugin_path;
    private $plugin_version = '1.0.0';
    
    /**
     * 获取单例实例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 构造函数
     */
    private function __construct() {
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);
        
        // 初始化插件
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * 初始化插件
     */
    public function init() {
        // 入队前端资源
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // 注册设置
        add_action('admin_init', array($this, 'register_settings'));
        
        // 添加后台菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // AJAX处理
        add_action('wp_ajax_save_music_list', array($this, 'ajax_save_music_list'));
        
        // 在footer输出播放器
        add_action('wp_footer', array($this, 'render_player'));
    }
    
    /**
     * 入队前端资源
     */
    public function enqueue_assets() {
        wp_enqueue_style(
            'background-music-style',
            $this->plugin_url . 'assets/background-music.css',
            array(),
            $this->plugin_version
        );
        
        wp_enqueue_script(
            'background-music-script',
            $this->plugin_url . 'assets/background-music.js',
            array(),
            $this->plugin_version,
            true
        );
    }
    
    /**
     * 注册设置
     */
    public function register_settings() {
        register_setting('background_music_options', 'background_music_url');
        register_setting('background_music_options', 'background_music_list');
        register_setting('background_music_options', 'selected_music_id');
        register_setting('background_music_options', 'preset_music_1_url');
        register_setting('background_music_options', 'preset_music_2_url');
        register_setting('background_music_options', 'preset_music_3_url');
        
        add_settings_section(
            'background_music_section',
            '背景音乐设置',
            function() {
                echo '<p>设置网站的背景音乐。您可以选择预设音乐，或上传自己的音乐文件。所有音乐都可以从媒体库选择。</p>';
            },
            'background_music_options'
        );
        
        add_settings_field(
            'selected_music_id',
            '当前使用的音乐',
            array($this, 'render_music_select_field'),
            'background_music_options',
            'background_music_section'
        );
        
        add_settings_field(
            'preset_musics',
            '预设音乐设置',
            array($this, 'render_preset_musics_field'),
            'background_music_options',
            'background_music_section'
        );
        
        add_settings_field(
            'background_music_list',
            '我的音乐库',
            array($this, 'render_music_list_field'),
            'background_music_options',
            'background_music_section'
        );
    }
    
    /**
     * 渲染音乐选择字段
     */
    public function render_music_select_field() {
        $selected_id = get_option('selected_music_id', '');
        $music_list = get_option('background_music_list', array());
        $preset_musics = $this->get_preset_musics();
        
        echo '<select name="selected_music_id" id="selected_music_id" class="regular-text">';
        echo '<option value="">-- 请选择音乐 --</option>';
        
        // 显示预设音乐（只有设置了URL的才会显示）
        if (!empty($preset_musics)) {
            echo '<optgroup label="预设音乐">';
            foreach ($preset_musics as $id => $music) {
                $selected = ($selected_id === $id) ? 'selected' : '';
                echo '<option value="' . esc_attr($id) . '" ' . $selected . '>' . esc_html($music['name']) . '</option>';
            }
            echo '</optgroup>';
        }
        
        // 显示用户上传的音乐
        if (!empty($music_list) && is_array($music_list)) {
            echo '<optgroup label="我的音乐">';
            foreach ($music_list as $id => $music) {
                $selected = ($selected_id === $id) ? 'selected' : '';
                $name = isset($music['name']) ? $music['name'] : '未命名音乐';
                echo '<option value="' . esc_attr($id) . '" ' . $selected . '>' . esc_html($name) . '</option>';
            }
            echo '</optgroup>';
        }
        
        echo '</select>';
        echo '<p class="description">从下拉菜单中选择要使用的背景音乐。</p>';
    }
    
    /**
     * 渲染音乐列表字段
     */
    public function render_music_list_field() {
        $music_list = get_option('background_music_list', array());
        echo '<div id="music-list-container">';
        if (!empty($music_list) && is_array($music_list)) {
            echo '<table class="wp-list-table widefat fixed striped">';
            echo '<thead><tr><th>音乐名称</th><th>URL</th><th>操作</th></tr></thead>';
            echo '<tbody>';
            foreach ($music_list as $id => $music) {
                $name = isset($music['name']) ? $music['name'] : '未命名音乐';
                $url = isset($music['url']) ? $music['url'] : '';
                echo '<tr>';
                echo '<td><strong>' . esc_html($name) . '</strong></td>';
                echo '<td><code style="font-size:11px;">' . esc_html($url) . '</code></td>';
                echo '<td><button type="button" class="button button-small delete-music" data-id="' . esc_attr($id) . '">删除</button></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>您还没有上传任何音乐文件。</p>';
        }
        echo '</div>';
        echo '<p><button type="button" class="button" id="add-music-btn">+ 添加新音乐</button></p>';
    }
    
    /**
     * 渲染预设音乐设置字段
     */
    public function render_preset_musics_field() {
        $preset_1_url = get_option('preset_music_1_url', '');
        $preset_2_url = get_option('preset_music_2_url', '');
        $preset_3_url = get_option('preset_music_3_url', '');
        
        echo '<table class="form-table">';
        echo '<tr>';
        echo '<th><label for="preset_music_1_url">预设音乐 1</label></th>';
        echo '<td>';
        echo '<input type="url" name="preset_music_1_url" id="preset_music_1_url" value="' . esc_attr($preset_1_url) . '" class="regular-text" placeholder="从媒体库选择音频文件URL" />';
        echo '<p class="description">上传音频文件到<a href="' . admin_url('media-new.php') . '" target="_blank">媒体库</a>，然后复制文件URL粘贴到这里。留空则不显示此预设音乐。</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="preset_music_2_url">预设音乐 2</label></th>';
        echo '<td>';
        echo '<input type="url" name="preset_music_2_url" id="preset_music_2_url" value="' . esc_attr($preset_2_url) . '" class="regular-text" placeholder="从媒体库选择音频文件URL" />';
        echo '<p class="description">上传音频文件到<a href="' . admin_url('media-new.php') . '" target="_blank">媒体库</a>，然后复制文件URL粘贴到这里。留空则不显示此预设音乐。</p>';
        echo '</td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th><label for="preset_music_3_url">预设音乐 3</label></th>';
        echo '<td>';
        echo '<input type="url" name="preset_music_3_url" id="preset_music_3_url" value="' . esc_attr($preset_3_url) . '" class="regular-text" placeholder="从媒体库选择音频文件URL" />';
        echo '<p class="description">上传音频文件到<a href="' . admin_url('media-new.php') . '" target="_blank">媒体库</a>，然后复制文件URL粘贴到这里。留空则不显示此预设音乐。</p>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
    }
    
    /**
     * 获取预设音乐列表
     */
    private function get_preset_musics() {
        $preset_1_url = get_option('preset_music_1_url', '');
        $preset_2_url = get_option('preset_music_2_url', '');
        $preset_3_url = get_option('preset_music_3_url', '');
        
        $presets = array();
        
        // 只有设置了URL的预设音乐才会显示
        if (!empty($preset_1_url)) {
            $presets['preset_1'] = array(
                'name' => '预设音乐 1',
                'url' => $preset_1_url
            );
        }
        
        if (!empty($preset_2_url)) {
            $presets['preset_2'] = array(
                'name' => '预设音乐 2',
                'url' => $preset_2_url
            );
        }
        
        if (!empty($preset_3_url)) {
            $presets['preset_3'] = array(
                'name' => '预设音乐 3',
                'url' => $preset_3_url
            );
        }
        
        return $presets;
    }
    
    /**
     * 添加后台菜单
     */
    public function add_admin_menu() {
        add_options_page(
            '背景音乐设置',
            '背景音乐',
            'manage_options',
            'background-music',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * 渲染后台设置页面
     */
    public function render_admin_page() {
        ?>
        <div class="wrap">
            <h1>背景音乐设置</h1>
            <form method="post" action="options.php" id="music-settings-form">
                <?php
                settings_fields('background_music_options');
                do_settings_sections('background_music_options');
                submit_button();
                ?>
            </form>
            
            <!-- 添加音乐对话框 -->
            <div id="add-music-dialog" style="display:none; margin-top:20px; padding:20px; background:#f9f9f9; border:1px solid #ddd; border-radius:4px;">
                <h3>添加新音乐</h3>
                <table class="form-table">
                    <tr>
                        <th><label for="new_music_name">音乐名称</label></th>
                        <td><input type="text" id="new_music_name" class="regular-text" placeholder="例如：我的背景音乐" /></td>
                    </tr>
                    <tr>
                        <th><label for="new_music_url">音频文件URL</label></th>
                        <td>
                            <input type="url" id="new_music_url" class="regular-text" placeholder="https://example.com/music.mp3" />
                            <p class="description">
                                <a href="<?php echo admin_url('media-new.php'); ?>" target="_blank">上传音频文件到媒体库</a>，然后复制文件URL粘贴到这里。
                            </p>
                        </td>
                    </tr>
                </table>
                <p>
                    <button type="button" class="button button-primary" id="save-new-music">保存音乐</button>
                    <button type="button" class="button" id="cancel-add-music">取消</button>
                </p>
            </div>
            
            <hr>
            <h2>使用说明</h2>
            <ol>
                <li><strong>设置预设音乐（推荐）：</strong>
                    <ul>
                        <li>在"预设音乐设置"部分，为每个预设音乐设置URL</li>
                        <li>上传音频文件到<a href="<?php echo admin_url('media-new.php'); ?>" target="_blank">媒体库</a></li>
                        <li>复制音频文件的URL，粘贴到对应的预设音乐输入框</li>
                        <li>点击"保存更改"</li>
                        <li>预设音乐会自动出现在"当前使用的音乐"下拉列表中</li>
                    </ul>
                </li>
                <li><strong>添加自定义音乐：</strong>
                    <ul>
                        <li>点击"添加新音乐"按钮</li>
                        <li>上传音频文件到<a href="<?php echo admin_url('media-new.php'); ?>" target="_blank">媒体库</a></li>
                        <li>复制音频文件的URL</li>
                        <li>在添加音乐对话框中输入音乐名称和URL</li>
                        <li>点击"保存音乐"</li>
                    </ul>
                </li>
                <li><strong>选择音乐：</strong>从"当前使用的音乐"下拉菜单中选择要使用的音乐，然后点击"保存更改"</li>
                <li><strong>删除音乐：</strong>在"我的音乐库"中点击"删除"按钮可以移除不需要的音乐</li>
            </ol>
            <p><strong>提示：</strong>所有音乐都可以从媒体库选择，只需上传文件后复制URL即可。</p>
        </div>
        
        <script type="text/javascript">
        var ajaxurl = ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>';
        
        jQuery(document).ready(function($) {
            // 显示添加音乐对话框
            $('#add-music-btn').on('click', function() {
                $('#add-music-dialog').slideDown();
                $('#new_music_name').focus();
            });
            
            // 隐藏添加音乐对话框
            $('#cancel-add-music').on('click', function() {
                $('#add-music-dialog').slideUp();
                $('#new_music_name').val('');
                $('#new_music_url').val('');
            });
            
            // 保存新音乐
            $('#save-new-music').on('click', function() {
                var name = $('#new_music_name').val().trim();
                var url = $('#new_music_url').val().trim();
                
                if (!name) {
                    alert('请输入音乐名称');
                    return;
                }
                
                if (!url) {
                    alert('请输入音频文件URL');
                    return;
                }
                
                var musicList = <?php echo json_encode(get_option('background_music_list', array())); ?>;
                if (!musicList) musicList = {};
                
                var newId = 'custom_' + Date.now();
                musicList[newId] = {
                    name: name,
                    url: url
                };
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'save_music_list',
                        music_list: JSON.stringify(musicList),
                        nonce: '<?php echo wp_create_nonce('save_music_list'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('保存失败：' + (response.data || '未知错误'));
                        }
                    },
                    error: function() {
                        alert('保存失败，请重试');
                    }
                });
            });
            
            // 删除音乐
            $(document).on('click', '.delete-music', function() {
                if (!confirm('确定要删除这首音乐吗？')) {
                    return;
                }
                
                var musicId = $(this).data('id');
                var musicList = <?php echo json_encode(get_option('background_music_list', array())); ?>;
                
                if (musicList[musicId]) {
                    delete musicList[musicId];
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'save_music_list',
                            music_list: JSON.stringify(musicList),
                            nonce: '<?php echo wp_create_nonce('save_music_list'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                location.reload();
                            } else {
                                alert('删除失败：' + (response.data || '未知错误'));
                            }
                        },
                        error: function() {
                            alert('删除失败，请重试');
                        }
                    });
                }
            });
        });
        </script>
        <?php
    }
    
    /**
     * AJAX处理：保存音乐列表
     */
    public function ajax_save_music_list() {
        check_ajax_referer('save_music_list', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('权限不足');
            return;
        }
        
        $music_list_json = isset($_POST['music_list']) ? $_POST['music_list'] : '';
        $music_list = json_decode(stripslashes($music_list_json), true);
        
        if (is_array($music_list)) {
            update_option('background_music_list', $music_list);
            wp_send_json_success('保存成功');
        } else {
            wp_send_json_error('数据格式错误');
        }
    }
    
    /**
     * 在footer渲染播放器
     */
    public function render_player() {
        $selected_id = get_option('selected_music_id', '');
        $music_url = '';
        $preset_musics = $this->get_preset_musics();
        
        // 根据选择的ID获取音乐URL
        if (!empty($selected_id)) {
            if (isset($preset_musics[$selected_id])) {
                $music_url = $preset_musics[$selected_id]['url'];
            } else {
                $music_list = get_option('background_music_list', array());
                if (isset($music_list[$selected_id]) && isset($music_list[$selected_id]['url'])) {
                    $music_url = $music_list[$selected_id]['url'];
                }
            }
        }
        
        // 如果还没有选择，尝试使用旧的设置
        if (empty($music_url)) {
            $music_url = get_option('background_music_url', '');
        }
        
        // 如果URL为空，不显示播放器
        if (empty($music_url)) {
            return;
        }
        
        // 获取音乐名称
        $music_name = '背景音乐';
        if (!empty($selected_id)) {
            if (isset($preset_musics[$selected_id])) {
                $music_name = $preset_musics[$selected_id]['name'];
            } else {
                $music_list = get_option('background_music_list', array());
                if (isset($music_list[$selected_id]) && isset($music_list[$selected_id]['name'])) {
                    $music_name = $music_list[$selected_id]['name'];
                }
            }
        }
        ?>
        <!-- 背景音乐播放器 -->
        <audio id="background-music" loop preload="auto">
            <source src="<?php echo esc_url($music_url); ?>" type="audio/mpeg">
        </audio>
        
        <div id="background-music-player">
            <button id="music-play-pause" aria-label="播放背景音乐">
                <span class="music-icon">▶</span>
            </button>
            <div id="music-volume-control">
                <span id="music-volume-icon">🔊</span>
                <input type="range" id="music-volume" min="0" max="1" step="0.01" value="0.5" aria-label="音量控制">
            </div>
        </div>
        
        <div id="music-info" style="display: none; opacity: 0;">
            背景音乐已加载
        </div>
        
        <script>
        window.backgroundMusicData = {
            url: <?php echo json_encode($music_url); ?>,
            name: <?php echo json_encode($music_name); ?>
        };
        </script>
        <?php
    }
}

// 初始化插件
Background_Music::get_instance();

