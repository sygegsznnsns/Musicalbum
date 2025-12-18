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
        register_setting('background_music_options', 'selected_music_id');
        register_setting('background_music_options', 'preset_music_1_url');
        register_setting('background_music_options', 'preset_music_1_name');
        register_setting('background_music_options', 'preset_music_2_url');
        register_setting('background_music_options', 'preset_music_2_name');
        register_setting('background_music_options', 'preset_music_3_url');
        register_setting('background_music_options', 'preset_music_3_name');
        
        add_settings_section(
            'background_music_section',
            '背景音乐设置',
            function() {
                echo '<p>设置网站的背景音乐。您可以从媒体库选择音频文件，设置为预设音乐。</p>';
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
    }
    
    /**
     * 渲染音乐选择字段
     */
    public function render_music_select_field() {
        $selected_id = get_option('selected_music_id', '');
        $preset_musics = $this->get_preset_musics();
        
        echo '<select name="selected_music_id" id="selected_music_id" class="regular-text">';
        echo '<option value="">-- 请选择音乐 --</option>';
        
        // 显示预设音乐（只有设置了URL的才会显示）
        if (!empty($preset_musics)) {
            foreach ($preset_musics as $id => $music) {
                $selected = ($selected_id === $id) ? 'selected' : '';
                echo '<option value="' . esc_attr($id) . '" ' . $selected . '>' . esc_html($music['name']) . '</option>';
            }
        } else {
            echo '<option value="" disabled>请先在下方设置预设音乐</option>';
        }
        
        echo '</select>';
        echo '<p class="description">从下拉菜单中选择要使用的背景音乐。请先在"预设音乐设置"中配置音乐URL。</p>';
    }
    
    /**
     * 渲染预设音乐设置字段
     */
    public function render_preset_musics_field() {
        $preset_1_url = get_option('preset_music_1_url', '');
        $preset_1_name = get_option('preset_music_1_name', '');
        $preset_2_url = get_option('preset_music_2_url', '');
        $preset_2_name = get_option('preset_music_2_name', '');
        $preset_3_url = get_option('preset_music_3_url', '');
        $preset_3_name = get_option('preset_music_3_name', '');
        
        echo '<table class="form-table">';
        
        // 预设音乐 1
        echo '<tr>';
        echo '<th><label for="preset_music_1_name">预设音乐 1</label></th>';
        echo '<td>';
        echo '<div style="margin-bottom: 8px;">';
        echo '<label for="preset_music_1_name" style="display: block; margin-bottom: 4px; font-weight: 500;">显示名称：</label>';
        echo '<input type="text" name="preset_music_1_name" id="preset_music_1_name" value="' . esc_attr($preset_1_name) . '" class="regular-text" placeholder="例如：轻松音乐" style="max-width: 300px;" />';
        echo '<p class="description" style="margin-top: 4px;">这个名称会显示在前端音乐选择下拉菜单中，让用户知道这是什么音乐。</p>';
        echo '</div>';
        echo '<div>';
        echo '<label for="preset_music_1_url" style="display: block; margin-bottom: 4px; font-weight: 500;">音频文件URL：</label>';
        echo '<input type="url" name="preset_music_1_url" id="preset_music_1_url" value="' . esc_attr($preset_1_url) . '" class="regular-text" placeholder="从媒体库选择音频文件URL" />';
        echo '<p class="description" style="margin-top: 4px;">上传音频文件到<a href="' . admin_url('media-new.php') . '" target="_blank">媒体库</a>，然后复制文件URL粘贴到这里。留空则不显示此预设音乐。</p>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        
        // 预设音乐 2
        echo '<tr>';
        echo '<th><label for="preset_music_2_name">预设音乐 2</label></th>';
        echo '<td>';
        echo '<div style="margin-bottom: 8px;">';
        echo '<label for="preset_music_2_name" style="display: block; margin-bottom: 4px; font-weight: 500;">显示名称：</label>';
        echo '<input type="text" name="preset_music_2_name" id="preset_music_2_name" value="' . esc_attr($preset_2_name) . '" class="regular-text" placeholder="例如：工作音乐" style="max-width: 300px;" />';
        echo '<p class="description" style="margin-top: 4px;">这个名称会显示在前端音乐选择下拉菜单中，让用户知道这是什么音乐。</p>';
        echo '</div>';
        echo '<div>';
        echo '<label for="preset_music_2_url" style="display: block; margin-bottom: 4px; font-weight: 500;">音频文件URL：</label>';
        echo '<input type="url" name="preset_music_2_url" id="preset_music_2_url" value="' . esc_attr($preset_2_url) . '" class="regular-text" placeholder="从媒体库选择音频文件URL" />';
        echo '<p class="description" style="margin-top: 4px;">上传音频文件到<a href="' . admin_url('media-new.php') . '" target="_blank">媒体库</a>，然后复制文件URL粘贴到这里。留空则不显示此预设音乐。</p>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        
        // 预设音乐 3
        echo '<tr>';
        echo '<th><label for="preset_music_3_name">预设音乐 3</label></th>';
        echo '<td>';
        echo '<div style="margin-bottom: 8px;">';
        echo '<label for="preset_music_3_name" style="display: block; margin-bottom: 4px; font-weight: 500;">显示名称：</label>';
        echo '<input type="text" name="preset_music_3_name" id="preset_music_3_name" value="' . esc_attr($preset_3_name) . '" class="regular-text" placeholder="例如：放松音乐" style="max-width: 300px;" />';
        echo '<p class="description" style="margin-top: 4px;">这个名称会显示在前端音乐选择下拉菜单中，让用户知道这是什么音乐。</p>';
        echo '</div>';
        echo '<div>';
        echo '<label for="preset_music_3_url" style="display: block; margin-bottom: 4px; font-weight: 500;">音频文件URL：</label>';
        echo '<input type="url" name="preset_music_3_url" id="preset_music_3_url" value="' . esc_attr($preset_3_url) . '" class="regular-text" placeholder="从媒体库选择音频文件URL" />';
        echo '<p class="description" style="margin-top: 4px;">上传音频文件到<a href="' . admin_url('media-new.php') . '" target="_blank">媒体库</a>，然后复制文件URL粘贴到这里。留空则不显示此预设音乐。</p>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';
        
        echo '</table>';
    }
    
    /**
     * 获取预设音乐列表
     */
    private function get_preset_musics() {
        $preset_1_url = get_option('preset_music_1_url', '');
        $preset_1_name = get_option('preset_music_1_name', '');
        $preset_2_url = get_option('preset_music_2_url', '');
        $preset_2_name = get_option('preset_music_2_name', '');
        $preset_3_url = get_option('preset_music_3_url', '');
        $preset_3_name = get_option('preset_music_3_name', '');
        
        $presets = array();
        
        // 只有设置了URL的预设音乐才会显示
        if (!empty($preset_1_url)) {
            $presets['preset_1'] = array(
                'name' => !empty($preset_1_name) ? $preset_1_name : '预设音乐 1',
                'url' => $preset_1_url
            );
        }
        
        if (!empty($preset_2_url)) {
            $presets['preset_2'] = array(
                'name' => !empty($preset_2_name) ? $preset_2_name : '预设音乐 2',
                'url' => $preset_2_url
            );
        }
        
        if (!empty($preset_3_url)) {
            $presets['preset_3'] = array(
                'name' => !empty($preset_3_name) ? $preset_3_name : '预设音乐 3',
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
            
            <hr>
            <h2>使用说明</h2>
            <ol>
                <li><strong>上传音频文件：</strong>
                    <ul>
                        <li>进入<a href="<?php echo admin_url('media-new.php'); ?>" target="_blank">媒体库</a>上传音频文件（MP3格式推荐）</li>
                        <li>上传后点击音频文件，在右侧详情中复制"文件URL"</li>
                    </ul>
                </li>
                <li><strong>设置预设音乐：</strong>
                    <ul>
                        <li>在"预设音乐设置"部分，将复制的URL粘贴到对应的预设音乐输入框</li>
                        <li>可以设置1-3首预设音乐，留空则不显示该预设音乐</li>
                        <li>点击"保存更改"</li>
                    </ul>
                </li>
                <li><strong>选择音乐：</strong>
                    <ul>
                        <li>在"当前使用的音乐"下拉菜单中选择要使用的预设音乐</li>
                        <li>点击"保存更改"</li>
                    </ul>
                </li>
                <li><strong>更换音乐：</strong>
                    <ul>
                        <li>修改预设音乐的URL并保存，或直接在"当前使用的音乐"中选择其他预设音乐</li>
                    </ul>
                </li>
            </ol>
            <p><strong>提示：</strong>所有音乐都从媒体库选择，只需上传文件后复制URL粘贴到预设音乐设置中即可。</p>
        </div>
        <?php
    }
    
    /**
     * 在footer渲染播放器
     */
    public function render_player() {
        $selected_id = get_option('selected_music_id', '');
        $music_url = '';
        $preset_musics = $this->get_preset_musics();
        
        // 根据选择的ID获取音乐URL
        if (!empty($selected_id) && isset($preset_musics[$selected_id])) {
            $music_url = $preset_musics[$selected_id]['url'];
            $music_name = $preset_musics[$selected_id]['name'];
        } else {
            // 如果还没有选择，尝试使用旧的设置（兼容旧版本）
            $music_url = get_option('background_music_url', '');
            $music_name = '背景音乐';
        }
        
        // 如果URL为空，不显示播放器
        if (empty($music_url)) {
            return;
        }
        ?>
        <!-- 背景音乐播放器 -->
        <audio id="background-music" loop preload="auto">
            <source src="<?php echo esc_url($music_url); ?>" type="audio/mpeg">
        </audio>
        
        <div id="background-music-player">
            <button id="music-play-pause" aria-label="播放背景音乐">
                <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor"><path d="M3 2.5v11l9-5.5z"/></svg>
            </button>
            <?php if (!empty($preset_musics) && count($preset_musics) > 1): ?>
            <div id="music-select-control">
                <select id="music-select" aria-label="选择背景音乐">
                    <option value="">无音乐</option>
                    <?php foreach ($preset_musics as $id => $music): ?>
                        <option value="<?php echo esc_attr($id); ?>" data-url="<?php echo esc_attr($music['url']); ?>" <?php echo ($selected_id === $id) ? 'selected' : ''; ?>>
                            <?php echo esc_html($music['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
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
            name: <?php echo json_encode($music_name); ?>,
            presets: <?php echo json_encode($preset_musics); ?>,
            currentId: <?php echo json_encode($selected_id); ?>
        };
        </script>
        <?php
    }
}

// 初始化插件
Background_Music::get_instance();

