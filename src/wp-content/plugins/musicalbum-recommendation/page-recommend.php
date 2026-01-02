<?php
/**
 * page-recommend.php
 * 功能：渲染基于观演记录 + 喜欢演员的音乐剧推荐页面
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 读取 musical.csv，返回以“音乐剧名”为 key 的详情数组
 *
 * @return array
 */
function msr_load_musical_csv_data() {

    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $file = plugin_dir_path(__FILE__) . 'musical.csv';
    if (!file_exists($file)) {
        return [];
    }

    $handle = fopen($file, 'r');
    if (!$handle) {
        return [];
    }

    $header = fgetcsv($handle); // 读取表头
    $data = [];

    while (($row = fgetcsv($handle)) !== false) {
        if (empty($row[0])) continue;

        // 按 CSV 列名对应 PHP key
        $data[trim($row[0])] = [
            'originality' => $row[1] ?? '',
            'status'      => $row[2] ?? '',
            'premiere'    => $row[3] ?? '',
            'other_info'  => $row[4] ?? '',
            'company'     => $row[5] ?? '',
            'creators'    => $row[6] ?? '',
        ];
    }

    fclose($handle);
    $cache = $data;

    return $cache;
}


/**
 * 页面渲染函数
 */
function msr_render_recommend_page() {
    if ( ! is_user_logged_in() ) {
        return '<p>请先登录查看推荐。</p>';
    }

    // ✅ 新增：加载音乐剧详情数据
    $musical_csv_data = msr_load_musical_csv_data();

    if ( ! is_array( $musical_csv_data ) ) {
        $musical_csv_data = [];
    }

    $user_id = get_current_user_id();
    
    /**
     * =========
     * 处理演员关注 / 取消关注（POST）
     * =========
     */
    $favorite_actors = get_user_meta( $user_id, 'musicalbum_favorite_actors', true );
    if ( ! is_array( $favorite_actors ) ) {
        $favorite_actors = [];
    }
    
    // 新增关注演员
    if ( isset( $_POST['new_actor'] ) && ! empty( $_POST['new_actor'] ) ) {
        $new_actor = sanitize_text_field( $_POST['new_actor'] );
        if ( ! in_array( $new_actor, $favorite_actors, true ) ) {
            $favorite_actors[] = $new_actor;
            update_user_meta( $user_id, 'musicalbum_favorite_actors', $favorite_actors );
            delete_transient( 'msr_actor_recommend_' . $user_id );
        }
    }
    
    // 取消关注演员
    if ( isset( $_POST['remove_actor'] ) ) {
        $remove_actor = sanitize_text_field( $_POST['remove_actor'] );
        $favorite_actors = array_values( array_diff( $favorite_actors, [ $remove_actor ] ) );
        update_user_meta( $user_id, 'musicalbum_favorite_actors', $favorite_actors );
        delete_transient( 'msr_actor_recommend_' . $user_id );
    }
    
    /**
     * =========
     * 获取推荐结果
     * =========
     */
    $personal = musicalbum_recommend_by_crowd( $user_id, 10 );
    $trending = musicalbum_recommend_trending( 10 );
    $actor_recommend = musicalbum_recommend_by_favorite_actors( $user_id, 10 );
    $ai_recommend = musicalbum_get_ai_recommendations( get_current_user_id() );

    ob_start();
?>
<div class="msr-page">

    <!-- 左侧：演员管理 -->
    <div class="msr-section-wrapper">
        <div class="msr-actor-container">
            <h3 class="msr-section-title">你关注的演员</h3>
            <?php if ( empty( $favorite_actors ) ) : ?>
                <p class="msr-empty-text">你还没有关注任何演员，关注演员后将为你推荐相关剧目。</p>
            <?php else : ?>
                <ul class="msr-actor-list">
                    <?php foreach ( $favorite_actors as $actor ) : ?>
                        <li class="msr-actor-item">
                            <span class="msr-actor-name"><?php echo esc_html( $actor ); ?></span>
                            <form method="post" class="msr-inline-form">
                                <input type="hidden" name="remove_actor" value="<?php echo esc_attr( $actor ); ?>">
                                <button type="submit" class="msr-btn msr-btn-secondary">取消关注</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <form method="post" class="msr-actor-form">
                <input type="text" name="new_actor" placeholder="输入演员姓名" class="msr-input">
                <button type="submit" class="msr-btn msr-btn-primary">关注演员</button>
            </form>
        </div>

        <!-- 右侧：AI 推荐 -->
        <div class="msr-ai-container">
            <h3 class="msr-section-title">AI 为你推荐</h3>
            <?php if ( empty( $ai_recommend ) ) : ?>
                <p class="msr-empty-text">观演记录较少，AI 推荐暂不可用。</p>
            <?php else : ?>
                <ul class="msr-ai-list">
                    <?php foreach ( $ai_recommend as $item ) : ?>
                        <li class="msr-ai-item">
                            <strong class="msr-ai-title"><?php echo esc_html( $item['title'] ); ?></strong>
                            <p class="msr-ai-desc"><?php echo esc_html( $item['desc'] ); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- 推荐模块 -->
    <div class="msr-recommend-container">
        <h2 class="msr-page-title">为你推荐的音乐剧</h2>

        <!-- 演员相关推荐 -->
        <h3 class="msr-section-title">关注演员的相关剧目</h3>
        <?php if ( empty( $favorite_actors ) ) : ?>
            <p class="msr-empty-text">你尚未关注演员，暂无基于演员的推荐。</p>
        <?php elseif ( empty( $actor_recommend ) ) : ?>
            <p class="msr-empty-text">暂未找到与你关注演员相关的音乐剧，可尝试关注更多演员。</p>
        <?php else : ?>
            <?php foreach ( $actor_recommend as $actor_name => $musicals ) : ?>
                <h4 class="msr-subtitle"><?php echo esc_html( $actor_name ); ?> 参演的音乐剧</h4>
                <div class="msr-grid">
                    <?php foreach ( $musicals as $item ) : ?>
                        <div class="msr-card msr-item">
                            <h5 class="msr-card-title">
                                <a href="javascript:void(0);"
                                   class="msr-musical-link"
                                   data-musical="<?php echo esc_attr( $item['musical'] ); ?>">
                                    <?php echo esc_html( $item['musical'] ); ?>
                                </a>
                            </h5>
                            <p class="msr-card-text"><?php echo esc_html( $item['reason'] ); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- 协同过滤推荐 -->
        <?php if ( ! empty( $personal ) ) : ?>
            <h3 class="msr-section-title">你的同好都在看：</h3>
            <div class="msr-grid">
                <?php foreach ( $personal as $item ) : ?>
                    <div class="msr-card msr-item">
                        <h4 class="msr-card-title">
                            <a href="javascript:void(0);"
                               class="msr-musical-link"
                               data-musical="<?php echo esc_attr( $item['musical'] ); ?>">
                                <?php echo esc_html( $item['musical'] ); ?>
                            </a>
                        </h4>
                        <p class="msr-card-text"><?php echo esc_html( $item['reason'] ); ?></p>
                        <form method="post">
                            <input type="hidden" name="musical_title" value="<?php echo esc_attr( $item['musical'] ); ?>">
                            <button type="submit" name="musicalbum_not_interested"
                                    class="msr-btn msr-btn-secondary">
                                不感兴趣
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- 热门推荐 -->
        <?php if ( ! empty( $trending ) ) : ?>
            <h3 class="msr-section-title">近期热门观演</h3>
            <div class="msr-grid">
                <?php foreach ( $trending as $item ) : ?>
                    <div class="msr-card msr-item">
                        <h4 class="msr-card-title">
                            <a href="javascript:void(0);"
                               class="msr-musical-link"
                               data-musical="<?php echo esc_attr( $item['musical'] ); ?>">
                                <?php echo esc_html( $item['musical'] ); ?>
                            </a>
                        </h4>
                        <p class="msr-card-text"><?php echo esc_html( $item['reason'] ); ?></p>
                        <form method="post">
                            <input type="hidden" name="musical_title" value="<?php echo esc_attr( $item['musical'] ); ?>">
                            <button type="submit" name="musicalbum_not_interested"
                                    class="msr-btn msr-btn-secondary">
                                不感兴趣
                            </button>
                        </form>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>

</div>

<!-- 音乐剧详情弹窗 -->
<div id="msr-modal" class="msr-modal">
    <div class="msr-modal-content">
        <span class="msr-modal-close">&times;</span>
        <div id="msr-modal-body"></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const musicalData = <?php echo json_encode($musical_csv_data, JSON_UNESCAPED_UNICODE); ?>;
    const modal = document.getElementById('msr-modal');
    const modalBody = document.getElementById('msr-modal-body');
    const modalClose = document.querySelector('.msr-modal-close');

    document.querySelectorAll('.msr-musical-link').forEach(function(link){
        link.addEventListener('click', function(){
            const name = this.dataset.musical.trim();
            if(!musicalData[name]){
                modalBody.innerHTML = '<p>该音乐剧的详细信息待完善。</p>';
            }else{
                const m = musicalData[name];
                modalBody.innerHTML = `
                    <h4>${name}</h4>
                    <p><strong>原创性：</strong> ${m.originality}</p>
                    <p><strong>进度：</strong> ${m.status}</p>
                    <p><strong>首演日期：</strong> ${m.premiere}</p>
                    <p><strong>其他信息：</strong> ${m.other_info}</p>
                    <p><strong>制作公司：</strong> ${m.company}</p>
                    <pre style="white-space:pre-wrap;"><strong>主创信息：</strong>\n${m.creators}</pre>
                `;
            }
            modal.style.display = 'block';
        });
    });

    modalClose.addEventListener('click', function(){ modal.style.display = 'none'; });
    window.addEventListener('click', function(e){ if(e.target === modal){ modal.style.display = 'none'; } });
});
</script>

<?php
    return ob_get_clean();
}


/**
 * 注册简码（保持你的原始简码名）
 */
add_shortcode( 'musical_recommend', 'msr_render_recommend_page' );