# 技术选型说明文档

本文档详细描述了 Musicalbum (月影集) 项目在前端、后端、数据持久化、数据服务及部署方面的技术选型与架构设计。

## 1. 前端 (Frontend)

*   **核心框架**
    *   **HTML5 / CSS3 / JavaScript (ES6+)**：构建页面的基石。
    *   **WordPress Theme Development**：基于 **Astra Child Theme** 进行二次开发，确保样式与功能的灵活扩展。

*   **JavaScript 库**
    *   **jQuery**：利用 WordPress 内置的标准库处理 DOM 操作、事件绑定、AJAX 异步请求（如点赞、资源上传、动态加载）。

*   **CSS 架构**
    *   **CSS Variables (Custom Properties)**：用于全站主题色统一管理（如 `--ast-global-color-0`），实现一键换肤与风格统一。
    *   **Grid / Flexbox**：用于构建响应式的社区中心、资源库网格布局，适配移动端与桌面端。

*   **地图与可视化**
    *   **Leaflet.js / 高德地图 JS API**：集成于剧院地图插件中，提供剧院分布展示、POI 点击交互及基于 Web URI 的路线导航功能。

*   **交互组件**
    *   **TinyMCE**：WordPress 内置富文本编辑器，用于论坛发帖、撰写长评。
    *   **Lightbox / Modal**：用于剧照预览、资源详情的弹窗展示。

## 2. 后端 (Backend)

*   **开发语言**
    *   **PHP 7.4+**：作为 WordPress 的原生语言，负责所有核心业务逻辑的处理。

*   **应用框架**
    *   **WordPress Core**：利用其成熟的 Plugin API (Action/Filter Hooks) 实现模块化开发，保证系统的稳定性与可维护性。
    *   **REST API**：自定义 API 端点（Namespace: `musicalbum/v1/`），为前端提供标准化的 JSON 数据接口，支持前后端分离的交互模式。

*   **业务模块 (自定义插件)**
    *   **Musicalbum Community Integration**：核心业务插件，负责社区互动、资源共享、知识库及积分逻辑。
    *   **Musicalbum Recommendation**：实现混合推荐算法（协同过滤 + 热门 + 内容关联）。
    *   **Musicalbum Theater Maps**：负责剧院数据的地理化展示与服务。

*   **生态集成**
    *   **BuddyPress**：提供完整的社交网络基础设施（用户档案、动态流、好友关系、私信）。
    *   **bbPress**：提供结构化的论坛版块功能。

## 3. 持久化 (Persistence)

*   **数据库**
    *   **MySQL 5.7+ / MariaDB**：作为主要的关系型数据库。

*   **数据模型**
    *   **Core Tables**：
        *   `wp_posts`：存储核心内容（文章、页面、自定义文章类型）。
        *   `wp_users` / `wp_usermeta`：存储用户基础信息及扩展画像。
    *   **Custom Post Types (CPT)**：利用 WordPress EAV 模型灵活扩展业务对象：
        *   `musicalbum_viewing`：观演记录。
        *   `musicalbum_resource`：共享资源。
        *   `topic` / `reply`：论坛帖子。
    *   **Meta Data** (`wp_postmeta`)：
        *   存储非结构化扩展数据，如：剧目评分、剧院经纬度、资源文件 URL、关联演员 ID 等。
    *   **Custom Taxonomies**：
        *   `resource_category`：资源分类标签。

## 4. 数据服务 (Data Services)

*   **外部 API 集成**
    *   **Saoju.net API**（或其他剧目数据库）：获取音乐剧的卡司、主创、排期等元数据，解决冷启动数据缺失问题，并辅助内容推荐。
    *   **高德地图 Web 服务 API**：提供地理编码（地址转坐标）及路径规划支持。

*   **缓存机制**
    *   **WordPress Transients API**：对外部 API 的响应结果及高耗时的推荐计算结果进行临时缓存（TTL），显著降低数据库压力并提升页面加载速度。

## 5. 部署 (Deployment)

*   **Web 服务器**
    *   **Nginx**：作为高性能反向代理服务器，处理 HTTP 请求，配置 Rewrite Rules 支持 WordPress 固定链接结构。

*   **运行环境**
    *   **OS**：Linux (Ubuntu/CentOS) 或 Windows Server。
    *   **PHP Runtime**：配置 PHP-FPM 以获得最佳性能。

*   **安全与传输**
    *   **HTTPS / SSL**：强制开启 HTTPS（Let's Encrypt 或商业证书），确保用户数据传输安全，且是使用浏览器定位 API 和麦克风/摄像头权限的前置条件。

*   **文件存储**
    *   **Local Storage**：默认使用 `wp-content/uploads` 目录存储用户上传的图片、文档等多媒体资源，按年月分目录管理。
