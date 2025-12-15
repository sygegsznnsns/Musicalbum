## 目标
- 将第7章“接口设计”重写为可直接在 WordPress（Astra 父主题 + musicalbum-child 子主题 + 子插件）实现与调用的版本。
- 提供接口 → 钩子/函数 → 前端调用 的端到端应用映射；移除“专辑/视频”等无关内容。

## 技术对齐
- 平台：WordPress 6.x；子主题 `musicalbum-child`；自定义子插件 `le-ying-ji`（示例名）。
- 接口：WordPress REST API（`/wp-json/lyj/v1`）；鉴权：`X-WP-Nonce`；存储：`wp-content/uploads` 或对接 OSS SDK（可选）。

## 接口设计（可实现版）
- 上传：`POST /wp-json/lyj/v1/oss/upload/{objectPath}` → Multipart `file`；头：`X-WP-Nonce`；返：`{downloadUrl}`；错：401/400/500。
- 下载：`GET /wp-json/lyj/v1/oss/download/{objectPath}` → 二进制流；头 `Content-Disposition`；错：404。
- 列表：`GET /wp-json/lyj/v1/oss/list?prefix=&page=&per_page=` → `items[]` + 分页；错：400。
- 元信息：`GET /wp-json/lyj/v1/oss/meta/{objectPath}` → 大小、类型、ETag、时间。
- 删除：`DELETE /wp-json/lyj/v1/oss/object/{objectPath}` → 返：200；错：404/401。
- 历史：`POST /wp-json/lyj/v1/history`（新增）；`GET /wp-json/lyj/v1/history`（查询）；`DELETE /wp-json/lyj/v1/history`（清理）。
- 规则：对象键校验按 PDF；分页 `per_page ∈ [1,50]`；所有写操作需 `X-WP-Nonce`。

## 后端实现映射（子插件）
- 路由注册：
  - 在子插件 `le-ying-ji/le-ying-ji.php` 中使用 `register_rest_route('lyj/v1','/oss/upload/(?P<objectPath>.+)',[ 'methods'=>'POST','callback'=>'lyj_upload','permission_callback'=>'lyj_can_write'])` 等形式注册所有路由。
- 权限与Nonce：
  - `lyj_can_write` 使用 `current_user_can('upload_files')` + `wp_verify_nonce` 验证 `X-WP-Nonce`。
- 上传处理：
  - 使用 `wp_handle_upload` 或直连 OSS SDK（若启用）将文件保存并返回下载链接（拼接 `site_url('/wp-json/lyj/v1/oss/download/...')`）。
- 下载处理：
  - 根据 `objectPath` 定位 `uploads` 路径或 OSS 对象，设置 `Content-Disposition`，流式输出。
- 列表/元信息/删除：
  - 基于 `WP_Filesystem` 或 SDK 列举/查询/删除对象；返回统一 JSON 结构。
- 历史记录：
  - 使用自定义表（`wp_lyj_history`）或 `user_meta` 存储；提供增删查接口与分页查询。

## 前端调用映射（子主题模板）
- 脚本入队：
  - 在 `functions.php` 中 `wp_enqueue_script('lyj-viewing-center', get_stylesheet_directory_uri().'/assets/viewing-center.js', ['wp-api','jquery'], null, true);`
  - 使用 `wp_localize_script` 注入 `rest_url`, `wp_create_nonce('wp_rest')` 与必要配置。
- 模板调用（示例）：
  - `page-templates/musicalbum-viewing-center.php` 中渲染上传按钮、列表区域、历史查询区。
  - 前端 `viewing-center.js`：
    - 上传：`fetch(rest_url+'lyj/v1/oss/upload/'+objectPath,{ method:'POST', headers:{'X-WP-Nonce':nonce}, body:formData })`。
    - 下载：`location.href = rest_url+'lyj/v1/oss/download/'+objectPath`。
    - 列表：`fetch(rest_url+'lyj/v1/oss/list?prefix=...')` 渲染 `items`。
    - 历史：调用相应接口渲染分页。
- 校验：前端对 `objectPath` 做预校验（禁止 `//`、只允许合法字符），失败直接提示。

## 数据流图与系统展示映射
- 数据流：模板事件 → 前端调用 REST → 子插件处理 → `uploads/OSS` 存储 → 历史记录写入 → 前端渲染更新。
- 展示：运维页可复用 `/api/v1/ops/*` 接口（如需），或在后台页面以同样方式拉取健康与日志。

## 验收标准
- 所有写操作在未携带或错误 `X-WP-Nonce` 时返回 401；对象键规则严格执行返回 400。
- 下载带 `Content-Disposition` 且文件名正确；列表/分页准确；历史记录可查询与清理。

## 文档输出
- 生成 `SRS_6-10_乐影集_接口设计_WordPress版.doc`（.doc HTML），包含：接口说明、路由注册示例、模板调用示例、请求/响应示例与错误用例，确保 WPS/Word 兼容。
- 清理现有文档中不适配 WordPress 的段落（如 GPU/大模型、专辑/视频）。