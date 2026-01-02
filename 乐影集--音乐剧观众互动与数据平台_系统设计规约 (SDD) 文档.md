:::color3
**😊****小组成员****😊**** ：**2353243 陈攀  ； 2352038 陈子昂  ； 2351869 纪鹏

:::

:::info
[<font style="color:rgb(38, 38, 38);">1. 引言</font>](#vyX0S)

[<font style="color:rgb(38, 38, 38);">2. 系统概述</font>](#nJ4BH)

[<font style="color:rgb(38, 38, 38);">3. 详细设计</font>](#frOMs)

[<font style="color:rgb(38, 38, 38);">4. 数据库表设计及数据来源</font>](#ZMZaw)

[<font style="color:rgb(38, 38, 38);">5. 接口设计</font>](#pbhRd)

[<font style="color:rgb(38, 38, 38);">6. 非功能需求实现</font>](#mhW8o)

[<font style="color:rgb(38, 38, 38);">7. 测试计划</font>](#JkDwC)

[<font style="color:rgb(38, 38, 38);">8. 用户体验设计</font>](#MOPao)

[<font style="color:rgb(38, 38, 38);">9. 部署与运维</font>](#dbq9X)

[<font style="color:rgb(38, 38, 38);">10. 总结</font>](#uNsaJ)

:::

# 引言
## <font style="color:rgb(0, 0, 0);">项目背景</font>
+ <font style="color:rgb(0, 0, 0);">项目名称：乐影集 - 音乐剧垂直生态服务平台</font>
+ <font style="color:rgb(0, 0, 0);">项目目标：</font>
    - <font style="color:rgb(0, 0, 0);">搭建音乐剧观众专属的综合服务系统架构，覆盖观演全链路需求；</font>
    - <font style="color:rgb(0, 0, 0);">构建支持多渠道数据录入、高效存储与智能分析的数据管理体系；</font>
    - <font style="color:rgb(0, 0, 0);">实现观演记录管理、数据可视化分析、社区互动交流、智能剧目推荐等核心功能；</font>
    - <font style="color:rgb(0, 0, 0);">打造剧院周边服务生态，提供票务互动、应援活动、生活便捷服务等附加价值，全面提升音乐剧观众的观剧体验与文化参与感。</font>

## <font style="color:rgb(0, 0, 0);">范围</font>
<font style="color:rgb(0, 0, 0);">本 SDD 文档涵盖 “乐影集” 平台的完整技术设计，包括观演记录管理、数据统计与可视化、社区交流、智能推荐、剧院周边服务、用户账户与权限管理六大核心功能模块，同时包含系统架构设计、技术栈选型、接口设计及非功能需求实现方案。设计严格遵循 SRS 中规定的约束条件与需求，确保系统在实现全部功能需求的同时，满足性能、安全、易用性等非功能指标。</font>

## <font style="color:rgb(0, 0, 0);">定义与缩写词</font>
| **<font style="color:rgb(0, 0, 0) !important;">缩写词</font>** | **<font style="color:rgb(0, 0, 0) !important;">英文全称</font>** | **<font style="color:rgb(0, 0, 0) !important;">中文释义</font>** |
| :--- | :--- | :--- |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">SDD</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">Software Design Description</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">软件设计说明书</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">SRS</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">Software Requirement Specification</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">软件需求规格说明书</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">OCR</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">Optical Character Recognition</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">光学字符识别，用于票面信息提取</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">ACF</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">Advanced Custom Fields</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">WordPress 自定义字段管理插件，用于存储观演记录相关数据</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">CRP</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">Custom Related Posts</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">WordPress 插件，用于实现智能推荐功能</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">PB</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">Profile Builder</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">WordPress 插件，用于用户资料管理与表单提交</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">REST</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">Representational State Transfer</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">表述性状态转移，一种用于设计网络应用的软件架构风格，用于系统 API 设计</font> |


# <font style="color:rgb(31,35,41);">系统概述</font>
## <font style="color:rgb(0, 0, 0);">系统上下文</font>
<font style="color:rgb(0, 0, 0);">“乐影集” 平台是一款面向音乐剧观众的独立 Web 垂直服务系统，通过标准化接口与外部系统交互：对接主流购票平台实现用户订单数据导入，调用第三方地图 API（高德 / 百度地图）获取地理位置及周边服务信息，集成百度 OCR API 实现票面信息自动提取，依托云存储服务安全存储用户上传文件（如票面图片、分享内容）及核心数据。系统内部以观演记录管理模块为数据核心，为其他功能模块提供结构化数据支撑，各模块通过预设接口协同工作，形成覆盖 “观演前 - 观演中 - 观演后” 全链路的完整服务生态。</font>

### <font style="color:rgb(0, 0, 0);">系统边界与参与者</font>
本系统主要涉及以下外部参与者：

+ 注册用户（主要用户角色）
+ 平台管理员（管理维护角色）  
+ 外部服务（OCR API、地图服务等）

### 系统顶层用例图
<!-- 这是一张图片，ocr 内容为： -->
![](https://cdn.nlark.com/yuque/0/2025/png/61531748/1767013942094-d5ac3819-a50d-4781-a7ee-b30927a1134b.png)

## <font style="color:rgb(0, 0, 0);">设计目标与原则</font>
### <font style="color:rgb(0, 0, 0);">设计目标</font>
+ <font style="color:rgb(0, 0, 0);">实现 SRS 中规定的全部功能需求，包括观演记录管理、数据可视化、社区互动、智能推荐等核心功能。</font>
+ <font style="color:rgb(0, 0, 0);">满足性能、安全、易用性等非功能需求，确保系统具备高稳定性、高效性与良好的用户体验。</font>
+ <font style="color:rgb(0, 0, 0);">保证系统具备良好的扩展性与可维护性，以适应音乐剧市场发展及用户需求变化。</font>
+ <font style="color:rgb(0, 0, 0);">保障数据安全与用户隐私保护，符合国家相关法律法规及行业标准。</font>

### <font style="color:rgb(0, 0, 0);">设计原则</font>
+ <font style="color:rgb(0, 0, 0);">模块化与高内聚低耦合原则：将系统划分为独立功能模块，模块间边界清晰、接口标准化，降低相互依赖。</font>
+ <font style="color:rgb(0, 0, 0);">数据为核心原则：以观演记录数据为中心，实现数据的标准化采集、存储与利用，支撑数据驱动的个性化服务。</font>
+ <font style="color:rgb(0, 0, 0);">可扩展性与可演进性原则：采用松耦合架构与标准化接口设计，为后续功能升级与技术迭代预留扩展空间。</font>
+ <font style="color:rgb(0, 0, 0);">用户体验优先原则：优化功能流程与界面交互，降低用户操作成本，提升系统易用性。</font>
+ <font style="color:rgb(0, 0, 0);">安全性与隐私保护原则：实施严格的权限控制、数据加密及内容审核机制，保障数据安全与合规性。</font>
+ <font style="color:rgb(0, 0, 0);">高可维护性与可测试性原则：规范代码标准，完善文档记录，设置日志记录与异常处理机制，便于系统维护与测试。</font>

## <font style="color:rgb(0, 0, 0);">技术栈选型</font>
### <font style="color:rgb(0, 0, 0);">前端技术</font>
| **<font style="color:rgb(0, 0, 0) !important;">技术名称</font>** | **<font style="color:rgb(0, 0, 0) !important;">用途</font>** | **<font style="color:rgb(0, 0, 0) !important;">核心功能</font>** |
| :--- | :--- | :--- |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Astra 主题（父主题）</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">提供基础 UI 布局与响应式栅格系统</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">轻量化布局、钩子机制、可视化自定义</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">musicalbum-child 子主题</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">定制业务专属模板与样式（不修改父主题）</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">模板覆盖、独立样式管理、按需资源加载</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">WordPress Block Editor（Gutenberg）+ 原生 JS/CSS</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">组织页面内容并实现轻量交互与样式渲染</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">可复用区块、短代码支持、编辑体验一致</font> |


### <font style="color:rgb(0, 0, 0);">后端技术</font>
| **<font style="color:rgb(0, 0, 0) !important;">技术名称</font>** | **<font style="color:rgb(0, 0, 0) !important;">用途</font>** | **<font style="color:rgb(0, 0, 0) !important;">核心功能</font>** |
| :--- | :--- | :--- |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">WordPress Core（PHP 8.1+）</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">提供内容管理、用户管理、媒体库、模板渲染及 REST API</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">自定义文章类型、元数据管理、丰富钩子机制</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">MySQL 8（InnoDB）</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">存储用户信息、观演记录、社区内容等核心数据</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">事务支持、索引优化、高并发读支持</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Advanced Custom Fields（ACF）</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">管理观演记录相关自定义字段</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">自定义字段定义、数据存储与读取、表单集成</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Profile Builder（PB）</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">实现用户注册、登录及资料管理</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">用户认证、表单提交、数据关联</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Custom Related Posts（CRP）/YITH</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">实现智能推荐算法</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">关联内容计算、推荐列表生成</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">BuddyPress + bbPress</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">搭建社区互动功能</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">内容发布、评论 / 点赞、论坛管理</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Visualizer</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">实现数据可视化功能</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">图表生成（饼图、柱状图、折线图）、数据导出</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Simple Calendar</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">实现观演记录日历式展示</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">事件聚合、日期关联、详情展示</font> |


### <font style="color:rgb(0, 0, 0);">部署与持久化技术</font>
| **<font style="color:rgb(0, 0, 0) !important;">技术名称</font>** | **<font style="color:rgb(0, 0, 0) !important;">用途</font>** | **<font style="color:rgb(0, 0, 0) !important;">核心功能</font>** |
| :--- | :--- | :--- |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Nginx/Apache + PHP-FPM</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">Web 服务器与 PHP 运行环境</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">HTTP/2 支持、OPcache 优化、高并发处理</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Redis</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">实现缓存机制</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">页面缓存、对象缓存、提升读性能</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">CDN</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">分发静态资源</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">降低首字节延迟、加速资源加载</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">Docker Compose（可选）</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">多环境服务编排</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">开发与部署环境一致、简化配置管理</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">MySQL 8</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">存储核心数据（文章 / 分类 / 用户 / 元数据）</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">数据持久化、事务支持、索引优化</font> |
| <font style="color:rgba(0, 0, 0, 0.85) !important;">wp-content/uploads 文件系统</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">存储用户上传媒体文件</font> | <font style="color:rgba(0, 0, 0, 0.85) !important;">文件存储、权限控制、云存储对接支持</font> |


## 系统架构视图
### 逻辑logical视图
<!-- 这是一张图片，ocr 内容为： -->
![](https://cdn.nlark.com/yuque/0/2025/png/61531748/1766945746240-589ab769-2498-446b-830f-224bf070d4fa.png)

### 运行runtime视图
<!-- 这是一个文本绘图，源码为：@startuml Project_Runtime_View

skinparam linetype ortho
skinparam shadowing false
skinparam sequenceMessageAlign center

title 2.4.2 系统运行（Runtime）视图 - 全项目

actor User

participant "Web Browser" as Browser
participant "Front-end UI\n(Theme + JS)" as UI
participant "WP REST API" as REST

participant "Auth Module" as Auth
participant "Viewing Record Module" as Viewing
participant "Statistics Module" as Stats
participant "Community Module" as Community
participant "Recommendation Module" as Recommend
participant "Theater Service Module" as Theater

database "WordPress DB" as DB

== 用户认证与权限 ==
User -> Browser : 登录 / 注册
Browser -> UI
UI -> REST : Auth Request
REST -> Auth : verifyUser()
Auth -> DB : queryUser()
DB --> Auth
Auth --> REST
REST --> UI

== 观演记录管理 ==
User -> Browser : 录入 / 查看观演记录
Browser -> UI
UI -> REST : viewing record API
REST -> Viewing : create / query record
Viewing -> DB : read / write viewing data
DB --> Viewing
Viewing --> REST
REST --> UI

== 数据统计与可视化 ==
User -> Browser : 查看统计图表
Browser -> UI
UI -> REST : statistics API
REST -> Stats : calculate statistics
Stats -> DB : aggregate query
DB --> Stats
Stats --> REST
REST --> UI : chart data

== 社区交流互动 ==
User -> Browser : 发布 / 搜索内容
Browser -> UI
UI -> REST : community API
REST -> Community : post / search content
Community -> DB : read / write post
DB --> Community
Community --> REST
REST --> UI

== 智能推荐服务 ==
User -> Browser : 查看推荐内容
Browser -> UI
UI -> REST : recommendation API
REST -> Recommend : generate recommendation
Recommend -> DB : read viewing & preference
DB --> Recommend
Recommend --> REST
REST --> UI

== 剧院周边服务 ==
User -> Browser : 查询周边信息
Browser -> UI
UI -> REST : theater service API
REST -> Theater : query service info
Theater -> DB : read service data
DB --> Theater
Theater --> REST
REST --> UI

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/787783f4b18b1a265c46abc360ed73ff.svg)

### 开发development视图
<!-- 这是一个文本绘图，源码为：@startuml Project_Development_View

skinparam componentStyle rectangle
skinparam shadowing false
skinparam linetype ortho

title 2.4.3 系统开发（Development）视图 - 全项目

package "Client Layer\n(Front-end)" {
    component "Web UI\n(Theme Templates)" as UI
    component "JS Components\n(Ajax / Charts)" as JS
    component "CSS Styles" as CSS
}

package "Application Layer\n(WordPress Plugins)" {

    package "Viewing Record Plugin" {
        component "Viewing REST Controller" as ViewingAPI
        component "Viewing Service" as ViewingService
        component "OCR Import Module" as OCR
        component "Ticket Platform Import" as TicketImport
    }

    package "Statistics Plugin" {
        component "Statistics REST Controller" as StatsAPI
        component "Statistics Service" as StatsService
        component "Chart Data Builder" as ChartBuilder
    }

    package "Community Plugin" {
        component "Community REST Controller" as CommunityAPI
        component "Content Service" as ContentService
        component "Moderation Module" as Moderation
    }

    package "Recommendation Plugin" {
        component "Recommendation REST Controller" as RecommendAPI
        component "Recommendation Engine" as RecommendEngine
        component "Preference Analyzer" as Preference
    }

    package "Theater Service Plugin" {
        component "Theater REST Controller" as TheaterAPI
        component "Theater Info Service" as TheaterService
    }

    package "User & Auth Plugin" {
        component "Auth REST Controller" as AuthAPI
        component "Permission Service" as Permission
    }
}

package "Infrastructure Layer" {
    component "WordPress Core" as WPCore
    component "Database Access\n($wpdb / ORM)" as DBAccess
}

database "WordPress Database\n(MySQL)" as DB

' ===== Front-end Dependencies =====
UI --> JS
UI --> CSS
JS --> ViewingAPI
JS --> StatsAPI
JS --> CommunityAPI
JS --> RecommendAPI
JS --> TheaterAPI
JS --> AuthAPI

' ===== Plugin Internal Dependencies =====
ViewingAPI --> ViewingService
ViewingService --> OCR
ViewingService --> TicketImport

StatsAPI --> StatsService
StatsService --> ChartBuilder

CommunityAPI --> ContentService
ContentService --> Moderation

RecommendAPI --> RecommendEngine
RecommendEngine --> Preference

TheaterAPI --> TheaterService

AuthAPI --> Permission

' ===== Infrastructure Dependencies =====
ViewingService --> DBAccess
StatsService --> DBAccess
ContentService --> DBAccess
RecommendEngine --> DBAccess
TheaterService --> DBAccess
Permission --> DBAccess

DBAccess --> WPCore
WPCore --> DB

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/76842dc9bb2a859cadfd34966f35fbb7.svg)

### 物理physical视图
<!-- 这是一个文本绘图，源码为：@startuml Project_Physical_View

!theme plain
skinparam shadowing false
skinparam linetype ortho
skinparam node {
  BorderThickness 2
}

title 2.4.4 系统物理（Physical / Deployment）视图

node "用户终端\n(Client Device)" {
    node "Web Browser" {
        component "HTML / CSS / JS"
    }
}

node "应用服务器\n(Web Server)" {
    node "Nginx / Apache" {
        component "WordPress Runtime"
        component "Theme Templates"
        component "Front-end JS Assets"
    }

    node "WordPress Plugins" {
        component "观演记录管理插件"
        component "数据统计与可视化插件"
        component "社区交流插件"
        component "智能推荐插件"
        component "剧院周边服务插件"
        component "用户账户与权限插件"
    }
}

node "数据库服务器\n(Database Server)" {
    database "MySQL\nWordPress Database" as DB
}

node "第三方服务\n(External Services)" {
    node "OCR 服务商" {
        component "Baidu / Aliyun OCR API"
    }

    node "地图与位置服务" {
        component "Map API"
    }

    node "购票平台" {
        component "Ticket Platform API"
    }
}

' ===== 通信关系 =====
"Web Browser" --> "Nginx / Apache" : HTTPS 请求\n(页面 / REST API)
"Nginx / Apache" --> DB : SQL 查询\n(CRUD)

"WordPress Plugins" --> DB : 数据持久化\n(观演 / 社区 / 推荐)

"智能推荐插件" --> DB : 用户行为数据
"数据统计与可视化插件" --> DB : 聚合查询

"观演记录管理插件" --> "Baidu / Aliyun OCR API" : 票面识别
"观演记录管理插件" --> "Ticket Platform API" : 购票数据导入

"剧院周边服务插件" --> "Map API" : 地图 / 位置信息

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/faa82fae666ba3ef55947831a81ed30e.svg)

### 主程序-子程序体系结构视图
系统采用基于 WordPress 钩子机制（Hooks）的变体主程序-子程序架构。WordPress 核心作为主控程序，负责请求的接收、分发与生命周期管理；各功能插件作为独立的子程序模块，通过注册 Action 和 Filter 挂载到主程序的执行流中。

<!-- 这是一个文本绘图，源码为：@startuml Main_Subroutine_Architecture
!theme plain
skinparam shadowing false
skinparam linetype ortho
skinparam rectangle {
    BackgroundColor #F8F9FA
    BorderColor #333333
}

title 2.4.5 主程序-子程序体系结构视图

rectangle "主程序 (Main Program)\nWordPress Core" as Main {
    component "请求接收器\n(Request Handler)" as Handler
    component "核心加载器\n(WP-Load)" as Loader
    component "钩子管理器\n(Plugin API)" as Hooks
    component "模板加载器\n(Template Loader)" as Template
}

rectangle "子程序集合 (Subroutines / Plugins)" as Subs {
    component "观演记录管理\n子程序" as Sub_Viewing
    component "数据统计\n子程序" as Sub_Stats
    component "社区交流\n子程序" as Sub_Community
    component "智能推荐\n子程序" as Sub_Rec
    component "剧院服务\n子程序" as Sub_Theater
    component "用户权限\n子程序" as Sub_Auth
}

database "共享数据存储" as DB

' 主控流程
Handler --> Loader : 初始化环境
Loader --> Hooks : 触发初始化钩子\n(init / plugins_loaded)
Hooks --> Template : 触发渲染钩子\n(template_redirect)

' 子程序调用
Hooks -down-> Sub_Viewing : 调用 (API/Page)
Hooks -down-> Sub_Stats : 调用 (Calc/Render)
Hooks -down-> Sub_Community : 调用 (BP/Activity)
Hooks -down-> Sub_Rec : 调用 (Logic)
Hooks -down-> Sub_Theater : 调用 (Map/Search)
Hooks -down-> Sub_Auth : 调用 (Check/Login)

' 数据交互
Sub_Viewing ..> DB : 读写
Sub_Stats ..> DB : 只读
Sub_Community ..> DB : 读写
Sub_Rec ..> DB : 只读
Sub_Theater ..> DB : 读写
Sub_Auth ..> DB : 读写

note right of Main
  **主控职责：**
  1. 解析 HTTP 请求
  2. 管理生命周期
  3. 分发事件到子程序
  4. 组装最终响应
end note

note bottom of Subs
  **子程序职责：**
  1. 响应特定钩子事件
  2. 执行具体业务逻辑
  3. 返回处理结果或输出
end note

@enduml -->
![](https://cdn.nlark.com/yuque/__puml/e722652787df88a77488876a88484877.svg)

### 体系结构环境视图
体系结构环境图展示了乐影集系统与其运行环境、外部实体及支撑设施之间的交互关系。该视图界定了系统的边界，明确了系统所依赖的下级基础设施、交互的同级外部服务以及服务的上级用户环境。

<!-- 这是一个文本绘图，源码为：@startuml System_Architecture_Environment
!theme plain
top to bottom direction
skinparam linetype ortho
skinparam shadowing false
skinparam nodesep 60
skinparam ranksep 60

title 2.4.6 体系结构环境图

' 参与者
actor "普通用户\n(Audience)" as User
actor "管理员\n(Admin)" as Admin

' 上级系统
package "上级系统 (Superordinate Systems)" {
    node "客户端运行环境" as ClientEnv {
        component "Web 浏览器\n(Chrome/Edge/Safari)" as Browser
    }
}

' 目标系统
package "目标系统 (Target System)" {
    component "乐影集平台\n(Musicalbum)" as TargetSystem #E3F2FD
}

' 下级系统
package "下级系统 (Subordinate/Infrastructure)" {
    component "WordPress 核心框架" as WPCore
    component "MySQL 数据库" as DBMS
    component "PHP 运行时" as PHP
    component "Web 服务器 (Nginx)" as WebServer
}

' 同级系统
package "同级系统 (Peer Systems)" {
    component "高德地图服务\n(LBS Provider)" as Amap
    component "百度 OCR 服务\n(AI Provider)" as OCR
    component "外部图床/CDN" as CDN
}

' 关系
User -down-> Browser : 交互操作
Admin -down-> Browser : 系统管理

Browser <-down-> TargetSystem : HTTPS / REST API\n(请求/响应)

TargetSystem .right.> Amap : API 调用\n(地理编码/周边搜索)
TargetSystem .right.> OCR : API 调用\n(票面识别)
TargetSystem .left.> CDN : 静态资源分发

TargetSystem -down-> WPCore : 基于扩展
TargetSystem -down-> PHP : 运行依赖
TargetSystem -down-> DBMS : 数据持久化
TargetSystem -down-> WebServer : 宿主托管

WPCore .down.> PHP : 调用
WPCore .down.> DBMS : SQL 查询

@enduml -->
![](https://cdn.nlark.com/yuque/__puml/system_environment_placeholder.svg)

# 详细设计
## 观影记录管理模块
### 模块概述
#### 模块简介
观影记录管理模块是乐影集（Musicalbum）系统的核心功能模块之一，负责用户观演记录的完整生命周期管理。该模块基于WordPress平台开发，采用自定义文章类型（Custom Post Type）存储观演记录数据，通过REST API提供前后端交互能力，支持记录的创建、查询、更新、删除等基本操作，同时提供列表视图、日历视图、搜索过滤、OCR识别等高级功能。

#### 技术架构
+ **后端框架**：WordPress Plugin API
+ **数据存储**：WordPress数据库（wp_posts表 + ACF字段）
+ **前端技术**：jQuery、FullCalendar.js
+ **API协议**：WordPress REST API
+ **字段管理**：Advanced Custom Fields (ACF)

#### 模块职责
1. 观演记录的CRUD操作（创建、读取、更新、删除）
2. 记录列表展示与多维度筛选
3. 日历视图展示观演记录
4. OCR票面识别与自动填充
5. 图片上传与管理
6. 数据权限控制（用户只能管理自己的记录，管理员可管理所有记录）

### 核心功能设计
#### 记录管理功能
+ **创建记录**

**功能描述**：用户可以通过表单创建新的观演记录，支持手动录入和OCR识别两种方式。

**业务流程**：

        1. 用户点击"新增记录"按钮
        2. 弹出录入表单模态框
        3. 用户可选择：
            + 手动填写表单字段
            + 上传票面图片进行OCR识别，自动填充字段
        4. 提交表单，调用REST API创建记录
        5. 刷新列表显示新记录
+ **查询记录**

**功能描述**：支持多种查询方式，包括列表查询、详情查询、条件筛选等。

**查询方式**：

        1. **列表查询**：获取当前用户的所有观演记录（管理员可查看所有记录）
        2. **详情查询**：根据记录ID获取单条记录的完整信息
        3. **条件筛选**：
            + 按类别筛选（category）
            + 按关键词搜索（标题、剧院、卡司、备注）
            + 按日期排序（最新/最早）
            + 按标题排序（A-Z/Z-A）
+ **更新记录**

**功能描述**：用户可编辑已存在的观演记录，支持修改所有字段。

**业务流程**：

        1. 用户在列表或日历视图中点击"编辑"按钮
        2. 弹出编辑表单，预填充当前记录数据
        3. 用户修改字段值
        4. 提交表单，调用REST API更新记录
        5. 刷新显示
+ **删除记录**

**功能描述**：用户可删除自己的观演记录，删除操作不可恢复。

**业务流程**：

        1. 用户点击"删除"按钮
        2. 弹出确认对话框
        3. 确认后调用REST API删除记录
        4. 从列表中移除该记录

#### 视图展示功能
+ **列表视图**

**功能描述**：以列表形式展示观演记录，支持分页、排序、筛选。

**展示内容**：

        * 记录标题
        * 观演日期
        * 剧目类别
        * 剧院
        * 票价
        * 操作按钮（编辑、删除、查看详情）

**交互功能**：

        * 搜索框：实时搜索标题、剧院、卡司、备注
        * 类别筛选下拉框
        * 排序选择器（日期/标题，升序/降序）
        * 分页导航（如需要）
+ **日历视图**

**功能描述**：使用FullCalendar组件以日历形式展示观演记录，直观显示观演时间分布。

**展示内容**：

        * 日历网格显示
        * 观演日期标记
        * 点击日期显示该日期的观演记录
        * 支持月份切换

**交互功能**：

        * 月份导航（上一月/下一月）
        * 点击日期查看详情
        * 点击记录项跳转到详情页

#### OCR识别功能
+ **票面识别**

**功能描述**：用户上传票面图片，系统通过OCR技术识别图片中的文字信息，自动提取并填充表单字段。

**支持的服务商**：

        * 百度OCR API
        * 可扩展其他OCR服务（通过WordPress过滤器）

**识别字段**：

        * 标题（title）
        * 剧院（theater）
        * 卡司（cast）
        * 票价（price）
        * 观演日期（view_date）

**业务流程**：

        1. 用户上传票面图片
        2. 前端调用OCR REST API
        3. 后端调用OCR服务商API进行识别
        4. 解析识别结果，提取结构化字段
        5. 返回识别结果给前端
        6. 前端自动填充表单字段

#### 图片上传功能
+ **票面图片上传**

**功能描述**：用户可上传票面图片作为记录的附件，图片存储在WordPress媒体库中。

**支持格式**：

        * JPEG/JPG
        * PNG
        * GIF
        * WebP

**业务流程**：

        1. 用户选择图片文件
        2. 前端预览图片
        3. 调用上传API上传到WordPress媒体库
        4. 返回图片ID和URL
        5. 保存图片ID到记录字段

### 功能细化视图
<!-- 这是一个文本绘图，源码为：@startuml 观演记录管理模块功能细化图

skinparam componentStyle rectangle
skinparam rectangle {
    BackgroundColor<<component>> #E1F5FE
    BackgroundColor<<external>> #FFF9C4
    BorderColor #01579B
    BorderThickness 2
}

title 观演记录管理模块功能细化图

package "观演记录管理模块" {
    component ViewingRecordService <<component>>
}

package "核心业务处理组件" {
    component "记录管理" <<component>> as RecordManagement
    component "视图展示" <<component>> as ViewDisplay
    component "OCR识别" <<component>> as OCRService
    component "图片上传" <<component>> as ImageUpload
}

package "记录与追溯组件" {
    component "记录管理器" <<component>> as RecordManager
    component "视图渲染器" <<component>> as ViewRenderer
    component "OCR处理器" <<component>> as OCRProcessor
    component "图片上传处理器" <<component>> as ImageProcessor
}

component "数据持久化组件" <<component>> as DataPersistence

cloud "外部模块" {
    component "<<外部>>\nWordPress媒体库" <<external>> as MediaLibrary
    component "<<外部>>\nOCR服务商" <<external>> as OCRProvider
}

' ViewingRecordService 到核心业务处理组件的交互
ViewingRecordService ..> RecordManagement : 创建/查询/更新/删除记录
ViewingRecordService ..> ViewDisplay : 列表/日历视图切换
ViewingRecordService ..> OCRService : 票面OCR识别
ViewingRecordService ..> ImageUpload : 图片上传

' 核心业务处理组件到记录与追溯组件的交互
RecordManagement --> RecordManager : 处理记录操作
ViewDisplay --> ViewRenderer : 渲染视图
OCRService --> OCRProcessor : 处理OCR请求
ImageUpload --> ImageProcessor : 处理上传请求

' 记录与追溯组件到数据持久化组件的交互
RecordManager ..> DataPersistence : 持久化存储记录
ViewRenderer --> DataPersistence : 获取记录数据

' 与外部模块的交互
ImageProcessor ..> MediaLibrary : 上传图片
OCRProcessor ..> OCRProvider : 调用OCR API
OCRProvider ..> OCRService : 返回识别结果

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/660faa6fbabdccf025babd8542b2f7e6.svg)

1. **顶层模块（观演记录管理模块）**
+ ViewingRecordService：统一入口，接收用户请求并分发到各业务组件
2. **核心业务处理组件**
+ 记录管理：处理记录的增删改查
+ 视图展示：提供列表视图和日历视图
+ OCR识别：识别票面图片并提取信息
+ 图片上传：处理图片上传
3. **记录与追溯组件**
+ 记录管理器：执行记录操作
+ 视图渲染器：渲染列表和日历视图
+ OCR处理器：处理OCR识别逻辑
+ 图片上传处理器：处理图片上传逻辑
4. **数据持久化组件**
+ 负责所有数据的存储与读取
5. **外部模块**
+ WordPress媒体库：存储上传的图片
+ OCR服务商：提供OCR识别服务（如百度OCR）

### 类设计
#### 类概述
`Viewing_Records`是观影记录管理模块的核心类，采用单例模式（final class），所有方法均为静态方法。该类负责注册WordPress钩子、定义自定义文章类型、注册REST API路由、处理业务逻辑等。

#### 类结构
```markdown
final class Viewing_Records {
    // 初始化方法
    public static function init()
    
    // 短码注册与处理
    public static function register_shortcodes()
    public static function shortcode_viewing_manager($atts, $content)
    public static function shortcode_viewing_form($atts, $content)
    public static function shortcode_profile_viewings($atts, $content)
    public static function shortcode_recent_viewings($atts, $content)
    
    // 自定义文章类型与字段
    public static function register_viewing_post_type()
    public static function register_acf_fields()
    
    // REST API路由注册
    public static function register_rest_routes()
    
    // 观演记录CRUD API
    public static function rest_viewings_list($request)
    public static function rest_viewings_get($request)
    public static function rest_viewings_create($request)
    public static function rest_viewings_update($request)
    public static function rest_viewings_delete($request)
    
    // OCR相关API
    public static function rest_ocr($request)
    public static function rest_upload_image($request)
    
    // 工具方法
    public static function safe_get_field($field_name, $post_id)
    private static function extract_title($text)
    private static function extract_theater($text)
    private static function extract_cast($text)
    private static function extract_price($text)
    private static function extract_date($text)
    
    // 资源加载
    public static function enqueue_assets()
}
```

#### 核心方法说明
**init()**

+ **功能**：插件初始化，注册所有WordPress钩子
+ **调用时机**：插件激活时
+ **职责**：
    - 注册短码
    - 注册自定义文章类型
    - 注册REST API路由
    - 注册ACF字段
    - 加载前端资源

**register_viewing_post_type()**

+ **功能**：注册自定义文章类型`viewing_record`
+ **参数**：无
+ **返回值**：无
+ **说明**：定义观演记录的数据结构，支持REST API访问

**rest_viewings_list($request)**

+ **功能**：获取观演记录列表
+ **参数**：`$request` - WP_REST_Request对象
+ **返回值**：WP_REST_Response，包含记录数组
+ **查询参数**：
    - `category`：类别筛选
    - `search`：关键词搜索
    - `sort`：排序方式（date_desc/date_asc/title_asc/title_desc）

**rest_viewings_create($request)**

+ **功能**：创建新的观演记录
+ **参数**：`$request` - WP_REST_Request对象（JSON格式）
+ **返回值**：WP_REST_Response，包含新记录ID
+ **请求体字段**：见2.1.1节关键字段

**rest_viewings_update($request)**

+ **功能**：更新观演记录
+ **参数**：`$request` - WP_REST_Request对象（包含id参数和JSON请求体）
+ **返回值**：WP_REST_Response，包含更新结果
+ **权限检查**：只能更新自己的记录（管理员除外）

**rest_viewings_delete($request)**

+ **功能**：删除观演记录
+ **参数**：`$request` - WP_REST_Request对象（包含id参数）
+ **返回值**：WP_REST_Response，包含删除结果
+ **权限检查**：只能删除自己的记录（管理员除外）

**rest_ocr($request)**

+ **功能**：OCR识别接口
+ **参数**：`$request` - WP_REST_Request对象（包含图片文件）
+ **返回值**：WP_REST_Response，包含识别结果
+ **处理流程**：
    1. 接收图片文件
    2. 调用OCR服务商API
    3. 解析识别文本
    4. 提取结构化字段
    5. 返回结果

**rest_upload_image($request)**

+ **功能**：上传图片到WordPress媒体库
+ **参数**：`$request` - WP_REST_Request对象（包含文件）
+ **返回值**：WP_REST_Response，包含图片ID和URL
+ **文件验证**：检查文件类型、大小

### 类关联协作
#### 类关系概览
观影记录管理模块采用前后端分离的架构设计，通过REST API实现前后端通信。模块中的类主要分为三个层次：

1. **后端服务层**：`Viewing_Records`类（PHP）
2. **数据模型层**：`ViewingRecord`实体
3. **前端交互层**：`ViewingManager`、`OCRService`、`CalendarView`、`RESTClient`（JavaScript）

#### 核心类关系图
1. **关系概览**

![画板](https://cdn.nlark.com/yuque/0/2025/jpeg/61531748/1766834921811-97994116-543e-4c9d-a7b3-6b9b1b0cec20.jpeg)

2. **类图**

<!-- 这是一个文本绘图，源码为：@startuml 观影记录管理模块类图
!theme plain
skinparam classAttributeIconSize 0
skinparam linetype ortho

package "后端服务层" {
  class Viewing_Records <<PHP>> {
    +init() : void
    +register_shortcodes() : void
    +enqueue_assets() : void
    +register_viewing_post_type() : void
    +register_rest_routes() : void
    +register_acf_fields() : void
    +shortcode_viewing_manager() : string
    +rest_viewings_list(req) : WP_REST_Response
    +rest_viewings_get(req) : WP_REST_Response
    +rest_viewings_create(req) : WP_REST_Response
    +rest_viewings_update(req) : WP_REST_Response
    +rest_viewings_delete(req) : WP_REST_Response
    +rest_upload_image(req) : WP_REST_Response
    +rest_ocr(req) : WP_REST_Response
    +safe_get_field(field, post_id) : mixed
    --
    -default_baidu_ocr(bytes) : array
    -default_aliyun_ocr(bytes) : array
    -extract_title(text) : string
    -extract_theater(text) : string
    -extract_cast(text) : string
    -extract_price(text) : string
    -extract_date(text) : string
  }
}

package "数据模型层" {
  class ViewingRecord {
    +id : int
    +title : string
    +category : string
    +theater : string
    +cast : string
    +price : string
    +view_date : string
    +view_time_start : string
    +view_time_end : string
    +notes : string
    +ticket_image_id : int
    +ticket_image_url : string
    +author : string
    +url : string
  }
}

package "前端交互层" {
  class ViewingManager <<JS>> {
    +initViewingManager() : void
    +loadListView() : void
    +initCalendarView() : void
    +editViewing(id) : void
    +deleteViewing(id) : void
    +createViewing(data) : void
    +resetForm() : void
    +initFormDateInputs() : void
    +initImageUpload() : void
    +handleImageUpload(input, preview, idSelector) : void
    +initDateInput(textInput, datePicker) : void
    +initTimeValidation(start, end) : void
    +renderListView(records) : void
    +showRecordDetails(record) : void
    --
    -timeToMinutes(timeStr) : int
    -escapeHtml(text) : string
  }

  class RESTClient <<JS>> {
    +viewings : string
    +uploadImage : string
    +ocr : string
    +nonce : string
  }

  class OCRService <<JS/PHP>> {
    +rest_ocr(image) : object
    +default_baidu_ocr(bytes) : array
    +default_aliyun_ocr(bytes) : array
    +extract_title(text) : string
    +extract_theater(text) : string
    +extract_cast(text) : string
    +extract_price(text) : string
    +extract_date(text) : string
  }

  class CalendarView <<JS>> {
    +FullCalendar.Calendar
    +render(events) : void
    +updateEvents(events) : void
    +gotoDate(date) : void
  }
}

' 关系定义（使用标准UML关系类型）
ViewingManager ..> RESTClient : "依赖\n(Dependency)"
RESTClient ..> Viewing_Records : "依赖\n(Dependency)"
Viewing_Records "1" o-- "*" ViewingRecord : "聚合\n(Aggregation)"
ViewingManager ..> ViewingRecord : "依赖\n(Dependency)"
ViewingManager ..> OCRService : "依赖\n(Dependency)"
OCRService ..> ViewingRecord : "依赖\n(Dependency)"
ViewingManager --> CalendarView : "关联\n(Association)"

' 注释
note right of Viewing_Records
  后端核心控制器
  负责CRUD操作和
  REST API路由注册
end note

note right of ViewingManager
  前端管理界面核心类
  负责用户交互和
  界面渲染
end note

note right of RESTClient
  REST API客户端
  封装API调用逻辑
  提供统一的接口
end note

@enduml -->
![](https://cdn.nlark.com/yuque/__puml/bd584e357c7e6575cf7b83d51038c890.svg)

#### 详细类关系说明
1. **Viewing_Records 与 ViewingRecord 的关系**

**关系类型**：聚合关系（Aggregation）

**关系描述**：

    - `Viewing_Records`类负责管理`ViewingRecord`实体的完整生命周期
    - `Viewing_Records`通过WordPress的`WP_Query`查询观演记录，将数据库记录转换为`ViewingRecord`对象
    - 在REST API响应中，`Viewing_Records`将`ViewingRecord`数据序列化为JSON格式返回给前端

**协作方式**：

    - **创建记录**：`rest_viewings_create()`方法接收前端数据，创建WordPress文章，并将字段保存为ACF元数据，形成`ViewingRecord`实体
    - **查询记录**：`rest_viewings_list()`和`rest_viewings_get()`方法查询数据库，将查询结果组装成`ViewingRecord`对象数组返回
    - **更新记录**：`rest_viewings_update()`方法根据ID查找记录，更新字段值
    - **删除记录**：`rest_viewings_delete()`方法删除对应的`ViewingRecord`实体



2. **ViewingManager 与 RESTClient 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `ViewingManager`是前端管理界面的核心类，负责用户交互和界面渲染
    - `RESTClient`是REST API的客户端封装，提供统一的API调用接口
    - `ViewingManager`通过`RESTClient`与后端`Viewing_Records`类通信

**协作方式**：

    - **初始化**：`ViewingManager`在初始化时从`ViewingRecords.rest`对象（由`RESTClient`提供）获取API端点和nonce
    - **数据获取**：`loadListView()`方法通过`RESTClient`调用`GET /viewing/v1/viewings`获取记录列表
    - **数据操作**：`editViewing()`、`deleteViewing()`等方法通过`RESTClient`发送PUT、DELETE请求
    - **错误处理**：`RESTClient`返回的错误由`ViewingManager`统一处理和展示



3. **RESTClient 与 Viewing_Records 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `RESTClient`是前端JavaScript对象，封装了REST API的调用逻辑
    - `Viewing_Records`是后端PHP类，提供REST API端点处理
    - `RESTClient`通过HTTP请求调用`Viewing_Records`注册的REST路由
    - `RESTClient`依赖于`Viewing_Records`提供的API接口，符合依赖关系的特点

**协作方式**：

    - **路由注册**：`Viewing_Records::register_rest_routes()`方法注册所有REST API路由
    - **端点注入**：`Viewing_Records::enqueue_assets()`方法通过`wp_localize_script`将API端点URL注入到前端`ViewingRecords.rest`对象
    - **请求处理**：`RESTClient`发送HTTP请求到对应端点，`Viewing_Records`的静态方法处理请求并返回响应
    - **认证机制**：`RESTClient`在请求头中携带WordPress Nonce，`Viewing_Records`验证nonce确保请求安全



4. ViewingManager 与 ViewingRecord 的关系

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `ViewingManager`负责在界面上渲染`ViewingRecord`数据
    - `ViewingManager`提供表单供用户编辑`ViewingRecord`的字段
    - `ViewingManager`将用户输入转换为`ViewingRecord`对象发送给后端
    - `ViewingManager`使用`ViewingRecord`的数据结构，但不拥有它，符合依赖关系的特点

**协作方式**：

    - **列表渲染**：`loadListView()`方法接收`ViewingRecord`数组，生成HTML列表展示
    - **详情展示**：点击记录时，`ViewingManager`获取完整的`ViewingRecord`对象，在模态框中展示所有字段
    - **表单编辑**：`editViewing(id)`方法获取`ViewingRecord`数据，填充到编辑表单中
    - **数据提交**：用户提交表单时，`ViewingManager`收集表单数据，组装成`ViewingRecord`对象，通过`RESTClient`发送给后端



5. **ViewingManager 与 OCRService 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `ViewingManager`提供OCR识别功能的用户界面
    - `OCRService`负责实际的OCR识别处理
    - 用户上传图片后，`ViewingManager`触发`OCRService`进行识别
    - `ViewingManager`使用`OCRService`提供的识别功能，符合依赖关系的特点

**协作方式**：

    - **图片上传**：用户在`ViewingManager`的表单中上传票面图片
    - **触发识别**：`ViewingManager`调用`OCRService::rest_ocr()`方法，发送图片到后端
    - **识别处理**：`OCRService`调用第三方OCR API（百度/阿里云），解析识别结果
    - **字段填充**：`OCRService`返回识别结果，`ViewingManager`自动填充表单字段



6. **ViewingManager 与 CalendarView 的关系**

**关系类型**：关联关系（Association）

**关系描述**：

    - `ViewingManager`负责管理视图切换（列表视图/日历视图）
    - `CalendarView`是基于FullCalendar.js的日历组件
    - `ViewingManager`控制`CalendarView`的初始化和数据加载
    - `ViewingManager`与`CalendarView`之间存在持久的引用关系，符合关联关系的特点

**协作方式**：

    - **视图切换**：用户点击"日历"按钮，`ViewingManager`切换到日历视图
    - **初始化日历**：`initCalendarView()`方法创建FullCalendar实例
    - **数据加载**：`ViewingManager`从后端获取`ViewingRecord`数组，转换为日历事件格式
    - **事件渲染**：`CalendarView`根据`ViewingRecord`的`view_date`字段在日历上标记观演日期
    - **交互处理**：用户点击日历上的事件，`ViewingManager`显示对应的`ViewingRecord`详情



7. **OCRService 与 ViewingRecord 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `OCRService`识别票面图片后，提取结构化数据
    - 提取的数据用于填充`ViewingRecord`的字段
    - `OCRService`不直接操作`ViewingRecord`，而是返回数据供`ViewingManager`使用
    - `OCRService`依赖于`ViewingRecord`的数据结构定义，符合依赖关系的特点

**协作方式**：

    - **图片识别**：`OCRService::rest_ocr()`接收图片，调用OCR API识别文字
    - **数据提取**：`OCRService`使用`extract_title()`、`extract_theater()`等方法从识别文本中提取字段值
    - **返回结果**：返回包含`title`、`theater`、`cast`、`price`、`view_date`的对象
    - **字段映射**：`ViewingManager`将OCR结果映射到`ViewingRecord`的对应字段

## <font style="color:rgb(38,38,38);">数据统计与可视化模块</font>
### 模块概述
#### 模块简介
数据统计与可视化模块是乐影集（Musicalbum）系统的重要组成部分，负责对用户的观演记录进行多维度数据分析和可视化展示。该模块基于WordPress REST API提供统计数据接口，前端使用Chart.js库进行图表渲染，支持多种图表类型和交互式数据探索，帮助用户深入了解自己的观演习惯和偏好。

#### 技术架构
+ **后端框架**：WordPress Plugin API
+ **数据源**：观演记录（viewing_record自定义文章类型）
+ **前端技术**：jQuery、Chart.js 4.4.0
+ **API协议**：WordPress REST API
+ **图表库**：Chart.js（支持饼图、柱状图、折线图、环形图等）

#### 模块职责
1. 观演数据的多维度统计分析
2. 数据可视化图表生成与展示
3. 统计数据导出（CSV/JSON格式）
4. 数据概览仪表板展示
5. 交互式数据探索（点击图表查看详情）

### 核心功能设计
#### 数据统计功能
+ **剧目类别分布统计**

**功能描述**：统计用户观演记录中各剧目类别的分布情况，包括音乐剧、话剧、歌剧、舞剧、音乐会、戏曲等。

**统计逻辑**：

        1. 遍历所有观演记录
        2. 提取每条记录的`category`字段
        3. 如果`category`为空，则从标题中提取类别（使用关键词匹配）
        4. 按类别分组计数
        5. 返回各类别的观演次数
+ **演员出场频率统计**

**功能描述**：统计演员在观演记录中的出场频率，识别用户最常观看的演员。

**统计逻辑**：

        1. 遍历所有观演记录
        2. 提取每条记录的`cast`字段（卡司信息）
        3. 从卡司文本中提取演员姓名（支持多种分隔符：逗号、顿号、分号、换行）
        4. 按演员姓名分组计数
        5. 按出场次数降序排序
        6. 返回前10名演员及其出场次数
+ **票价区间分布统计**

**功能描述**：统计观演记录的票价分布情况，按价格区间分组展示。

**统计逻辑**：

        1. 遍历所有观演记录
        2. 提取每条记录的`price`字段
        3. 从价格文本中提取数字（去除货币符号等）
        4. 计算所有价格的最小值和最大值
        5. 动态确定区间大小（至少50元，最多10个区间）
        6. 将价格分配到对应区间
        7. 统计每个区间的记录数量
+ **剧院分布统计**

**功能描述**：统计用户在不同剧院的观演次数，识别常去的剧院。

**统计逻辑**：

        1. 遍历所有观演记录
        2. 提取每条记录的`theater`字段
        3. 按剧院名称分组计数
        4. 按观演次数降序排序
        5. 返回前10名剧院及其观演次数
+ **数据概览统计**

**功能描述**：提供观演数据的核心指标概览，包括总记录数、本月观演次数、总花费、最爱类别等。

**统计指标**：

        1. **总记录数**：用户所有观演记录的总数
        2. **本月观演**：当前月份的观演次数
        3. **总花费**：所有观演记录的价格总和
        4. **最爱类别**：观演次数最多的剧目类别

#### 数据可视化功能
+ **固定图表展示**

**功能描述**：在统计页面固定显示三个核心图表，提供快速数据洞察。

**图表类型**：

        1. **剧目类别分布图**：饼图或柱状图
        2. **演员出场频率图**：柱状图或折线图
        3. **票价区间分布图**：柱状图或折线图

**交互功能**：

        * 图表悬停显示详细数值
        * 点击图表元素查看对应记录列表
        * 图表自动响应式适配
+ **自定义图表生成**

**功能描述**：用户可根据需要选择数据类型和图表类型，动态生成自定义图表。

**支持的数据类型**：

        * 剧目类别（category）
        * 剧院（theater）
        * 演员出场频率（cast）
        * 票价区间（price）

**支持的图表类型**：

        * 饼图（pie）
        * 柱状图（bar）
        * 折线图（line）
        * 环形图（doughnut）

**交互流程**：

        1. 用户选择数据类型
        2. 用户选择图表类型
        3. 点击"生成图表"按钮
        4. 系统加载对应数据
        5. 渲染图表
+ **图表交互功能**

**功能描述**：提供丰富的图表交互功能，增强用户体验。

**交互特性**：

        * **悬停提示**：鼠标悬停显示详细数据
        * **点击查看详情**：点击图表元素查看对应的观演记录列表
        * **图例交互**：点击图例显示/隐藏数据系列
        * **缩放功能**：支持图表缩放（部分图表类型）

#### 数据导出功能
+ **CSV导出**

**功能描述**：将观演统计数据导出为CSV格式文件，便于在Excel等工具中进一步分析。

**导出内容**：

        * 标题
        * 类别
        * 剧院
        * 卡司
        * 票价
        * 观演日期

**文件格式**：

        * 编码：UTF-8 with BOM（支持中文）
        * 分隔符：逗号
        * 文件名：`观演统计_YYYY-MM-DD.csv`
+ **JSON导出**

**功能描述**：将观演统计数据导出为JSON格式文件，便于程序化处理。

**导出内容**：完整的记录数组（JSON格式）

**文件格式**：

        * 编码：UTF-8
        * 格式：标准JSON
        * 文件名：`观演统计_YYYY-MM-DD.json`

#### 数据详情查看
+ **按类别查看详情**

**功能描述**：点击类别图表元素，查看该类别下的所有观演记录。

**查询参数**：

        * `type`：category
        * `value`：类别名称（如"音乐剧"）
        * `page`：页码（分页）
        * `per_page`：每页记录数
+ **按演员查看详情**

**功能描述**：点击演员图表元素，查看包含该演员的所有观演记录。

**查询参数**：

        * `type`：cast
        * `value`：演员姓名
        * `page`：页码
        * `per_page`：每页记录数
+ **按票价区间查看详情**

**功能描述**：点击票价区间图表元素，查看该价格区间内的所有观演记录。

**查询参数**：

        * `type`：price
        * `value`：价格区间（如"100-150元"）
        * `page`：页码
        * `per_page`：每页记录数
+ **按剧院查看详情**

**功能描述**：点击剧院图表元素，查看该剧院的所有观演记录。

**查询参数**：

        * `type`：theater
        * `value`：剧院名称
        * `page`：页码
        * `per_page`：每页记录数

### 功能细化视图
<!-- 这是一个文本绘图，源码为：@startuml 数据统计与可视化模块功能细化图

skinparam componentStyle rectangle
skinparam rectangle {
    BackgroundColor<<component>> #E1F5FE
    BackgroundColor<<external>> #FFF9C4
    BorderColor #01579B
    BorderThickness 2
}

title 数据统计与可视化模块功能细化图

package "数据统计与可视化模块" {
    component StatisticsVisualizationService <<component>>
}

package "核心业务处理组件" {
    component "数据统计" <<component>> as StatisticsService
    component "数据可视化" <<component>> as VisualizationService
    component "数据导出" <<component>> as ExportService
    component "数据详情" <<component>> as DetailService
}

package "记录与追溯组件" {
    component "统计处理器" <<component>> as StatisticsProcessor
    component "图表渲染器" <<component>> as ChartRenderer
    component "导出处理器" <<component>> as ExportProcessor
    component "详情查询器" <<component>> as DetailQuery
}

component "数据持久化组件" <<component>> as DataPersistence

cloud "外部模块" {
    component "<<外部>>\n图表库\n(Chart.js等)" <<external>> as ChartLibrary
}

' StatisticsVisualizationService 到核心业务处理组件的交互
StatisticsVisualizationService ..> StatisticsService : 请求统计数据\n(类别/演员/票价/剧院/概览)
StatisticsVisualizationService ..> VisualizationService : 生成/渲染图表
StatisticsVisualizationService ..> ExportService : 导出统计数据
StatisticsVisualizationService ..> DetailService : 查看数据详情

' 核心业务处理组件到记录与追溯组件的交互
StatisticsService --> StatisticsProcessor : 处理统计计算\n(分布/频率/区间统计)
VisualizationService --> ChartRenderer : 渲染图表\n(固定图表/自定义图表)
ExportService --> ExportProcessor : 处理导出请求\n(CSV/JSON格式)
DetailService --> DetailQuery : 查询详情记录\n(按类别/演员/票价/剧院)

' 记录与追溯组件到数据持久化组件的交互
StatisticsProcessor --> DataPersistence : 获取观演记录数据
DetailQuery --> DataPersistence : 查询筛选记录
ChartRenderer --> DataPersistence : 获取图表数据

' 与外部模块的交互
ChartRenderer ..> ChartLibrary : 使用图表库渲染\n(饼图/柱状图/折线图等)

@enduml

 -->
![](https://cdn.nlark.com/yuque/__puml/34cb22974e9a84ed4e11ceddac5be0d6.svg)

1. **顶层模块（数据统计与可视化模块）**
+ StatisticsVisualizationService：统一入口，接收用户请求并分发到各业务组件
2. **核心业务处理组件**
+ 数据统计：处理各类统计计算（类别分布、演员频率、票价区间、剧院分布、数据概览）
+ 数据可视化：生成和渲染图表（固定图表、自定义图表）
+ 数据导出：处理数据导出（CSV、JSON）
+ 数据详情：提供详情查看功能（按类别/演员/票价/剧院筛选）
3. **记录与追溯组件**
+ 统计处理器：执行统计计算（分布、频率、区间）
+ 图表渲染器：渲染图表（固定图表、自定义图表）
+ 导出处理器：处理导出格式转换（CSV、JSON）
+ 详情查询器：查询筛选后的记录详情
4. **数据持久化组件**
+ 负责所有数据的存储与读取
5. **外部模块**
+ 图表库（Chart.js等）：提供图表渲染能力（饼图、柱状图、折线图等）

### 类设计
#### 主类：Viewing_Records（统计相关方法）
1. **统计方法结构**

```markdown
final class Viewing_Records {
    // 统计数据API
    public static function rest_statistics($request)
    public static function rest_statistics_details($request)
    public static function rest_statistics_export($request)
    public static function rest_overview($request)
    
    // 统计短码
    public static function shortcode_statistics($atts, $content)
    public static function shortcode_custom_chart($atts, $content)
    public static function shortcode_viewing_overview($atts, $content)
    
    // 数据提取与处理工具方法
    private static function extract_category_from_title($title)
    private static function extract_actors_from_cast($cast)
    private static function calculate_price_ranges($prices)
}
```

2. **核心方法说明**

**rest_statistics($request)**

+ **功能**：获取统计数据（类别、演员、票价、剧院分布）
+ **参数**：`$request` - WP_REST_Request对象
+ **返回值**：WP_REST_Response，包含统计数据对象
+ **权限**：普通用户只能查看自己的数据，管理员可查看所有数据

**rest_overview($request)**

+ **功能**：获取数据概览（总记录数、本月观演、总花费、最爱类别）
+ **参数**：`$request` - WP_REST_Request对象
+ **返回值**：WP_REST_Response，包含概览数据
+ **计算逻辑**：
    - 遍历所有记录统计总数
    - 筛选当前月份的记录
    - 累加所有价格
    - 找出观演次数最多的类别

**rest_statistics_details($request)**

+ **功能**：根据筛选条件获取观演记录详情列表
+ **参数**：`$request` - WP_REST_Request对象
+ **查询参数**：
    - `type`：筛选类型（category/cast/price/theater）
    - `value`：筛选值
    - `page`：页码
    - `per_page`：每页记录数
+ **返回值**：WP_REST_Response，包含记录列表和分页信息
+ **特殊处理**：票价区间筛选需要手动过滤和分页

**rest_statistics_export($request)**

+ **功能**：导出统计数据
+ **参数**：`$request` - WP_REST_Request对象
+ **查询参数**：
    - `format`：导出格式（csv/json）
+ **返回值**：文件下载响应
+ **处理逻辑**：
    - 查询所有记录
    - 根据格式生成文件内容
    - 设置HTTP响应头
    - 输出文件内容

**extract_category_from_title($title)**

+ **功能**：从标题中提取剧目类别
+ **参数**：`$title` - 剧目标题字符串
+ **返回值**：类别名称字符串
+ **匹配规则**：使用关键词匹配（支持中英文关键词）

**extract_actors_from_cast($cast)**

+ **功能**：从卡司文本中提取演员姓名列表
+ **参数**：`$cast` - 卡司文本字符串
+ **返回值**：演员姓名数组
+ **处理逻辑**：
    1. 移除常见前缀（主演、卡司等）
    2. 按多种分隔符分割（逗号、顿号、分号、换行）
    3. 过滤空值和过长文本（可能是误识别）

**calculate_price_ranges($prices)**

+ **功能**：计算票价区间分布
+ **参数**：`$prices` - 价格数组
+ **返回值**：区间分布数组
+ **算法**：
    1. 排序价格数组
    2. 计算最小值和最大值
    3. 动态确定区间大小（至少50元，最多10个区间）
    4. 将价格分配到对应区间
    5. 统计每个区间的数量

#### 前端JavaScript类设计
1. **统计模块结构**

```markdown
// 图表实例管理
var chartInstances = {
  category: null,
  cast: null,
  price: null,
  main: null  // 自定义图表实例
};

// 统计数据缓存
var statisticsData = {};

// 核心函数
function loadStatistics(callback)
function generateChart(dataType, chartType, instanceId)
function renderDynamicChart(data, chartType, dataType, instanceId)
function renderCategoryChart(data)
function renderCastChart(data)
function renderPriceChart(data)
function showDetails(dataType, value)
function exportStatistics()
```

2. **核心函数说明**

**loadStatistics(callback)**

+ **功能**：加载统计数据并渲染固定图表
+ **参数**：`callback` - 回调函数（可选）
+ **流程**：
    1. 显示加载提示
    2. 调用REST API获取数据
    3. 缓存数据
    4. 销毁旧图表
    5. 渲染新图表
    6. 执行回调

**generateChart(dataType, chartType, instanceId)**

+ **功能**：生成自定义图表
+ **参数**：
    - `dataType`：数据类型
    - `chartType`：图表类型
    - `instanceId`：实例ID（支持多个图表实例）
+ **流程**：
    1. 检查数据是否已加载
    2. 获取对应数据
    3. 销毁旧图表
    4. 更新标题
    5. 渲染图表

**renderDynamicChart(data, chartType, dataType, instanceId)**

+ **功能**：动态渲染图表（使用Chart.js）
+ **参数**：
    - `data`：图表数据对象
    - `chartType`：图表类型
    - `dataType`：数据类型
    - `instanceId`：实例ID
+ **处理**：
    - 提取标签和数值
    - 对票价数据进行排序
    - 配置图表选项
    - 创建Chart.js实例

**showDetails(dataType, value)**

+ **功能**：显示数据详情（点击图表元素时调用）
+ **参数**：
    - `dataType`：数据类型
    - `value`：筛选值
+ **流程**：
    1. 调用详情API获取记录列表
    2. 显示模态框
    3. 渲染记录列表
    4. 支持分页

**exportStatistics()**

+ **功能**：导出统计数据
+ **流程**：
    1. 调用导出API
    2. 触发文件下载

### 类关联协作
#### 类关系概览
数据统计与可视化模块采用数据驱动的架构设计，通过统计分析将观演记录数据转换为可视化图表。模块中的类主要分为四个层次：

1. **后端服务层**：`Viewing_Records`类（PHP）提供统计API
2. **数据模型层**：`StatisticsData`对象
3. **前端业务层**：`StatisticsModule`（JavaScript）
4. **视图渲染层**：`ChartJS`、`DetailsModal`、`ExportService`（JavaScript）

#### 核心类关系图
1. **关系概览**

![画板](https://cdn.nlark.com/yuque/0/2025/jpeg/61531748/1766930471864-3790e889-328b-48ce-8139-6a4150562cbc.jpeg)

2. **类图**

<!-- 这是一个文本绘图，源码为：@startuml 数据统计与可视化模块类图
!theme plain
skinparam classAttributeIconSize 0
skinparam linetype ortho

package "后端服务层" {
  class Viewing_Records <<PHP>> {
    +shortcode_statistics() : string
    +shortcode_custom_chart() : string
    +shortcode_viewing_overview() : string
    +rest_statistics(req) : WP_REST_Response
    +rest_statistics_details(req) : WP_REST_Response
    +rest_statistics_export(req) : WP_REST_Response
    +rest_overview(req) : WP_REST_Response
    --
    -extract_category_from_title(title) : string
    -extract_actors_from_cast(cast) : array
    -calculate_price_ranges(prices) : array
  }
}

package "数据模型层" {
  class StatisticsData {
    +category : Map<string, int>
    +cast : Map<string, int>
    +price : Map<string, int>
    +theater : Map<string, int>
  }

  class OverviewData {
    +total_count : int
    +month_count : int
    +total_spending : float
    +favorite_category : string
  }
}

package "前端业务层" {
  class StatisticsModule <<JS>> {
    +loadStatistics(callback) : void
    +renderCategoryChart(data) : void
    +renderCastChart(data) : void
    +renderPriceChart(data) : void
    +generateChart(dataType, chartType, instanceId) : void
    +renderDynamicChart(data, chartType, dataType, instanceId) : void
    +showDetails(type, value) : void
    +exportStatistics() : void
    +loadOverview(instanceEl) : void
    --
    -getChartOptions(chartType, dataType) : object
    -getDatasetLabel(dataType) : string
    -generateColors(chartType, count) : array|string
    -generatePieColors(count) : array
  }

  class RESTClient <<JS>> {
    +statistics : string
    +statisticsDetails : string
    +statisticsExport : string
    +overview : string
    +nonce : string
  }
}

package "视图渲染层" {
  class ChartJS <<Lib>> {
    +Chart(ctx, config) : Chart
    +chart.destroy() : void
    +chart.update() : void
    +chart.render() : void
  }

  class DetailsModal <<JS>> {
    +open() : void
    +update(content) : void
    +close() : void
    +renderDetailsList(records) : string
  }

  class ExportService <<JS>> {
    +exportData(format) : void
    +exportChart(chartType) : void
    +downloadFile(url) : void
  }
}

package "数据缓存" {
  object statisticsData {
    StatisticsData对象
    存储在内存中
    避免重复请求
  }

  object chartInstances {
    图表实例对象
    管理所有Chart实例
    便于刷新和导出
  }
}

' 关系定义（使用标准UML关系类型）
StatisticsModule ..> RESTClient : "依赖\n(Dependency)"
RESTClient ..> Viewing_Records : "依赖\n(Dependency)"
Viewing_Records ..> StatisticsData : "依赖\n(Dependency)"
Viewing_Records ..> OverviewData : "依赖\n(Dependency)"
StatisticsModule --> statisticsData : "关联\n(Association)"
StatisticsModule ..> ChartJS : "依赖\n(Dependency)"
StatisticsModule --> chartInstances : "关联\n(Association)"
StatisticsModule --> DetailsModal : "关联\n(Association)"
DetailsModal ..> RESTClient : "依赖\n(Dependency)"
StatisticsModule ..> ExportService : "依赖\n(Dependency)"
ExportService ..> RESTClient : "依赖\n(Dependency)"

' 注释
note right of Viewing_Records
  后端统计服务
  提供统计API和
  数据聚合功能
end note

note right of StatisticsModule
  前端统计模块核心类
  负责数据加载、图表渲染
  和用户交互协调
end note

note right of ChartJS
  第三方图表库
  负责图表渲染和
  交互事件处理
end note

note bottom of statisticsData
  内存缓存对象
  存储统计数据
  提高性能
end note

note bottom of chartInstances
  图表实例管理器
  存储所有Chart实例
  支持刷新和导出
end note

@enduml -->
![](https://cdn.nlark.com/yuque/__puml/461c5a05ecd9b485fb085da0079289c6.svg)

#### 详细类关系说明
1. **Viewing_Records 与 StatisticsData 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `Viewing_Records`类通过`rest_statistics()`方法查询所有观演记录，进行统计分析
    - 统计结果被组织成`StatisticsData`对象返回给前端
    - `Viewing_Records`使用`StatisticsData`的数据结构来组织返回数据，符合依赖关系的特点

**协作方式**：

    - **数据查询**：`rest_statistics()`方法使用`WP_Query`查询所有观演记录
    - **统计分析**：遍历记录，统计类别、演员、票价、剧院等维度的数据
    - **数据聚合**：将统计结果组织成`StatisticsData`格式
    - **返回数据**：以JSON格式返回`StatisticsData`对象



2. **StatisticsModule 与 StatisticsData 的关系**

**关系类型**：关联关系（Association）

**关系描述**：

    - `StatisticsModule`是前端统计模块的核心类
    - `StatisticsData`是统计数据的内存缓存对象
    - `StatisticsModule`持有`StatisticsData`的引用，从缓存读取数据用于图表渲染，也可以更新缓存
    - `StatisticsModule`与`StatisticsData`之间存在持久的引用关系，符合关联关系的特点

**协作方式**：

    - **数据加载**：`loadStatistics()`方法从后端获取数据，保存到`statisticsData`变量
    - **数据读取**：`generateChart()`、`renderCategoryChart()`等方法从`statisticsData`读取数据
    - **数据缓存**：避免重复请求，提高性能
    - **数据更新**：点击刷新按钮时，重新加载数据并更新`statisticsData`



3. **StatisticsModule 与 RESTClient 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `StatisticsModule`依赖`RESTClient`获取统计数据
    - `RESTClient`提供统一的REST API调用接口
    - `StatisticsModule`通过`RESTClient`与后端`Viewing_Records`通信
    - `StatisticsModule`使用`RESTClient`提供的API端点，符合依赖关系的特点

**协作方式**：

    - **API端点**：`StatisticsModule`从`ViewingRecords.rest`对象获取API端点URL
    - **数据获取**：`loadStatistics()`通过`RESTClient`调用`GET /viewing/v1/statistics`
    - **详情查询**：`showDetails()`通过`RESTClient`调用`GET /viewing/v1/statistics/details`
    - **数据导出**：`exportStatistics()`通过`RESTClient`调用`GET /viewing/v1/statistics/export`



4. **StatisticsModule 与 ChartJS 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `StatisticsModule`负责准备数据和配置
    - `ChartJS`是第三方图表库，负责实际的图表渲染
    - `StatisticsModule`调用`ChartJS`的API创建和更新图表
    - `StatisticsModule`使用`ChartJS`提供的图表功能，符合依赖关系的特点

**协作方式**：

    - **数据准备**：`StatisticsModule`从`StatisticsData`提取数据，转换为`ChartJS`需要的格式
    - **配置生成**：`getChartOptions()`方法生成图表配置对象
    - **图表创建**：调用`new Chart(ctx, config)`创建图表实例
    - **实例管理**：将图表实例保存到`chartInstances`对象，便于刷新和导出
    - **图表更新**：刷新数据时，先销毁旧图表，再创建新图表



5. **StatisticsModule 与 DetailsModal 的关系**

**关系类型**：关联关系（Association）

**关系描述**：

    - `StatisticsModule`负责触发详情查看
    - `DetailsModal`负责显示详情内容
    - 用户点击图表元素时，`StatisticsModule`创建或获取`DetailsModal`实例并打开它
    - `StatisticsModule`与`DetailsModal`之间存在持久的引用关系，符合关联关系的特点

**协作方式**：

    - **事件绑定**：图表配置中的`onClick`回调函数由`StatisticsModule`定义
    - **模态框创建**：`showDetails()`方法动态创建或显示`DetailsModal`
    - **数据加载**：`DetailsModal`通过`RESTClient`获取详情数据
    - **内容更新**：将获取的记录列表渲染到`DetailsModal`中
    - **交互处理**：用户点击记录链接，跳转到详情页



6. **StatisticsModule 与 ExportService 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `StatisticsModule`提供导出功能的用户界面
    - `ExportService`负责实际的导出操作
    - 用户点击导出按钮时，`StatisticsModule`调用`ExportService`执行导出
    - `StatisticsModule`使用`ExportService`提供的导出功能，符合依赖关系的特点

**协作方式**：

    - **导出菜单**：`exportStatistics()`方法显示导出选项菜单
    - **格式选择**：用户选择导出格式（CSV/JSON）或图表类型
    - **触发导出**：`ExportService`根据选择调用相应的REST API端点
    - **文件下载**：后端返回文件流，浏览器自动下载



7. **RESTClient 与 Viewing_Records 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `RESTClient`是前端JavaScript对象，封装REST API调用
    - `Viewing_Records`是后端PHP类，提供统计相关的REST API端点
    - `RESTClient`通过HTTP请求调用`Viewing_Records`的统计方法
    - `RESTClient`依赖于`Viewing_Records`提供的API接口，符合依赖关系的特点

**协作方式**：

    - **路由注册**：`Viewing_Records::register_rest_routes()`注册统计相关路由
    - **端点注入**：`Viewing_Records::enqueue_assets()`将API端点URL注入到前端
    - **请求处理**：`RESTClient`发送请求，`Viewing_Records`的静态方法处理
    - **响应返回**：`Viewing_Records`返回JSON格式的统计数据

**统计相关API端点**：

    - `GET /viewing/v1/statistics` → `rest_statistics()`
    - `GET /viewing/v1/statistics/details` → `rest_statistics_details()`
    - `GET /viewing/v1/statistics/export` → `rest_statistics_export()`
    - `GET /viewing/v1/overview` → `rest_overview()`



8. **DetailsModal 与 RESTClient 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：

    - `DetailsModal`需要显示具体记录列表时，通过`RESTClient`获取数据
    - `DetailsModal`不直接调用后端，而是通过`RESTClient`间接调用
    - `DetailsModal`使用`RESTClient`提供的API端点，符合依赖关系的特点

**协作方式**：

    - **参数传递**：`DetailsModal`接收统计类型和值作为参数
    - **API调用**：通过`RESTClient`调用`/viewing/v1/statistics/details`端点
    - **数据渲染**：将返回的记录列表渲染到模态框中

## <font style="color:rgb(38,38,38);">社区交流模块</font>
### 模块概述
#### 模块简介
社区交流模块是乐影集（Musicalbum）系统的社交核心，基于成熟的 BuddyPress 和 bbPress 框架进行深度定制与开发。该模块旨在打破用户间的信息孤岛，通过构建“观演+社交”的互动生态，使用户能够围绕音乐剧内容建立连接。模块支持用户资料扩展（展示观演足迹）、动态流发布、兴趣小组（剧院/剧目圈子）以及论坛讨论等功能，为音乐剧爱好者提供一个垂直、专业的交流平台。

#### 技术架构
+ **核心框架**：BuddyPress Core, bbPress Core
+ **扩展插件**：Musicalbum Community Integration (自研粘合插件)
+ **数据源**：`wp_users`, `wp_bp_*` (BuddyPress 自定义表), `wp_posts` (论坛帖子)
+ **前端技术**：BuddyPress Nouveau Templates, jQuery, React (部分动态组件)
+ **API协议**：BuddyPress REST API + WordPress REST API

#### 模块职责
1. 用户个人资料管理与观演数据展示
2. 社交关系链管理（好友、关注）
3. 动态流（Activity Stream）的发布与分发
4. 兴趣小组（Groups）的创建与管理
5. 论坛（Forums）话题讨论与内容沉淀
6. 消息通知与私信系统

### 核心功能设计
#### 交友互动功能
+ **好友关系管理**

**功能描述**：支持用户发送好友请求、接受/拒绝申请以及解除好友关系。

**业务流程**：
        1. 用户在其他成员主页点击“加为好友”
        2. 系统发送好友申请通知（Notification）
        3. 对方在“好友请求”列表处理申请
        4. 建立关系后，双方动态互通，并可发送私信

+ **私信与群聊**

**功能描述**：提供点对点的即时消息发送以及基于兴趣小组的群组聊天功能。

**交互逻辑**：
        * **私信**：支持发送文本、表情及观演记录卡片
        * **通知**：新消息触发红点提示与邮件提醒
        * **权限**：仅好友可发私信或全站开放（可配置）

#### 动态流（Activity Stream）功能
+ **观演动态自动同步**

**功能描述**：当用户在“观演记录管理模块”新增一条记录时，自动同步生成一条社交动态。

**业务流程**：
        1. 监听 `viewing_record_created` 动作钩子
        2. 获取新记录的标题、评分、剧照等元数据
        3. 组装 BuddyPress Activity 数据结构
        4. 写入 `wp_bp_activity` 表
        5. 粉丝即时在动态流中看到该更新

+ **互动交流**

**功能描述**：支持对动态进行富文本评论、点赞、收藏及转发。

**交互逻辑**：
        * **@提及**：支持在评论中 @其他用户，触发通知
        * **嵌套评论**：支持多级回复结构
        * **实时更新**：使用 AJAX 实现无刷新发布与加载

#### 兴趣小组功能
+ **剧院/剧目圈子**

**功能描述**：建立以“剧院”或“热门剧目”为维度的兴趣小组，聚合相关话题与爱好者。

**功能特性**：
        * **公有/私有小组**：支持开放加入或申请加入
        * **小组专属动态**：小组内的讨论仅成员可见（私有）或聚合展示
        * **小组关联**：小组可关联特定的剧院或剧目页面

#### 论坛讨论功能
+ **话题发布与回复**

**功能描述**：提供结构化的论坛版块（如“剧评专区”、“票务转让”、“组队观演”），支持长文发布与楼层式回复。

**业务流程**：
        1. 用户选择版块发布新话题（Topic）
        2. 支持插入图片、表情、引用观演记录
        3. 其他用户进行回帖（Reply）
        4. 版主/管理员置顶、加精或下沉帖子

### 功能细化视图
<!-- 这是一个文本绘图，源码为：@startuml 社区交流模块功能细化图

skinparam componentStyle rectangle
skinparam rectangle {
    BackgroundColor<<component>> #E1F5FE
    BackgroundColor<<external>> #FFF9C4
    BorderColor #01579B
    BorderThickness 2
}

title 社区交流模块功能细化图

package "社区交流模块" {
    component CommunityIntegrationService <<component>>
}

package "核心业务处理组件" {
    component "个人资料扩展" <<component>> as ProfileExtension
    component "动态流管理" <<component>> as ActivityManager
    component "小组管理" <<component>> as GroupManager
    component "论坛管理" <<component>> as ForumManager
}

package "交互与通知组件" {
    component "互动处理器" <<component>> as InteractionProcessor
    component "通知分发器" <<component>> as NotificationDispatcher
    component "权限校验器" <<component>> as PermissionChecker
}

component "数据持久化组件" <<component>> as DataPersistence

cloud "外部模块" {
    component "<<外部>>\nBuddyPress Core" <<external>> as BPCore
    component "<<外部>>\nbbPress Core" <<external>> as bbPressCore
    component "<<外部>>\n观演记录模块" <<external>> as ViewingModule
}

' CommunityIntegrationService 到核心业务处理组件的交互
CommunityIntegrationService ..> ProfileExtension : 注入资料页
CommunityIntegrationService ..> ActivityManager : 同步观演动态
CommunityIntegrationService ..> GroupManager : 创建剧院小组
CommunityIntegrationService ..> ForumManager : 嵌入版块

' 核心业务处理组件到交互与通知组件的交互
ActivityManager --> InteractionProcessor : 处理点赞/评论
GroupManager --> PermissionChecker : 校验入群权限
ForumManager --> NotificationDispatcher : 发送回帖通知
ProfileExtension --> ViewingModule : 获取观演数据

' 交互与通知组件到外部核心的交互
InteractionProcessor ..> BPCore : 调用 Activity API
NotificationDispatcher ..> BPCore : 调用 Notification API
ForumManager ..> bbPressCore : 调用 Forum API

' 数据持久化
BPCore --> DataPersistence : 存储社交数据
bbPressCore --> DataPersistence : 存储帖子数据

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/469c824143493e8469e38f9f6e520330.svg)

1. **顶层模块（社区交流模块）**
+ CommunityIntegrationService：作为粘合层，负责将业务逻辑注入 BuddyPress/bbPress 标准流程。
2. **核心业务处理组件**
+ 个人资料扩展：负责在用户页渲染观演数据。
+ 动态流管理：负责动态的生成、分发与筛选。
+ 小组管理：负责圈子的生命周期管理。
+ 论坛管理：负责长内容的发布与版务管理。
3. **外部模块**
+ BuddyPress/bbPress Core：提供底层的社交与论坛能力支持。
+ 观演记录模块：提供内容源数据。

### 类设计
#### 主类：Musicalbum_Community
1. **类结构**

```php
final class Musicalbum_Community {
    // 初始化
    public static function init()
    
    // 钩子注册
    public static function register_hooks()
    
    // 动态流相关
    public static function record_activity($args)
    public static function format_viewing_activity($action, $activity)
    
    // 用户资料相关
    public static function setup_profile_nav()
    public static function render_profile_viewing_content()
    
    // 论坛集成
    public static function sync_topic_to_activity($topic_id)
    
    // REST API 扩展
    public static function register_rest_routes()
    public static function rest_get_user_activities($request)
}
```

2. **核心方法说明**

**init()**

+ **功能**：加载社区模块依赖，检查 BuddyPress/bbPress 是否激活。
+ **职责**：
    - 加载文本域
    - 引入辅助函数库
    - 实例化子组件

**record_activity($args)**

+ **功能**：通用动态记录接口，用于将系统内行为（如新增观演）转换为社交动态。
+ **参数**：`$args` - 包含 `user_id`, `component`, `type`, `item_id` 等字段的数组。
+ **流程**：
    1. 校验数据完整性
    2. 调用 `bp_activity_add()` 写入数据库
    3. 触发通知钩子

**render_profile_viewing_content()**

+ **功能**：在用户资料页“观演记录”标签下渲染内容。
+ **流程**：
    1. 获取当前显示的用户 ID (`bp_displayed_user_id()`)
    2. 调用 `Viewing_Records::get_user_records()` 获取数据
    3. 引入前端模板 `templates/profile-viewing-loop.php` 进行渲染

### 类关联协作
#### 类关系概览
社区模块通过适配器模式（Adapter Pattern）将 WordPress 原生插件（BuddyPress/bbPress）的能力封装为业务所需的社交功能，同时与观演记录模块保持松耦合。

#### 核心类关系图
1. **关系概览**

+ `Musicalbum_Community` 作为主控制器，协调 BP 和 bbPress。
+ `BP_Activity_Activity` 是 BuddyPress 的核心动态类。
+ `Viewing_Records` 提供内容数据支撑。

2. **类图**

<!-- 这是一个文本绘图，源码为：@startuml 社区交流模块类图
!theme plain
skinparam classAttributeIconSize 0
skinparam linetype ortho

package "业务逻辑层" {
  class Musicalbum_Community <<PHP>> {
    +init() : void
    +register_hooks() : void
    +record_viewing_activity(post_id) : bool
    +setup_profile_nav() : void
    --
    -format_activity_string(record) : string
  }
}

package "外部依赖层" {
  class BP_Activity_Activity <<BuddyPress>> {
    +save() : int
    +get() : array
  }
  
  class Viewing_Records <<Module>> {
    +get_record(id) : object
  }
}

package "前端交互层" {
  class CommunityUI <<JS>> {
    +initActivityStream() : void
    +handleLike(activity_id) : void
    +postComment(content, activity_id) : void
  }
}

' 关系定义
Musicalbum_Community ..> BP_Activity_Activity : "调用\n(Wrapper)"
Musicalbum_Community ..> Viewing_Records : "获取数据\n(Data Source)"
CommunityUI ..> Musicalbum_Community : "AJAX请求\n(Interaction)"

note right of Musicalbum_Community
  社区业务门面
  封装底层社交API
  实现业务逻辑定制
end note

@enduml -->
![](https://cdn.nlark.com/yuque/__puml/013b0c800b749d2110c71a391583a48e.svg)

#### 详细类关系说明
1. **Musicalbum_Community 与 BP_Activity_Activity 的关系**

**关系类型**：依赖/包装（Dependency/Wrapper）

**关系描述**：
    - `Musicalbum_Community` 不直接操作数据库，而是通过调用 BuddyPress 提供的 `BP_Activity_Activity` 类方法或全局函数 `bp_activity_add` 来管理动态。
    - 这种设计确保了与 BuddyPress 生态的兼容性（如积分插件、通知插件能正常工作）。

**协作方式**：
    - **创建动态**：当用户发布观演记录时，`Musicalbum_Community` 捕获钩子，构造参数数组，调用 `bp_activity_add()`。
    - **读取动态**：前端请求动态流时，调用 `bp_activity_get()` 获取经过过滤的动态列表。

2. **Musicalbum_Community 与 Viewing_Records 的关系**

**关系类型**：关联关系（Association）

**关系描述**：
    - 社区动态的内容主体往往是观演记录。
    - 社区模块需要读取观演记录的元数据（海报、评分、剧评）来丰富动态展示。

**协作方式**：
    - **内容引用**：在生成动态 HTML 时，`Musicalbum_Community` 调用 `Viewing_Records::get_record($item_id)` 获取详细信息，渲染富媒体卡片（Rich Media Card）。
## <font style="color:rgb(38,38,38);">智能推荐模块</font>
### 模块概述
#### 模块简介
智能推荐模块是乐影集（Musicalbum）系统的核心智能化功能模块之一，负责基于用户的历史观演记录，为用户生成个性化的音乐剧推荐列表。该模块作为独立的 WordPress 插件实现，运行于 WordPress 插件体系之内，通过读取观演记录管理模块中已存在的数据，结合剧目元信息（如剧目类型、演员、标签等），采用规则驱动的推荐策略生成推荐结果，并通过短码在前端页面中进行展示。

该模块不引入复杂的机器学习模型，而是遵循“可解释、可控、可扩展”的设计原则，采用基于用户行为统计与内容相似度的推荐逻辑，保证推荐结果与用户观演偏好之间的明确对应关系，适用于当前乐影集系统的数据规模与应用场景。

#### 技术架构
● 后端框架：WordPress Plugin API  
● 数据来源：观演记录（Custom Post Type：viewing_record）  
● 剧目数据：音乐剧（Custom Post Type：musical）  
● 推荐策略：规则驱动 + 内容相似度匹配  
● 前端技术：PHP 模板渲染 + 轻量 JavaScript 交互  
● API协议：WordPress REST API（只读接口）  
● 数据存储：WordPress 数据库（wp_posts + wp_postmeta + taxonomy 关系表）  

#### 模块职责
1. 汇总并分析当前登录用户的观演记录数据
2. 提取用户观演偏好特征（剧目类型、演员、标签等）
3. 基于规则生成个性化推荐剧目列表
4. 提供推荐结果的 REST API 接口
5. 通过短码在前端页面中展示推荐内容
6. 保证推荐结果与用户身份绑定，避免跨用户数据泄露

### 核心功能设计
####  推荐生成功能
**功能描述：**  
系统在用户访问推荐页面或调用推荐接口时，根据当前用户的历史观演记录动态生成推荐剧目列表。推荐逻辑完全基于已有内容数据，不依赖外部服务。

**业务流程：**  
ⅰ. 获取当前登录用户 ID  
ⅱ. 查询该用户的所有观演记录（viewing_record）  
ⅲ. 从观演记录中提取关键偏好特征

● 剧目类型（taxonomy）  
● 相关演员（taxonomy / meta）  
● 已观演剧目 ID（用于去重）

ⅳ. 基于偏好特征查询候选音乐剧（musical）  
ⅴ. 排除用户已观演的剧目  
ⅵ. 按匹配度与规则权重排序  
ⅶ. 返回最终推荐列表

**推荐规则说明：**

● 剧目类型匹配  
优先推荐与用户历史观演记录中出现频率最高的剧目类型一致的音乐剧。

● 演员关联匹配  
若用户多次观演包含同一演员的作品，则推荐该演员参与的其他音乐剧。

● 去重规则  
所有推荐结果必须排除用户已存在观演记录的剧目，避免重复推荐。

● 数量控制  
推荐结果数量由插件内部参数控制，默认返回固定数量的推荐剧目，用于前端展示。

####  视图展示功能
**功能描述：**  
在推荐页面中以列表形式向用户展示系统生成的个性化推荐音乐剧。

**展示内容：**  
■ 剧目名称  
■ 剧目类型  
■ 推荐理由（基于匹配规则生成的简要说明）  
■ 剧目详细信息

**交互逻辑：**  
■ 页面加载时自动生成推荐结果  
■ 无需用户手动触发推荐  
■ 支持用户反馈推荐项（不感兴趣）

### 功能细化视图
<!-- 这是一个文本绘图，源码为：@startuml 智能推荐模块功能细化图

skinparam componentStyle rectangle
skinparam rectangle {
    BackgroundColor<<component>> #E1F5FE
    BackgroundColor<<external>> #FFF9C4
    BorderColor #01579B
    BorderThickness 2
}

title 智能推荐模块功能细化图

package "智能推荐模块" {
    component RecommendationService <<component>>
}

package "核心业务处理组件" {
    component "用户偏好分析" <<component>> as PreferenceAnalysis
    component "候选剧目生成" <<component>> as CandidateGeneration
    component "推荐规则计算" <<component>> as RecommendationRule
    component "结果过滤与排序" <<component>> as ResultFilter
}

package "推荐处理组件" {
    component "观演记录分析器" <<component>> as ViewingAnalyzer
    component "偏好特征提取器" <<component>> as PreferenceExtractor
    component "推荐生成器" <<component>> as RecommendationBuilder
}

component "数据访问组件" <<component>> as DataAccess

cloud "外部模块" {
    component "<<外部>>\n观演记录管理模块" <<external>> as ViewingModule
    component "<<外部>>\n音乐剧内容管理模块" <<external>> as MusicalModule
}

' RecommendationService 到核心业务处理组件的交互
RecommendationService ..> PreferenceAnalysis : 触发偏好分析
RecommendationService ..> CandidateGeneration : 请求候选剧目
RecommendationService ..> RecommendationRule : 应用推荐规则
RecommendationService ..> ResultFilter : 排序/去重/截断结果

' 核心业务处理组件到推荐处理组件的交互
PreferenceAnalysis --> ViewingAnalyzer : 分析观演记录
PreferenceAnalysis --> PreferenceExtractor : 提取偏好特征
CandidateGeneration --> RecommendationBuilder : 构建推荐集合
RecommendationRule --> RecommendationBuilder : 计算推荐权重
ResultFilter --> RecommendationBuilder : 输出最终结果

' 推荐处理组件到数据访问组件的交互
ViewingAnalyzer ..> DataAccess : 查询观演记录
RecommendationBuilder ..> DataAccess : 查询音乐剧数据

' 数据访问组件与外部模块的交互
DataAccess ..> ViewingModule : 读取 viewing_record 数据
DataAccess ..> MusicalModule : 读取 musical 数据

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/7504e47587d478f5ab1ea26fd928c0ce.svg)

**1. 顶层模块（智能推荐模块）**

● RecommendationService：推荐服务统一入口，负责组织推荐流程

**2. 核心业务处理组件**

● 用户偏好分析组件：统计观演记录中的偏好特征  
● 推荐生成组件：根据规则生成推荐结果  
● 结果过滤组件：排除已观演剧目并控制数量

**3. 数据访问组件**

● 观演记录查询器：读取 viewing_record 数据  
● 剧目查询器：读取 musical 数据

**4. 外部模块**

● 观演记录管理模块  
● 音乐剧内容管理模块

### 类设计
####  类概述
 Musicalbum_Recommendation 是智能推荐模块的核心类，采用单例模式（final class），所有方法均为静态方法。该类负责插件初始化、推荐逻辑执行、REST API 路由注册以及前端推荐结果输出。  

####  类结构  
```php
final class Musicalbum_Recommendation {

    // 初始化
    public static function init()

    // 短码相关
    public static function register_shortcodes()
    public static function shortcode_recommendations($atts)

    // REST API
    public static function register_rest_routes()
    public static function rest_get_recommendations($request)

    // 推荐核心逻辑
    private static function get_user_viewing_records($user_id)
    private static function extract_user_preferences($viewings)
    private static function query_candidate_musicals($preferences)
    private static function filter_watched_musicals($musicals, $viewings)
    private static function sort_recommendations($musicals, $preferences)

}

```

####  核心方法说明  
** init()  **

● 功能：初始化智能推荐插件  
● 调用时机：插件加载时  
● 职责：  
○ 注册短码  
○ 注册 REST API 路由  

** rest_get_recommendations($request)  **

● 功能：获取当前用户的推荐剧目列表  
● 参数：$request - WP_REST_Request 对象  
● 返回值：WP_REST_Response，包含推荐音乐剧数组  
● 权限控制：仅允许已登录用户访问  

** get_user_viewing_records($user_id)  **

● 功能：查询用户的观演记录  
● 数据来源：viewing_record 自定义文章类型  

** extract_user_preferences($viewings)  **

● 功能：从观演记录中提取用户偏好特征  
● 输出：剧目类型、演员等结构化偏好数据  

** query_candidate_musicals($preferences)  **

 ● 功能：基于偏好查询候选音乐剧  
● 查询方式：WP_Query + taxonomy / meta 条件  

### 类关联协作
#### 类关系概览
 智能推荐模块与系统中已有模块保持**单向依赖关系**，不反向修改任何已有数据，确保模块解耦。  

#### 核心类关系图
1. 关系概览

+ 推荐计算由 `Musicalbum_Recommendation_Engine` 统一负责
+ 用户行为数据由 `Musicalbum_User_Behavior` 提供
+ `Musicalbum_Shortcodes` 作为前后端衔接点
+ 前端 JS 不直接参与推荐算法，仅负责展示

 2. 类图

<!-- 这是一个文本绘图，源码为：@startuml 智能推荐模块类图
!theme plain
skinparam classAttributeIconSize 0
skinparam linetype ortho

package "后端服务层" {
  class Musicalbum_Recommendation <<PHP>> {
    +init() : void
    +register_shortcodes() : void
    +register_rest_routes() : void
    +shortcode_recommendations() : string
    +rest_get_recommendations(req) : WP_REST_Response
    --
    -get_user_viewing_records(user_id) : array
    -extract_user_preferences(viewings) : array
    -query_candidate_musicals(preferences) : array
    -filter_watched_musicals(musicals, viewings) : array
    -sort_recommendations(musicals, preferences) : array
  }
}

package "数据模型层" {
  class ViewingRecord {
    +id : int
    +user_id : int
    +musical_id : int
    +category : string
    +cast : string
    +view_date : string
  }

  class Musical {
    +id : int
    +title : string
    +categories : array
    +cast : string
    +tags : array
    +cover_url : string
    +url : string
  }

  class RecommendationResult {
    +musical_id : int
    +score : float
    +reason : string
  }
}

package "前端交互层" {
  class RecommendationView <<JS>> {
    +init() : void
    +loadRecommendations() : void
    +renderList(results) : void
    +renderReason(result) : string
  }

  class RESTClient <<JS>> {
    +recommendations : string
    +nonce : string
  }
}

' 关系定义（使用标准UML关系类型）
RecommendationView ..> RESTClient : "依赖\n(Dependency)"
RESTClient ..> Musicalbum_Recommendation : "依赖\n(Dependency)"

Musicalbum_Recommendation "1" o-- "*" ViewingRecord : "聚合\n(Aggregation)"
Musicalbum_Recommendation "1" o-- "*" Musical : "聚合\n(Aggregation)"
Musicalbum_Recommendation "1" o-- "*" RecommendationResult : "聚合\n(Aggregation)"

RecommendationView ..> RecommendationResult : "依赖\n(Dependency)"
Musicalbum_Recommendation ..> ViewingRecord : "依赖\n(Dependency)"
Musicalbum_Recommendation ..> Musical : "依赖\n(Dependency)"

' 注释
note right of Musicalbum_Recommendation
  智能推荐模块后端核心类
  负责用户偏好分析、
  推荐规则计算与
  REST API输出
end note

note right of RecommendationView
  推荐结果前端展示类
  负责请求推荐接口
  并渲染推荐列表
end note

note right of RESTClient
  REST API客户端
  封装推荐接口调用
  与Nonce认证
end note

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/8fcf1c36e51db1b8d29e1f28194a9e46.svg)

####  详细类关系说明
**1. Musicalbum_Shortcodes 与 Musicalbum_Recommendation_Engine 的关系**

**关系类型：依赖关系（Dependency）**

**关系描述：**

+ `Musicalbum_Shortcodes` 不包含任何推荐算法
+ 推荐结果完全依赖 `Musicalbum_Recommendation_Engine` 提供
+ Shortcode 仅负责参数解析、权限判断与结果展示

**协作方式：**

+ `render_recommendations()` 调用 `get_recommendations()`
+ 传入当前用户 ID 与 limit 参数
+ 接收 WP_Post 数组并生成前端 HTML
+ 当返回结果为空时，触发兜底逻辑（显示最新内容）

**2. Musicalbum_Recommendation_Engine 与 Musicalbum_User_Behavior 的关系**

**关系类型：依赖关系（Dependency）**

**关系描述：**

+ 推荐引擎本身不直接操作数据库行为记录
+ 用户历史行为由 `Musicalbum_User_Behavior` 统一管理
+ 推荐引擎只关心“行为结果”，不关心“行为如何产生”

**协作方式：**

+ `get_recommendations()` 内部调用  
`Musicalbum_User_Behavior::get_behavior()`
+ 获取用户浏览、交互等行为数据
+ 将行为数据转换为兴趣权重
+ 行为结构变化不会影响推荐引擎整体架构

**3. Musicalbum_Shortcodes 与 RecommendationUI 的关系**

**关系类型：依赖关系（Dependency）**

**关系描述：**

+ 前端 JS 不直接访问推荐算法
+ 推荐结果通过 PHP 短码渲染后注入页面
+ JS 仅对 DOM 进行样式与交互增强

**协作方式：**

+ 页面加载时 Shortcode 输出 HTML 结构
+ `RecommendationUI.init()` 绑定样式与交互
+ 不涉及任何数据请求或状态计算
+ 保证推荐功能在 JS 失效时仍可正常展示

## <font style="color:rgb(38,38,38);">剧院周边服务模块</font>
### 模块概述
#### 模块简介
剧院周边服务模块是乐影集（Musicalbum）系统的地理信息核心，基于 WP Go Maps (Pro) 插件与高德地图 API 深度集成开发。该模块旨在为音乐剧观众提供“观演前-观演后”的地理位置服务闭环，支持剧院位置可视化、观演足迹地图展示、周边生活服务（餐饮/交通）查询以及实时路线导航。通过将抽象的观演记录与具象的地理坐标关联，模块为用户构建了一张专属的“音乐剧地图”，显著提升了线下观演的便利性与趣味性。

#### 技术架构
+ **核心引擎**：WP Go Maps (Pro)
+ **地图数据源**：高德地图 API (Web服务 + JS API)
+ **前端技术**：OpenLayers 6, jQuery, Geolocation API
+ **后端技术**：WordPress REST API, MySQL Spatial Data Types
+ **数据存储**：`wp_wpgmza` (自定义地图表), `wp_wpgmza_maps` (地图配置表)

#### 模块职责
1. 剧院地理信息的标准化存储与管理
2. 观演记录的自动地理编码（地址转坐标）
3. 观演足迹地图的可视化渲染与交互
4. 基于用户当前位置的“附近剧院”搜索
5. 剧院周边生活服务（POI）查询
6. 实时路径规划与导航跳转

### 核心功能设计
#### 剧院地图管理功能
+ **自动地理编码同步**

**功能描述**：当用户在观演记录中填写“剧院”名称时，系统自动后台调用高德 API 获取经纬度，并同步至地图数据库。

**业务流程**：
        1. 监听 `save_post_viewing_record` 钩子
        2. 提取 `theater` 字段值
        3. 检查 `wp_wpgmza` 表中是否已存在该剧院标记
        4. 若不存在，调用高德地理编码 API 获取坐标
        5. 写入地图标记表，并关联观演记录 ID

+ **足迹地图展示**

**功能描述**：在前端以交互式地图形式展示用户所有打卡过的剧院，支持聚类显示与弹窗详情。

**展示内容**：
        * 剧院标记（Marker）：不同颜色的图标区分“常去”与“新去”
        * 信息窗口（Info Window）：展示剧院名、观演次数、最近观演记录链接

#### 周边服务查询功能
+ **附近剧院搜索**

**功能描述**：基于 HTML5 Geolocation 获取用户当前位置，实时搜索并按距离排序显示附近 5km 内的剧院。

**业务流程**：
        1. 前端请求浏览器地理位置权限
        2. 获取经纬度 (Lat, Lng)
        3. 调用后端 `ajax_search_nearby_theaters` 接口
        4. 后端通过高德周边搜索 API 检索数据
        5. 前端渲染剧院列表与地图标记

+ **生活服务检索**

**功能描述**：以特定剧院为中心，搜索周边的餐饮、停车场、地铁站等设施。

**交互逻辑**：
        * **快捷标签**：提供“美食”、“交通”、“住宿”快捷按钮
        * **关键词搜索**：支持自定义输入（如“咖啡馆”）
        * **结果联动**：点击列表项，地图自动聚焦并显示详情

#### 导航服务功能
+ **路径规划**

**功能描述**：提供从“我的位置”到“目标剧院”的路线概览，并支持一键跳转第三方地图 APP。

**业务流程**：
        1. 用户在详情页点击“去这里”
        2. 系统判断当前设备类型（PC/Mobile）
        3. PC端：打开高德地图 Web 版导航页面
        4. 移动端：唤起高德/百度/Apple地图 APP 进行导航

### 功能细化视图
<!-- 这是一个文本绘图，源码为：@startuml 剧院周边服务模块功能细化图

skinparam componentStyle rectangle
skinparam rectangle {
    BackgroundColor<<component>> #E1F5FE
    BackgroundColor<<external>> #FFF9C4
    BorderColor #01579B
    BorderThickness 2
}

title 剧院周边服务模块功能细化图

package "剧院周边服务模块" {
    component TheaterMapService <<component>>
}

package "核心业务处理组件" {
    component "地理编码同步器" <<component>> as Geocoder
    component "地图渲染器" <<component>> as MapRenderer
    component "周边搜索器" <<component>> as NearbySearcher
    component "导航服务" <<component>> as NavigationService
}

package "数据处理组件" {
    component "标记管理器" <<component>> as MarkerManager
    component "API 代理" <<component>> as APIProxy
}

component "数据持久化组件" <<component>> as DataPersistence

cloud "外部模块" {
    component "<<外部>>\nWP Go Maps Core" <<external>> as WPGMCore
    component "<<外部>>\n高德地图 API" <<external>> as AmapAPI
    component "<<外部>>\n观演记录模块" <<external>> as ViewingModule
}

' TheaterMapService 到核心业务处理组件的交互
TheaterMapService ..> Geocoder : 监听观演记录保存
TheaterMapService ..> MapRenderer : 渲染足迹地图
TheaterMapService ..> NearbySearcher : 搜索周边
TheaterMapService ..> NavigationService : 规划路线

' 核心业务处理组件到数据处理组件的交互
Geocoder --> MarkerManager : 创建/更新标记
MapRenderer --> MarkerManager : 读取标记数据
NearbySearcher --> APIProxy : 请求外部数据
NavigationService --> APIProxy : 获取路线信息

' 数据处理组件到外部模块的交互
APIProxy ..> AmapAPI : HTTP 请求
MarkerManager ..> WPGMCore : 调用插件 API
Geocoder ..> ViewingModule : 读取剧院名称

' 数据持久化
WPGMCore --> DataPersistence : 存储地图数据

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/8275955375d82054238e55e82811d739.svg)

1. **顶层模块（剧院周边服务模块）**
+ TheaterMapService：作为总控制器，负责协调地图插件与业务逻辑。
2. **核心业务处理组件**
+ 地理编码同步器：负责将“剧院文本”转化为“经纬度坐标”。
+ 地图渲染器：负责前端地图的初始化、图层叠加与交互事件。
+ 周边搜索器：负责处理“附近”相关的查询请求。
+ 导航服务：负责生成导航链接与跳转逻辑。
3. **外部模块**
+ WP Go Maps Core：提供底层的地图渲染引擎。
+ 高德地图 API：提供地理数据支持。

### 类设计
#### 主类：Musicalbum_Theater_Maps
1. **类结构**

```php
final class Musicalbum_Theater_Maps {
    // 初始化
    public static function init()
    
    // 资源加载
    public static function enqueue_assets()
    
    // 钩子注册
    public static function register_hooks()
    
    // 核心业务
    public static function sync_theater_to_map($post_id)
    public static function ajax_search_nearby_theaters()
    public static function shortcode_theater_map($atts)
    
    // 辅助方法
    private static function geocode_amap($address)
    private static function add_marker_to_db($data)
}
```

2. **核心方法说明**

**init()**

+ **功能**：初始化地图模块，注册 AJAX 接口与短码。
+ **职责**：
    - 注册 `wp_ajax_musicalbum_search_nearby_theaters`
    - 注册 `[musicalbum_theater_map]` 短码
    - 加载前端 JS/CSS 资源

**sync_theater_to_map($post_id)**

+ **功能**：观演记录保存时触发的同步逻辑。
+ **参数**：`$post_id` - 当前保存的文章 ID。
+ **流程**：
    1. 检查文章类型是否为 `viewing_record`
    2. 获取 `theater` 元数据
    3. 查询 `wp_wpgmza` 表查重
    4. 调用 `geocode_amap` 获取坐标
    5. 插入数据到地图表

**ajax_search_nearby_theaters()**

+ **功能**：处理前端发起的周边搜索请求。
+ **参数**：通过 `$_POST` 获取 `lat`, `lng`, `radius`, `keyword`。
+ **流程**：
    1. 校验 Nonce 安全令牌
    2. 构造高德 API 请求 URL (`v3/place/around`)
    3. 发送 HTTP 请求并解析 JSON
    4. 格式化 POI 数据并返回前端

### 类关联协作
#### 类关系概览
剧院周边服务模块通过代理模式（Proxy Pattern）封装了高德 API 的调用，并通过适配器模式（Adapter Pattern）与 WP Go Maps 插件进行交互，确保业务逻辑不直接依赖于具体的地图实现细节。

#### 核心类关系图
1. **关系概览**

+ `Musicalbum_Theater_Maps` 负责业务逻辑编排。
+ `WPGMZA_Marker` 是 WP Go Maps 的标记类。
+ `Amap_Service` (逻辑概念) 负责与高德 API 通信。

2. **类图**

<!-- 这是一个文本绘图，源码为：@startuml 剧院周边服务模块类图
!theme plain
skinparam classAttributeIconSize 0
skinparam linetype ortho

package "业务逻辑层" {
  class Musicalbum_Theater_Maps <<PHP>> {
    +init() : void
    +sync_theater_to_map(post_id) : void
    +ajax_search_nearby_theaters() : void
    --
    -geocode_amap(address) : array
  }
}

package "前端交互层" {
  class MusicalbumMap <<JS>> {
    +switchTab(tabName) : void
    +findNearby() : void
    +searchServices() : void
    +startNavigation() : void
    --
    -performSearch(lat, lng, kw) : void
    -addTempMarkers(pois) : void
  }
}

package "外部依赖层" {
  class WPGMZA_Map <<WP Go Maps>> {
    +addMarker(marker) : void
    +setCenter(latlng) : void
  }
  
  class Amap_API <<Cloud>> {
    +geo() : json
    +place_around() : json
  }
}

' 关系定义
Musicalbum_Theater_Maps ..> Amap_API : "HTTP请求\n(REST)"
Musicalbum_Theater_Maps ..> WPGMZA_Map : "数据库操作\n(DB)"
MusicalbumMap ..> Musicalbum_Theater_Maps : "AJAX调用\n(Search)"
MusicalbumMap ..> WPGMZA_Map : "JS调用\n(Render)"

note right of Musicalbum_Theater_Maps
  服务端核心类
  负责数据同步与
  API 代理转发
end note

note right of MusicalbumMap
  前端核心对象
  封装地图交互逻辑
  管理标记状态
end note

@enduml -->
![](https://cdn.nlark.com/yuque/__puml/1381220a22168393539825b4130009c9.svg)

#### 详细类关系说明
1. **Musicalbum_Theater_Maps 与 Amap_API 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：
    - `Musicalbum_Theater_Maps` 充当了服务器端的 API 代理。
    - 为了避免在前端暴露高德 API Key，所有涉及 Key 的请求（如地理编码、周边搜索）都由后端 PHP 类发起。

**协作方式**：
    - **地理编码**：后端直接 `wp_remote_get` 请求高德 API。
    - **周边搜索**：前端发起 AJAX -> 后端 PHP 代理请求高德 -> 返回结果给前端。

2. **MusicalbumMap (JS) 与 WPGMZA_Map 的关系**

**关系类型**：关联/操作（Association）

**关系描述**：
    - `MusicalbumMap` 是本系统自定义的前端全局对象。
    - `WPGMZA_Map` 是 WP Go Maps 插件提供的地图实例对象。
    - `MusicalbumMap` 需要获取 `WPGMZA_Map` 的实例来控制地图行为（移动中心、添加标记）。

**协作方式**：
    - **实例获取**：通过 `WPGMZA.maps[0]` 获取当前页面地图实例。
    - **标记操作**：调用 `map.addMarker()` 添加搜索结果标记。
    - **视野控制**：调用 `map.setCenter()` 移动视野到用户位置或目标剧院。
## <font style="color:rgb(38,38,38);">用户账户与权限模块</font>
### 模块概述
#### 模块简介
用户账户与权限模块是乐影集（Musicalbum）系统的基石，负责管理用户的全生命周期（注册、登录、资料维护、注销）及细粒度的功能访问控制。该模块基于 WordPress 原生用户系统（User System）构建，通过 Profile Builder 插件实现前端化表单交互，结合自定义角色与能力（Roles & Capabilities）机制，确保用户仅能操作归属于自己的观演数据，同时保障管理员对全站内容的监管权。

#### 技术架构
+ **核心框架**：WordPress User API
+ **扩展插件**：Profile Builder (前端表单), User Role Editor (角色管理)
+ **数据存储**：`wp_users` (核心账户表), `wp_usermeta` (扩展资料表)
+ **认证机制**：Cookie-based Authentication (Web), Nonce Verification (REST API)
+ **安全机制**：密码哈希存储 (phpass), CSRF 防护

#### 模块职责
1. 用户注册、登录、密码重置的前端化流程
2. 用户个人资料（头像、昵称、简介）的管理
3. 基于角色的权限控制（Subscriber vs Administrator）
4. 数据隔离策略实施（用户只能增删改查自己的观演记录）
5. 社交账号绑定与管理（预留接口）

### 核心功能设计
#### 账户管理功能
+ **前端注册与登录**

**功能描述**：提供独立于 WordPress 后台的注册与登录页面，支持自定义字段（如昵称）。

**业务流程**：
        1. 用户访问 `/register` 或 `/login` 页面
        2. Profile Builder 渲染短码表单 `[wppb-register]` / `[wppb-login]`
        3. 提交表单，系统校验数据格式与唯一性
        4. 验证通过后创建用户或建立会话
        5. 自动跳转至“我的主页”或来源页

+ **个人资料维护**

**功能描述**：用户可在前端页面修改密码、邮箱及扩展资料。

**交互逻辑**：
        * **表单展示**：使用 `[wppb-edit-profile]` 短码渲染
        * **头像上传**：集成 WordPress 媒体库或 Gravatar
        * **实时校验**：密码强度检测与邮箱格式验证

#### 权限控制功能
+ **角色定义**

**系统预设角色**：
        * **订阅者 (Subscriber)**：默认注册角色。权限：管理个人资料、管理个人观演记录、参与社区互动。
        * **管理员 (Administrator)**：系统维护者。权限：全站管理、用户管理、内容审核、插件配置。

+ **数据隔离控制**

**功能描述**：在后端 API 层面强制执行“所有权检查”，防止越权访问。

**实现逻辑**：
        1. 拦截所有针对 `viewing_record` 的 CRUD 请求
        2. 获取当前登录用户 ID (`get_current_user_id()`)
        3. 对比目标记录的 `post_author` 字段
        4. 若不一致且非管理员，返回 `403 Forbidden`

### 功能细化视图
<!-- 这是一个文本绘图，源码为：@startuml 用户账户与权限模块功能细化图

skinparam componentStyle rectangle
skinparam rectangle {
    BackgroundColor<<component>> #E1F5FE
    BackgroundColor<<external>> #FFF9C4
    BorderColor #01579B
    BorderThickness 2
}

title 用户账户与权限模块功能细化图

package "用户账户与权限模块" {
    component UserSecurityService <<component>>
}

package "核心业务处理组件" {
    component "账户管理器" <<component>> as AccountManager
    component "认证处理器" <<component>> as AuthProcessor
    component "权限校验器" <<component>> as PermissionValidator
    component "资料扩展器" <<component>> as ProfileExtender
}

package "数据处理组件" {
    component "User Query" <<component>> as UserQuery
    component "Meta Handler" <<component>> as MetaHandler
}

component "数据持久化组件" <<component>> as DataPersistence

cloud "外部模块" {
    component "<<外部>>\nWordPress Core" <<external>> as WPCore
    component "<<外部>>\nProfile Builder" <<external>> as PBPlugin
}

' UserSecurityService 到核心业务处理组件的交互
UserSecurityService ..> AuthProcessor : 处理登录/注册
UserSecurityService ..> AccountManager : 管理账户状态
UserSecurityService ..> PermissionValidator : 检查操作权限
UserSecurityService ..> ProfileExtender : 读写扩展资料

' 核心业务处理组件到数据处理组件的交互
AuthProcessor --> WPCore : 调用 wp_signon
AccountManager --> UserQuery : 查询用户状态
ProfileExtender --> MetaHandler : 存取 usermeta
PermissionValidator --> WPCore : 检查 current_user_can

' 数据处理组件到外部模块的交互
UserQuery ..> DataPersistence : SQL 查询
MetaHandler ..> DataPersistence : SQL 查询

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/d4f6c589025e19741c88145237885917.svg)

1. **顶层模块（用户账户与权限模块）**
+ UserSecurityService：作为安全网关，拦截并分发所有与用户相关的请求。
2. **核心业务处理组件**
+ 认证处理器：处理 Session、Cookie 与 Nonce 验证。
+ 权限校验器：执行 Capability 检查与数据所有权比对。
+ 账户管理器：处理注册、密码重置、封号等生命周期事件。
3. **外部模块**
+ WordPress Core：提供底层的 User API 和 Pluggable Functions。
+ Profile Builder：提供前端表单渲染能力。

### 类设计
#### 主类：Musicalbum_Auth_Manager
1. **类结构**

```php
final class Musicalbum_Auth_Manager {
    // 初始化
    public static function init()
    
    // 权限钩子
    public static function register_capabilities()
    public static function restrict_media_library($query)
    
    // REST API 权限回调
    public static function check_viewing_permission($request)
    public static function check_user_update_permission($request)
    
    // 资料扩展
    public static function register_user_fields()
    public static function save_user_fields($user_id)
}
```

2. **核心方法说明**

**init()**

+ **功能**：注册钩子与过滤器。
+ **职责**：
    - 绑定 `pre_get_posts` 以限制媒体库访问（用户只能看到自己上传的图片）
    - 注册自定义用户元数据字段

**check_viewing_permission($request)**

+ **功能**：通用的 REST API 权限检查回调函数。
+ **参数**：`$request` - WP_REST_Request 对象。
+ **逻辑**：
    1. 检查 `is_user_logged_in()`
    2. 获取目标资源 ID（如观演记录 ID）
    3. 获取资源作者 ID (`get_post_field('post_author', $id)`)
    4. 若 `current_user_id != author_id` 且无 `manage_options` 权限，返回 false

**restrict_media_library($query)**

+ **功能**：防止用户在媒体库中看到他人的图片（如票面隐私）。
+ **逻辑**：
    - 若当前用户非管理员且在后台媒体库查询
    - 强制添加 `author` 参数为当前用户 ID

### 类关联协作
#### 类关系概览
本模块通过 **装饰器模式（Decorator Pattern）** 增强了 WordPress 原生的用户对象，同时作为 **切面（Aspect）** 贯穿于其他所有模块的业务流程中（如观演记录的 CRUD 操作前置检查）。

#### 核心类关系图
1. **关系概览**

+ `Musicalbum_Auth_Manager` 是权限控制中心。
+ `WP_User` 是 WordPress 核心用户对象。
+ `Viewing_Records` 等业务类依赖 `Musicalbum_Auth_Manager` 进行权限判定。

2. **类图**

<!-- 这是一个文本绘图，源码为：@startuml 用户账户与权限模块类图
!theme plain
skinparam classAttributeIconSize 0
skinparam linetype ortho

package "安全控制层" {
  class Musicalbum_Auth_Manager <<PHP>> {
    +check_ownership(post_id) : bool
    +restrict_media_access(query) : void
    +register_custom_roles() : void
  }
}

package "业务逻辑层" {
  class Viewing_Records <<Module>> {
    +create_record()
    +update_record()
  }
}

package "核心框架层" {
  class WP_User <<WordPress>> {
    +ID : int
    +roles : array
    +caps : array
    +has_cap(cap) : bool
  }
  
  class WP_REST_Request <<WordPress>> {
    +get_param(key)
  }
}

' 关系定义
Viewing_Records ..> Musicalbum_Auth_Manager : "调用\n(Permission Check)"
Musicalbum_Auth_Manager ..> WP_User : "获取状态\n(Current User)"
Musicalbum_Auth_Manager ..> WP_REST_Request : "解析参数\n(Request Context)"

note right of Musicalbum_Auth_Manager
  安全守门员
  提供统一的权限校验方法
  防止越权访问
end note

@enduml -->
![](https://cdn.nlark.com/yuque/__puml/5717b4c6530691516f456c68e0d4f3b5.svg)

#### 详细类关系说明
1. **Viewing_Records 与 Musicalbum_Auth_Manager 的关系**

**关系类型**：依赖关系（Dependency）

**关系描述**：
    - `Viewing_Records` 在执行 `update` 或 `delete` 操作前，必须调用 `Musicalbum_Auth_Manager` 的校验方法。
    - 这种设计将权限逻辑从业务逻辑中剥离，避免代码重复。

**协作方式**：
    - **前置检查**：在 REST API 的 `permission_callback` 中调用 `Musicalbum_Auth_Manager::check_viewing_permission`。
    - **结果处理**：若返回 `false`，API 直接中断并返回 403 错误，业务逻辑代码不会被执行。

2. **Musicalbum_Auth_Manager 与 WP_User 的关系**

**关系类型**：关联关系（Association）

**关系描述**：
    - 权限管理器需要频繁获取当前用户的上下文信息（ID、角色、能力）。

**协作方式**：
    - **获取用户**：调用 `wp_get_current_user()` 获取 `WP_User` 实例。
    - **能力检查**：调用 `$user->has_cap('manage_options')` 判断是否为管理员。
## 各模块协作关系
<!-- 这是一个文本绘图，源码为：@startuml
!theme plain
allowmixing
skinparam linetype ortho
skinparam roundcorner 10
title Musicalbum 模块协作关系

top to bottom direction

component "观演记录管理模块" as Module_Viewing
component "数据统计与可视化模块" as Module_Statistics
component "社区交流模块" as Module_Community
component "智能推荐模块" as Module_Recommendation
component "剧院周边服务模块" as Module_Theater
component "用户账户与权限模块" as Module_User

cloud "外部服务" as External {
  cloud "OCR服务"
  cloud "地图服务"
  cloud "其他API"
}

database "WordPress数据库" as DB

' 核心数据流
Module_Viewing --> Module_Statistics : 提供数据
Module_Viewing --> Module_Recommendation : 提供观演记录

' 用户权限
Module_User --> Module_Viewing : 权限控制
Module_User --> Module_Community : 权限控制
Module_User --> Module_Statistics : 权限控制

' 社区模块协作
Module_Community --> Module_Viewing : 读取观演记录
Module_Community --> Module_Recommendation : 获取推荐

' 推荐模块协作
Module_Recommendation --> Module_Viewing : 基于观演记录
Module_Recommendation --> Module_Community : 基于社区数据

' 剧院周边服务
Module_Theater --> Module_Viewing : 关联观演记录
Module_Theater --> External : 调用外部服务

' 外部服务
Module_Viewing --> External : OCR识别

' 数据库连接（统一从底部）
Module_Viewing --> DB
Module_Statistics --> DB
Module_Community --> DB
Module_Recommendation --> DB
Module_Theater --> DB
Module_User --> DB

note right of Module_Viewing
  核心功能
  - 记录管理
  - OCR识别
  - CSV导入
end note

note right of Module_Statistics
  统计功能
  - 数据可视化
  - 图表展示
  - 数据导出
end note

note right of Module_Community
  社区功能
  - 资源分享
  - 知识库
  - 论坛集成
end note

@enduml
 -->
![](https://cdn.nlark.com/yuque/__puml/4cfe4eaf48ed36bbf4133fb10b0bd8d2.svg)

### 权限控制关系
1. **用户账户与权限模块 → 各功能模块**
+ **关系**：用户账户与权限模块控制所有功能模块的访问权限
+ **控制范围**：
    - 观演记录管理模块：控制用户对记录的增删改查权限
    - 数据统计与可视化模块：控制用户查看统计数据的权限
    - 社区交流模块：控制用户参与社区活动的权限
+ **说明**：所有模块在提供功能前，都需要通过用户账户与权限模块进行权限验证

### 社区模块协作
1. **社区交流模块 → 观演记录管理模块**
+ **关系**：社区模块读取观演记录数据
+ **说明**：用户可以在社区中分享观演记录，社区模块需要读取观演记录信息用于展示和分享
2. **社区交流模块 → 智能推荐模块**
+ **关系**：社区模块从推荐模块获取推荐内容
+ **说明**：推荐模块可以基于社区热门内容、用户关注等社区数据，为社区模块提供推荐内容

### 推荐模块协作
1. **智能推荐模块 → 观演记录管理模块**
+ **关系**：推荐模块基于观演记录进行推荐计算
+ **说明**：推荐算法分析用户的观演历史，识别用户偏好，生成个性化推荐
2. **智能推荐模块 → 社区交流模块**
+ **关系**：推荐模块基于社区数据增强推荐效果
+ **说明**：推荐算法结合社区热门内容、用户互动等社区数据，提升推荐准确性和多样性

### 剧院周边服务模块协作
1. **剧院周边服务模块 → 观演记录管理模块**
+ **关系**：剧院服务模块关联观演记录
+ **说明**：剧院周边服务（如地图定位、交通信息等）需要关联到具体的观演记录
2. **剧院周边服务模块 → 外部服务**
+ **关系**：剧院服务模块调用外部服务API
+ **说明**：剧院周边服务需要调用地图服务、位置服务等外部API获取相关信息

### 外部服务协作
1. **观演记录管理模块 → 外部服务（OCR）**
+ **关系**：观演记录模块调用OCR服务进行票面识别
+ **说明**：用户上传票面图片后，系统调用OCR服务识别票面信息，自动填充观演记录表单
2. **剧院周边服务模块 → 外部服务**
+ **关系**：剧院周边服务模块调用地图服务进行位置识别
+ **说明**：在该模块会展示地图以及周边已经注册上传的美食等信息

# 数据库表设计及数据来源
## 数据来源
### 观演记录数据
1. **用户手动录入**
+ **来源**：用户通过前端管理界面或表单手动填写观演信息
+ **录入方式**：
    - 观演记录管理界面（短码：`[viewing_manager]`）
    - 观演录入表单（短码：`[viewing_form]`）
    - WordPress 后台文章编辑界面
+ **数据内容**：剧目标题、类别、剧院、卡司、票价、观演日期时间、票面图片、备注等
2. **OCR自动识别**
+ **来源**：用户上传票面图片，系统通过OCR技术自动识别
+ **识别服务**：百度OCR API 或 阿里云OCR API
+ **识别内容**：从票面图片中提取剧目标题、剧院、卡司、票价、日期等信息
+ **使用场景**：用户上传票面照片后，系统自动填充表单字段，用户确认后保存

**3.CSV批量导入**

+ **来源**：从中国剧网下载CSV格式的剧目数据，批量导入系统
+ **导入方式**：通过后台管理界面或导入工具上传CSV文件
+ **数据格式**：CSV文件包含剧目标题、类别、剧院、卡司、票价、观演日期等字段
+ **使用场景**：批量导入历史观演记录或从外部数据源同步剧目信息
+ **优势**：快速批量录入大量观演记录，提高数据录入效率

### 社区内容数据
1. **资源分享**
+ **来源**：用户上传文件（音频、视频、文档等）
+ **录入方式**：通过资源分享功能上传文件，填写资源描述和分类
+ **存储位置**：`wp_posts` 表（文章类型：`musicalbum_resource`）和 `wp_musicalbum_resources` 自定义表
2. **知识库文章**
+ **来源**：用户或管理员创建的知识文章
+ **录入方式**：通过知识库管理界面创建和编辑文章
+ **存储位置**：`wp_posts` 表（文章类型：`musicalbum_knowledge`）
3. **论坛帖子**
+ **来源**：用户在论坛中发布的主题和回复
+ **录入方式**：通过论坛界面发帖和回复
+ **存储位置**：`wp_posts` 表（文章类型：`forum`、`topic`、`reply`）

### 统计数据
1. **观演数据统计**
+ **来源**：系统实时计算观演记录数据
+ **计算方式**：从 `wp_posts` 和 `wp_postmeta` 表中读取观演记录，进行聚合统计
+ **统计内容**：
    - 剧目类别分布
    - 演员出场频率
    - 票价区间分布
    - 剧院分布
    - 总记录数、本月观演次数、总花费等
+ **特点**：统计数据不存储在数据库中，每次访问时实时计算
2. **用户活动统计**
+ **来源**：基于用户发布的观演记录、资源、文章等数据统计
+ **计算方式**：从相关数据表中聚合用户活动数据

## 数据库表
### E-R图
<!-- 这是一个文本绘图，源码为：@startuml Musicalbum数据库ER图
!define PK_COLOR #FFE6E6
!define FK_COLOR #E6F3FF

skinparam linetype ortho
skinparam roundcorner 10
skinparam shadowing false

title Musicalbum 项目数据库表关系图 (ER Diagram)

' 用户相关表
entity "wp_users" as users PK_COLOR {
  * **ID** : bigint(20) <<PK>>
  --
  * user_login : varchar(60)
  * user_pass : varchar(255)
  * user_nicename : varchar(50)
  * user_email : varchar(100)
  * user_url : varchar(100)
  * user_registered : datetime
  * user_activation_key : varchar(255)
  * user_status : int(11)
  * display_name : varchar(250)
}

entity "wp_usermeta" as usermeta FK_COLOR {
  * **umeta_id** : bigint(20) <<PK>>
  --
  * user_id : bigint(20) <<FK>>
  * meta_key : varchar(255)
  * meta_value : longtext
}

' 文章相关表
entity "wp_posts" as posts PK_COLOR {
  * **ID** : bigint(20) <<PK>>
  --
  * post_author : bigint(20) <<FK>>
  * post_date : datetime
  * post_date_gmt : datetime
  * post_content : longtext
  * post_title : text
  * post_excerpt : text
  * post_status : varchar(20)
  * comment_status : varchar(20)
  * ping_status : varchar(20)
  * post_password : varchar(255)
  * post_name : varchar(200)
  * to_ping : text
  * pinged : text
  * post_modified : datetime
  * post_modified_gmt : datetime
  * post_content_filtered : longtext
  * post_parent : bigint(20)
  * guid : varchar(255)
  * menu_order : int(11)
  * **post_type** : varchar(20)
  * post_mime_type : varchar(100)
  * comment_count : bigint(20)
}

entity "wp_postmeta" as postmeta FK_COLOR {
  * **meta_id** : bigint(20) <<PK>>
  --
  * post_id : bigint(20) <<FK>>
  * meta_key : varchar(255)
  * meta_value : longtext
}

' 评论相关表
entity "wp_comments" as comments PK_COLOR {
  * **comment_ID** : bigint(20) <<PK>>
  --
  * comment_post_ID : bigint(20) <<FK>>
  * comment_author : tinytext
  * comment_author_email : varchar(100)
  * comment_author_url : varchar(200)
  * comment_author_IP : varchar(100)
  * comment_date : datetime
  * comment_date_gmt : datetime
  * comment_content : text
  * comment_karma : int(11)
  * comment_approved : varchar(20)
  * comment_agent : varchar(255)
  * comment_type : varchar(20)
  * comment_parent : bigint(20)
  * user_id : bigint(20) <<FK>>
}

entity "wp_commentmeta" as commentmeta FK_COLOR {
  * **meta_id** : bigint(20) <<PK>>
  --
  * comment_id : bigint(20) <<FK>>
  * meta_key : varchar(255)
  * meta_value : longtext
}

' 分类相关表
entity "wp_terms" as terms PK_COLOR {
  * **term_id** : bigint(20) <<PK>>
  --
  * name : varchar(200)
  * slug : varchar(200)
  * term_group : bigint(10)
}

entity "wp_term_taxonomy" as term_taxonomy PK_COLOR {
  * **term_taxonomy_id** : bigint(20) <<PK>>
  --
  * term_id : bigint(20) <<FK>>
  * taxonomy : varchar(32)
  * description : longtext
  * parent : bigint(20)
  * count : bigint(20)
}

entity "wp_term_relationships" as term_relationships FK_COLOR {
  * object_id : bigint(20) <<FK>>
  * term_taxonomy_id : bigint(20) <<FK>>
  * term_order : int(11)
}

entity "wp_termmeta" as termmeta FK_COLOR {
  * **meta_id** : bigint(20) <<PK>>
  --
  * term_id : bigint(20) <<FK>>
  * meta_key : varchar(255)
  * meta_value : longtext
}

' 选项表
entity "wp_options" as options PK_COLOR {
  * **option_id** : bigint(20) <<PK>>
  --
  * option_name : varchar(191)
  * option_value : longtext
  * autoload : varchar(20)
}

' 自定义表
entity "wp_musicalbum_resources" as resources PK_COLOR {
  * **id** : bigint(20) <<PK>>
  --
  * post_id : bigint(20) <<FK>>
  * user_id : bigint(20) <<FK>>
  * resource_type : varchar(50)
  * resource_url : text
  * created_at : datetime
}

' 关系定义
users ||--o{ posts : "创建\n(post_author)"
users ||--o{ usermeta : "拥有\n(user_id)"
users ||--o{ comments : "评论\n(user_id)"
users ||--o{ resources : "创建\n(user_id)"

posts ||--o{ postmeta : "拥有\n(post_id)"
posts ||--o{ comments : "被评论\n(comment_post_ID)"
posts ||--o{ term_relationships : "关联\n(object_id)"
posts ||--o{ resources : "关联\n(post_id)"

comments ||--o{ commentmeta : "拥有\n(comment_id)"

terms ||--|| term_taxonomy : "定义\n(term_id)"
terms ||--o{ termmeta : "拥有\n(term_id)"
term_taxonomy ||--o{ term_relationships : "关联\n(term_taxonomy_id)"

note right of posts
  **观演记录相关post_type:**
  - viewing_record (新)
  - musicalbum_viewing (旧)
  - musicalbum_resource (资源)
  - musicalbum_knowledge (知识库)
end note

note right of postmeta
  **观演记录ACF字段:**
  - category (剧目类别)
  - theater (剧院)
  - cast (卡司)
  - price (票价)
  - view_date (观演日期)
  - view_time_start (开始时间)
  - view_time_end (结束时间)
  - ticket_image (票面图片)
  - notes (备注)
end note

note right of options
  **观演记录配置选项:**
  - viewing_ocr_provider
  - viewing_baidu_api_key
  - viewing_aliyun_api_key
  - viewing_records_migration_done
end note

@enduml

 -->
![](https://cdn.nlark.com/yuque/__puml/45cb07059e155bc3870b74cde26ffb7b.svg)

### 数据库表
1. **wp_posts 表**

存储所有文章、页面和自定义文章类型（包括观演记录、资源、知识库等）。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| ID | bigint(20) | 文章ID（主键，自增） |
| post_author | bigint(20) | 作者用户ID（外键，关联wp_users.ID） |
| post_date | datetime | 文章创建时间（本地时间） |
| post_date_gmt | datetime | 文章创建时间（GMT时间） |
| post_content | longtext | 文章内容 |
| post_title | text | 文章标题 |
| post_excerpt | text | 文章摘要 |
| post_status | varchar(20) | 文章状态 |
| comment_status | varchar(20) | 评论状态 |
| ping_status | varchar(20) | Ping状态 |
| post_password | varchar(255) | 文章密码（如果设置了密码保护） |
| post_name | varchar(200) | 文章别名（slug，用于URL） |
| to_ping | text | 待Ping的URL列表 |
| pinged | text | 已Ping的URL列表 |
| post_modified | datetime | 最后修改时间（本地时间） |
| post_modified_gmt | datetime | 最后修改时间（GMT时间） |
| post_content_filtered | longtext | 过滤后的内容 |
| post_parent | bigint(20) | 父文章ID（用于页面层级） |
| guid | varchar(255) | 全局唯一标识符（URL） |
| menu_order | int(11) | 菜单顺序 |
| post_type | varchar(20) | 文章类型 |
| post_mime_type | varchar(100) | MIME类型（用于附件） |
| comment_count | bigint(20) | 评论数量 |


**重要说明：**

+ `post_type` 字段用于区分不同类型的文章：
    - `viewing_record`：观演记录（新）
    - `musicalbum_viewing`：观演记录（旧，兼容）
    - `musicalbum_resource`：共享资源
    - `musicalbum_knowledge`：知识库文章
    - `forum`, `topic`, `reply`：bbPress论坛相关
    - `post`：普通文章
    - `page`：页面
2. **wp_postmeta 表**

存储文章元数据，包括ACF字段值和其他自定义字段。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| meta_id | bigint(20) | 元数据ID（主键，自增） |
| post_id | bigint(20) | 关联的文章ID（外键，关联wp_posts.ID） |
| meta_key | varchar(255) | 字段名（meta key） |
| meta_value | longtext | 字段值（meta value） |


+ **观演记录常用字段（meta_key）：**
    - `category`：剧目类别（音乐剧、话剧、歌剧等）
    - `theater`：剧院名称
    - `cast`：卡司信息
    - `price`：票价
    - `view_date`：观演日期
    - `view_time_start`：观演开始时间
    - `view_time_end`：观演结束时间
    - `ticket_image`：票面图片ID
    - `notes`：备注信息
+ **ACF特殊字段：**
    - 以下划线开头的字段（如 `_category`）用于存储ACF内部配置信息
3. **wp_users 表**

存储用户基本信息。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| ID | bigint(20) | 用户ID（主键，自增） |
| user_login | varchar(60) | 用户登录名 |
| user_pass | varchar(255) | 用户密码（加密后的哈希值） |
| user_nicename | varchar(50) | 用户昵称（用于URL） |
| user_email | varchar(100) | 用户邮箱 |
| user_url | varchar(100) | 用户网站URL |
| user_registered | datetime | 用户注册时间 |
| user_activation_key | varchar(255) | 激活密钥 |
| user_status | int(11) | 用户状态 |
| display_name | varchar(250) | 显示名称 |


+ **重要说明：**
    - `ID` 字段关联到 `wp_posts.post_author`，用于标识文章作者
    - `user_pass` 存储的是加密后的密码哈希，不能直接查看明文
4. **wp_usermeta 表**

存储用户元数据，包括用户自定义字段和插件扩展信息。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| umeta_id | bigint(20) | 元数据ID（主键，自增） |
| user_id | bigint(20) | 关联的用户ID（外键，关联wp_users.ID） |
| meta_key | varchar(255) | 字段名 |
| meta_value | longtext | 字段值 |


+ **常用字段（meta_key）：**
    - `nickname`：昵称
    - `first_name`：名
    - `last_name`：姓
    - `description`：个人简介
    - BuddyPress和bbPress插件会添加额外的用户元数据
5. **wp_options 表**

存储系统选项和插件配置信息。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| option_id | bigint(20) | 选项ID（主键，自增） |
| option_name | varchar(191) | 选项名称（唯一键） |
| option_value | longtext | 选项值 |
| autoload | varchar(20) | 是否自动加载 |


+ **观演记录模块相关选项（option_name）：**
    - `viewing_ocr_provider`：OCR服务提供商
    - `viewing_baidu_api_key`：百度OCR API密钥
    - `viewing_baidu_secret_key`：百度OCR密钥
    - `viewing_records_migration_done`：数据迁移完成标记
+ **社区模块相关选项（option_name）：**
    - `musicalbum_community_enable_forum`：是否启用论坛集成
    - `musicalbum_community_enable_sharing`：是否启用资源分享
    - `musicalbum_community_enable_knowledge`：是否启用知识库
    - `musicalbum_community_viewing_forum_id`：观演记录论坛ID
    - `musicalbum_viewing_forum_id`：观演记录论坛ID（旧）
    - `musicalbum_viewing_group_id`：观演记录群组ID
+ **背景音乐模块相关选项（option_name）：**
    - `selected_music_id`：选中的音乐ID
    - `preset_music_1_url`：预设音乐1的URL
    - `preset_music_1_name`：预设音乐1的名称
    - `preset_music_2_url`：预设音乐2的URL
    - `preset_music_2_name`：预设音乐2的名称
    - `preset_music_3_url`：预设音乐3的URL
    - `preset_music_3_name`：预设音乐3的名称
    - `background_music_url`：背景音乐URL
+ **重要说明：**
    - `autoload = 'yes'` 的选项会在每次页面加载时自动加载到内存
    - `autoload = 'no'` 的选项需要手动查询才会加载
6. **wp_comments 表**

存储评论数据。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| comment_ID | bigint(20) | 评论ID（主键，自增） |
| comment_post_ID | bigint(20) | 关联的文章ID（外键，关联wp_posts.ID） |
| comment_author | tinytext | 评论作者名称 |
| comment_author_email | varchar(100) | 评论作者邮箱 |
| comment_author_url | varchar(200) | 评论作者网站URL |
| comment_author_IP | varchar(100) | 评论作者IP地址 |
| comment_date | datetime | 评论时间（本地时间） |
| comment_date_gmt | datetime | 评论时间（GMT时间） |
| comment_content | text | 评论内容 |
| comment_karma | int(11) | 评论评分 |
| comment_approved | varchar(20) | 评论审核状态 |
| comment_agent | varchar(255) | 评论者浏览器User-Agent |
| comment_type | varchar(20) | 评论类型 |
| comment_parent | bigint(20) | 父评论ID（用于回复） |
| user_id | bigint(20) | 评论者用户ID（如果已登录，关联wp_users.ID） |


+ **重要说明：**
    - 如果评论者是已登录用户，`user_id` 字段会关联到 `wp_users.ID`
    - 如果评论者是访客，`user_id` 为 0，使用 `comment_author` 等字段存储信息
7. **wp_commentmeta 表**

存储评论元数据。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| meta_id | bigint(20) | 元数据ID（主键，自增） |
| comment_id | bigint(20) | 关联的评论ID（外键，关联wp_comments.comment_ID） |
| meta_key | varchar(255) | 字段名 |
| meta_value | longtext | 字段值 |


+ **常用字段（meta_key）：**
    - 插件可能会添加评论评分、审核标记等元数据
8. **wp_terms 表**

存储分类和标签的术语信息。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| term_id | bigint(20) | 术语ID（主键，自增） |
| name | varchar(200) | 术语名称 |
| slug | varchar(200) | 术语别名（用于URL） |
| term_group | bigint(10) | 术语分组 |


+ **重要说明：**
    - 此表只存储术语本身，不存储分类法信息
    - 分类法信息存储在 `wp_term_taxonomy` 表中
9. **wp_term_taxonomy 表**

存储分类法定义（如分类、标签等）。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| term_taxonomy_id | bigint(20) | 分类法ID（主键，自增） |
| term_id | bigint(20) | 关联的术语ID（外键，关联wp_terms.term_id） |
| taxonomy | varchar(32) | 分类法名称 |
| description | longtext | 分类法描述 |
| parent | bigint(20) | 父分类ID（用于层级分类） |
| count | bigint(20) | 使用该分类的文章数量 |


+ **常用分类法（taxonomy）：**
    - `category`：文章分类
    - `post_tag`：文章标签
    - `nav_menu`：导航菜单
    - 插件可能会添加自定义分类法
10. **wp_term_relationships 表**

存储文章与分类/标签的关联关系。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| object_id | bigint(20) | 文章ID（外键，关联wp_posts.ID） |
| term_taxonomy_id | bigint(20) | 分类法ID（外键，关联wp_term_taxonomy.term_taxonomy_id） |
| term_order | int(11) | 术语顺序 |


+ **重要说明：**
    - 此表是多对多关系表，一个文章可以有多个分类/标签
    - 主键是 `(object_id, term_taxonomy_id)` 的组合
11. **wp_termmeta 表**

存储分类和标签的元数据。

| 字段名 | 类型 | 说明 |
| --- | --- | --- |
| meta_id | bigint(20) | 元数据ID（主键，自增） |
| term_id | bigint(20) | 关联的术语ID（外键，关联wp_terms.term_id） |
| meta_key | varchar(255) | 字段名 |
| meta_value | longtext | 字段值 |


+ **常用字段（meta_key）：**
    - 插件可能会添加分类图标、颜色等元数据

# 接口设计
## 接口概述
<font style="color:rgb(0, 0, 0);">系统接口基于 REST API 规范设计，采用 HTTP 方法（GET/POST/PUT/DELETE）实现数据交互。所有写操作（POST/PUT/DELETE）需携带 X-WP-Nonce 进行身份验证，保障接口安全。接口返回格式统一为</font>`<font style="color:rgba(0, 0, 0, 0.85) !important;">{code: 整数, message: 字符串, data: 对象}</font>`

## <font style="color:rgb(0, 0, 0);">接口分类</font>
### 观演记录管理接口
#### 接口基础信息
+ **基础路径**：`/wp-json/viewing/v1/`
+ **认证方式**：WordPress Nonce（请求头：`X-WP-Nonce`）
+ **数据格式**：JSON（UTF-8编码）

#### 观演记录管理接口
1. **记录列表查询**
    - **接口**：`GET /viewing/v1/viewings`
    - **参数**：
        * `category`（string，可选）：类别筛选
        * `search`（string，可选）：关键词搜索（标题/剧院/卡司/备注）
        * `sort`（string，可选）：排序方式（`date_desc`/`date_asc`/`title_asc`/`title_desc`）
    - **响应**：记录数组，每条记录包含：id、title、category、theater、cast、price、view_date、view_time_start、view_time_end、notes、ticket_image、url、author
2. **单条记录查询**
    - **接口**：`GET /viewing/v1/viewings/{id}`
    - **参数**：`id`（路径参数，integer）
    - **响应**：单条记录对象（字段同1. 记录列表查询）
3. **创建记录**
    - **接口**：`POST /viewing/v1/viewings`
    - **请求体**（JSON）：

```markdown
{
  "title": "剧目名称（必填）",
  "category": "音乐剧|话剧|歌剧|舞剧|音乐会|戏曲|其他",
  "theater": "剧院名称",
  "cast": "卡司/演员",
  "price": "票价",
  "view_date": "2024-01-15",
  "view_time_start": "19:30",
  "view_time_end": "22:00",
  "notes": "备注",
  "ticket_image_id": 456
}
```

    - **响应**：`{"id": 123, "message": "记录创建成功"}`
4. **更新记录**
    - **接口**：`PUT /viewing/v1/viewings/{id}`
    - **参数**：`id`（路径参数，integer）
    - **请求体**：同3. 创建记录（所有字段可选，仅传需更新字段）
    - **响应**：`{"id": 123, "message": "记录更新成功", "updated": true}`
5. **删除记录**
    - **接口**：`DELETE /viewing/v1/viewings/{id}`
    - **参数**：`id`（路径参数，integer）
    - **响应**：`{"message": "记录删除成功"}`

#### 辅助功能接口
1. **OCR识别**
    - **接口**：`POST /viewing/v1/ocr`
    - **请求**：`multipart/form-data`，字段`image`（图片文件）
    - **响应**：识别结果对象

```markdown
{
  "title": "剧目名称",
  "theater": "剧院",
  "cast": "卡司",
  "price": "票价",
  "view_date": "2024-01-15",
  "_debug_text": "原始识别文本"
}
```

2. **图片上传**
    - **接口**：`POST /viewing/v1/upload-image`
    - **请求**：`multipart/form-data`，字段`file`（图片文件）
    - **响应**：`{"id": 456, "url": "图片URL", "thumbnail": "缩略图URL"}`
3. **错误响应与权限**

**错误响应格式**：

```markdown
{
  "code": "error_code",
  "message": "错误描述",
  "data": {"status": 400}
}
```

**常见错误码**：

    - `unauthorized` (401)：未登录
    - `forbidden` (403)：无权操作
    - `not_found` (404)：记录不存在
    - `missing_title` (400)：标题必填
    - `delete_failed` (500)：删除失败

**权限规则**：

    - 普通用户：仅可操作自己的记录
    - 管理员：可操作所有记录
    - 未登录用户：所有接口返回401

### 数据统计与可视化接口
#### 接口基础信息
+ **基础路径**：`/wp-json/viewing/v1/`
+ **认证方式**：WordPress Nonce（请求头：`X-WP-Nonce`）
+ **数据格式**：JSON（UTF-8编码）

#### 统计数据接口
1. **获取统计数据**
    - **接口**：`GET /viewing/v1/statistics`
    - **参数**：无
    - **响应（举例）**：

```markdown
{
  "category": {
    "音乐剧": 15,
    "话剧": 8
  },
  "cast": {
    "张三": 5,
    "李四": 3
  },
  "price": {
    "100-150元": 5,
    "150-200元": 8
  },
  "theater": {
    "上海大剧院": 10,
    "国家大剧院": 5
  }
}
```

2. **获取数据概览**
    - **接口**：`GET /viewing/v1/overview`
    - **参数**：无
    - **响应（举例）**：

```markdown
{
  "total_count": 28,
  "month_count": 3,
  "total_spending": 14560.00,
  "favorite_category": "音乐剧"
}
```

3. **获取统计数据详情**
    - **接口**：`GET /viewing/v1/statistics/details`
    - **参数**：
        * `type`（string，必填）：统计类型（category/cast/theater/price）
        * `value`（string，必填）：具体值（类别名/演员名/剧院名/票价区间）
        * `page`（integer，可选）：页码，默认1
        * `per_page`（integer，可选）：每页数量，默认20
    - **响应（举例）**：

```markdown
{
  "data": [
    {
      "id": 123,
      "title": "《歌剧魅影》",
      "category": "音乐剧",
      "theater": "上海大剧院",
      "cast": "张三, 李四",
      "price": "580",
      "view_date": "2024-01-15",
      "url": "https://example.com/viewing/123"
    }
  ],
  "total": 15,
  "pages": 1,
  "current_page": 1
}
```

4. **导出统计数据**
    - **接口**：`GET /viewing/v1/statistics/export`
    - **参数**：
        * `format`（string，可选）：导出格式（csv/json），默认csv
    - **响应**：文件下载

#### 错误响应与权限
**错误响应格式**：同观影记录管理模块

**权限规则**：

+ 普通用户：仅可查看和导出自己的统计数据
+ 管理员：可查看和导出所有用户的统计数据
+ 未登录用户：所有接口返回401

### 社区交流接口
#### 接口基础信息
+ **基础路径**：`/wp-json/community/v1/`
+ **认证方式**：WordPress Nonce（请求头：`X-WP-Nonce`）
+ **数据格式**：JSON（UTF-8编码）
+ **权限说明**：大部分接口仅限登录用户访问

#### 核心接口定义
1. **获取用户动态流**
    - **接口**：`GET /community/v1/activities`
    - **参数**：
        * `user_id`（integer，可选）：指定用户 ID，默认当前用户
        * `page`（integer，可选）：页码，默认 1
        * `per_page`（integer，可选）：每页数量，默认 10
        * `scope`（string，可选）：范围（`all`/`friends`/`groups`/`mentions`）
    - **响应**：
```json
[
  {
    "id": 101,
    "user_id": 1,
    "action": "陈攀 观看了 <a href='#'>歌剧魅影</a>",
    "content": "非常震撼的演出！",
    "date": "2024-03-20 14:00:00",
    "primary_link": "https://example.com/viewing/123",
    "viewing_id": 123
  }
]
```

2. **发布动态**
    - **接口**：`POST /community/v1/activities`
    - **请求体**：
```json
{
  "content": "今天天气真好，准备去看剧！",
  "component": "activity"
}
```
    - **响应**：`{"id": 102, "success": true}`

3. **动态互动（点赞/评论）**
    - **接口**：`POST /community/v1/activities/{id}/interact`
    - **参数**：`id`（路径参数，动态ID）
    - **请求体**：
```json
{
  "type": "like|comment",
  "content": "评论内容（仅 type=comment 时必填）"
}
```
    - **响应**：`{"success": true, "new_count": 5}`

4. **加入/退出小组**
    - **接口**：`POST /community/v1/groups/{id}/membership`
    - **参数**：`id`（路径参数，小组ID）
    - **请求体**：
```json
{
  "action": "join|leave"
}
```
    - **响应**：`{"success": true, "status": "member"}`

### 智能推荐接口
#### 接口基础信息
● 基础路径：`/wp-json/recommendation/v1/`  
● 认证方式：WordPress Nonce（请求头：`X-WP-Nonce`）  
● 数据格式：JSON（UTF-8 编码）  
● 访问权限：仅限已登录用户  

#### 智能推荐核心接口
**1.  推荐列表查询  **

○ 接口：`GET /recommendation/v1/recommendations`

○ 参数：  
（无显式查询参数，推荐结果完全基于当前登录用户的观演记录自动生成）

○ 业务说明：  
该接口用于获取当前登录用户的个性化音乐剧推荐列表。系统在接口调用时自动完成以下流程：

+ 查询当前用户的观演记录
+ 提取用户偏好特征（剧目类型、演员等）
+ 生成候选剧目集合
+ 排除已观演剧目
+ 根据推荐规则计算排序结果

○ 响应：推荐剧目数组，每条推荐项包含以下字段：

+ id：音乐剧 ID
+ title：剧目名称
+ categories：剧目类型数组
+ cast：主要演员
+ cover_url：封面图片 URL
+ url：剧目详情页链接
+ reason：推荐理由（基于匹配规则生成）

○ 响应示例：

```plain
[
  {
    "id": 321,
    "title": "伊丽莎白",
    "categories": ["音乐剧"],
    "cast": "主演：某某",
    "cover_url": "https://example.com/uploads/elisabeth.jpg",
    "url": "https://example.com/musical/elisabeth",
    "reason": "基于你常观看的音乐剧类型推荐"
  },
  {
    "id": 654,
    "title": "摇滚莫扎特",
    "categories": ["音乐剧"],
    "cast": "主演：某某",
    "cover_url": "https://example.com/uploads/mozart.jpg",
    "url": "https://example.com/musical/mozart",
    "reason": "与你近期观演剧目演员相关"
  }
]

```

**辅助说明：**

● 推荐接口为**只读接口**，不提供新增、修改或删除操作  
● 推荐结果**不进行持久化存储**，每次请求均为实时计算  
● 推荐结果与用户身份强绑定，不支持跨用户访问  
● 管理员账号调用接口时，推荐逻辑仍基于其个人观演记录

#### 接口与后端方法映射关系
| 接口路径 | HTTP 方法 | 后端处理方法 |
| --- | --- | --- |
| /recommendation/v1/recommendations | GET | Musicalbum_Recommendation::rest_get_recommendations |


### 剧院周边服务接口
#### 接口基础信息
+ **基础路径**：`/wp-json/theater-maps/v1/`
+ **认证方式**：WordPress Nonce（请求头：`X-WP-Nonce`）
+ **数据格式**：JSON（UTF-8编码）

#### 核心接口定义
1. **周边剧院搜索**
    - **接口**：`POST /theater-maps/v1/nearby`
    - **请求体**：
```json
{
  "lat": 31.2304,
  "lng": 121.4737,
  "radius": 5000,
  "keyword": "剧院"
}
```
    - **响应**：
```json
{
  "status": "success",
  "data": [
    {
      "name": "上海大剧院",
      "address": "黄浦区人民大道300号",
      "lat": 31.2325,
      "lng": 121.4748,
      "distance": 350
    }
  ]
}
```

2. **生活服务查询**
    - **接口**：`POST /theater-maps/v1/services`
    - **请求体**：
```json
{
  "lat": 31.2325,
  "lng": 121.4748,
  "type": "餐饮|交通|住宿"
}
```
    - **响应**：POI 列表数组

3. **获取已打卡剧院（足迹）**
    - **接口**：`GET /theater-maps/v1/footprints`
    - **参数**：
        * `user_id`（integer，可选）：默认当前用户
    - **响应**：
```json
[
  {
    "id": 1,
    "title": "上海大剧院",
    "lat": 31.2325,
    "lng": 121.4748,
    "count": 5,
    "last_visit": "2024-01-15"
  }
]
```

### 用户账户与权限接口
#### 接口基础信息
+ **基础路径**：`/wp-json/auth/v1/`
+ **认证方式**：WordPress Nonce
+ **数据格式**：JSON

#### 核心接口定义
1. **用户注册**
    - **接口**：`POST /auth/v1/register`
    - **请求体**：
```json
{
  "username": "chenpan",
  "email": "user@example.com",
  "password": "strong_password",
  "nickname": "陈攀"
}
```
    - **响应**：`{"success": true, "user_id": 123}`

2. **用户登录**
    - **接口**：`POST /auth/v1/login`
    - **请求体**：
```json
{
  "username": "chenpan",
  "password": "password"
}
```
    - **响应**：`{"success": true, "redirect": "/profile"}`

3. **获取当前用户资料**
    - **接口**：`GET /auth/v1/me`
    - **参数**：无
    - **响应**：
```json
{
  "id": 123,
  "username": "chenpan",
  "email": "user@example.com",
  "roles": ["subscriber"],
  "avatar": "https://...",
  "meta": {
    "favorite_theater": "上海大剧院"
  }
}
```

4. **更新用户资料**
    - **接口**：`POST /auth/v1/me`
    - **请求体**：
```json
{
  "nickname": "新的昵称",
  "description": "个人简介..."
}
```
    - **响应**：`{"success": true}`
## 外部接口集成设计
### <font style="color:rgb(0, 0, 0);">百度 OCR 接口</font>
+ <font style="color:rgb(0, 0, 0);">集成方式：通过自定义插件调用百度 OCR API，实现票面信息提取。</font>
+ <font style="color:rgb(0, 0, 0);">认证方式：使用 API Key 与 Secret Key 进行身份验证。</font>
+ <font style="color:rgb(0, 0, 0);">数据处理：将票面图片上传至百度 OCR 服务器，提取关键信息并验证识别结果，返回至前端。</font>
+ <font style="color:rgb(0, 0, 0);">接口：集成于观演记录 OCR 导入接口（</font>`<font style="color:rgb(0, 0, 0);">POST /wp-json/lyj/v1/ocr/ticket</font>`<font style="color:rgb(0, 0, 0);">），不直接对外暴露。</font>

# 非功能需求实现
## 性能需求实现
+ <font style="color:rgb(0, 0, 0);">页面加载速度：优化前端资源（压缩 JS/CSS 文件、图片懒加载），使用 CDN 分发静态资源，启用页面缓存与对象缓存（Redis），确保核心页面（首页、观演日历、社区）加载时间≤2 秒。</font>
+ <font style="color:rgb(0, 0, 0);">并发访问支持：配置 Nginx/Apache 服务器参数（调整 worker_processes、worker_connections），启用 PHP-FPM 进程管理，优化数据库索引，支持 1000 人并发访问无卡顿。</font>
+ <font style="color:rgb(0, 0, 0);">图表生成速度：优化数据查询 SQL，缓存统计结果（有效期 1 小时），使用 Visualizer 异步加载机制，确保图表生成时间≤3 秒。</font>
+ <font style="color:rgb(0, 0, 0);">搜索响应速度：为社区内容添加全文搜索索引，使用 Redis 缓存热门搜索结果，确保搜索响应时间≤1 秒</font>

## 安全需求实现
+ <font style="color:rgb(0, 0, 0);">数据安全：存储时加密敏感数据（密码、手机号），传输时使用 HTTPS 防止数据拦截，定期备份数据库防止数据丢失。</font>
+ <font style="color:rgb(0, 0, 0);">账户安全：实现密码复杂度验证（至少 8 位，含字母与数字），连续 5 次密码错误锁定账户 30 分钟，支持手机验证码登录增强安全性。</font>
+ <font style="color:rgb(0, 0, 0);">内容安全：社区内容采用 “关键词过滤 + 人工审核” 双重机制，违规信息拦截率≥95%，所有内容审核与删除操作留存日志可追溯。</font>
+ <font style="color:rgb(0, 0, 0);">防攻击能力：配置 Nginx 防御 SQL 注入、XSS、CSRF 等常见 Web 攻击，为公共接口设置 IP 限流，防止恶意请求与爬虫攻击。</font>

## 易用性需求实现
+ <font style="color:rgb(0, 0, 0);">易用性：简化核心功能（观演记录录入、内容发布、搜索）操作流程，确保操作步骤≤3 步，提供清晰操作提示与错误引导。</font>
+ <font style="color:rgb(0, 0, 0);">反馈机制：实时反馈用户操作结果（如表单提交成功提示、图表生成加载动画），清晰展示错误信息并提供解决方案（如 “图片大小超过 10MB，请上传≤10MB 的文件”）。</font>
+ <font style="color:rgb(0, 0, 0);">可访问性：支持字体大小调整（小 / 中 / 大），界面颜色对比度符合 WCAG 2.1 标准，适配视觉障碍用户。</font>

# 测试计划
## 功能测试
### 观演记录管理功能测试
+ **手动录入测试**
    - 测试表单字段验证（必填项、格式验证）
    - 测试日期时间选择器功能
    - 测试图片上传功能
    - 测试数据保存和更新功能
    - 测试数据删除功能
+ **OCR识别测试**
    - 测试图片上传和识别接口
    - 测试识别结果自动填充表单
    - 测试识别失败的错误处理
    - 测试不同格式图片的兼容性（JPG、PNG、WebP等）
+ **CSV批量导入测试**
    - 测试CSV文件格式验证
    - 测试数据解析和导入功能
    - 测试导入错误处理和提示
    - 测试大量数据导入的性能
+ **列表和日历视图测试**
    - 测试列表视图显示和分页
    - 测试日历视图渲染和交互
    - 测试视图切换功能
    - 测试数据筛选和搜索功能

### 数据统计功能测试
+ **统计数据计算测试**
    - 测试剧目类别分布统计准确性
    - 测试演员出场频率统计
    - 测试票价区间分布计算
    - 测试剧院分布统计
+ **图表渲染测试**
    - 测试Chart.js图表正常渲染
    - 测试不同图表类型（柱状图、饼图、折线图）
    - 测试图表交互功能（点击、悬停）
    - 测试数据为空时的图表显示
+ **数据导出测试**
    - 测试CSV格式导出功能
    - 测试导出数据完整性
    - 测试大量数据导出的性能

### 数据概览功能测试
+ **概览数据计算测试**
    - 测试总记录数统计准确性
    - 测试本月观演次数计算
    - 测试总花费累计计算
    - 测试最爱类别识别
+ **概览界面显示测试**
    - 测试概览卡片正常显示
    - 测试数据刷新功能
    - 测试空数据状态显示

### 社区功能测试
+ **资源分享测试**
    - 测试文件上传功能（音频、视频、文档等）
    - 测试资源分类和标签功能
    - 测试资源列表显示和分页
    - 测试资源搜索和筛选
    - 测试资源下载功能
    - 测试资源关联到观演记录
+ **知识库测试**
    - 测试知识文章创建和编辑
    - 测试文章分类和标签管理
    - 测试文章搜索功能
    - 测试文章列表显示
    - 测试文章详情页显示
    - 测试文章评论功能
+ **论坛集成测试（bbPress）**
    - 测试观演记录分享到论坛功能
    - 测试论坛帖子自动关联观演记录
    - 测试论坛分类创建和管理
    - 测试论坛主题和回复功能
    - 测试论坛短码显示功能
+ **社交网络集成测试（BuddyPress）**
    - 测试BuddyPress活动流集成
    - 测试观演记录分享到活动流
    - 测试用户资料扩展功能
    - 测试观演记录在用户资料中的显示
    - 测试群组功能集成
    - 测试用户活动短码显示
+ **社区推荐系统集成测试**
    - 测试社区数据对推荐算法的影响
    - 测试热门内容推荐
    - 测试关注用户的内容推荐

### 背景音乐模块测试
+ **音乐播放功能测试**
    - 测试音乐自动播放功能
    - 测试播放/暂停控制
    - 测试音量控制功能
    - 测试音乐循环播放
    - 测试音乐切换功能
+ **音乐配置测试**
    - 测试预设音乐设置功能
    - 测试自定义音乐URL设置
    - 测试音乐选择器功能
    - 测试音乐信息显示
+ **用户体验测试**
    - 测试音乐播放器界面显示
    - 测试移动端音乐播放
    - 测试音乐播放状态保存

### 智能推荐模块测试
+ **推荐算法测试**
    - 测试CRP（Contextual Related Posts）推荐功能
    - 测试YITH WooCommerce相关产品推荐
    - 测试基于分类的推荐功能
    - 测试推荐结果准确性
+ **推荐显示测试**
    - 测试推荐短码显示功能
    - 测试推荐结果列表渲染
    - 测试推荐为空时的显示
    - 测试推荐数量控制
+ **个性化推荐测试**
    - 测试基于用户观演历史的推荐
    - 测试推荐结果多样性

## 性能测试
### 数据加载性能
+ **页面加载速度测试**
    - 测试观演记录列表加载时间
    - 测试统计数据计算时间
    - 测试图表渲染时间
+ **数据库查询优化测试**
    - 测试大量数据下的查询性能
    - 测试索引使用情况
    - 测试查询缓存效果

### 并发性能测试
+ **多用户并发测试**
    - 测试多用户同时录入记录
    - 测试多用户同时查看统计数据
    - 测试API接口并发处理能力

# 用户体验设计
## 设计目标
乐影集平台的用户体验设计以音乐剧观众的真实使用场景为核心，围绕“记录方便、查看直观、理解清晰、推荐可信”四个关键目标展开。用户体验设计不仅关注界面美观性，更强调操作流程的连贯性、信息呈现的可理解性以及功能行为的可预期性，使用户在“观演前—观演中—观演后”的各个阶段均能获得顺畅、低负担的使用体验。

在设计过程中，系统充分结合 WordPress 平台特性与既有插件生态，通过轻量交互与清晰的信息结构，避免复杂操作与学习成本，确保不同技术背景的用户均可快速上手并长期使用。

## 目标用户与使用场景
### 目标用户
平台主要面向已具备一定音乐剧观演经验的普通观众，同时兼顾高频观演的资深剧迷与社区活跃用户。用户普遍具有以下特点：

+ 观演频率较高，存在记录、回顾与统计需求
+ 对演员、剧目类型具有明确偏好
+ 愿意通过数据与推荐探索新的剧目
+ 使用设备以桌面端与移动端浏览器为主

管理员用户则以维护平台内容、审核社区信息为主要使用场景，其界面体验以功能完整性与操作效率为优先。

## 整体交互设计原则
### 流程导向原则
系统交互围绕用户核心任务展开，避免无关操作干扰主要流程。例如，在观演记录管理模块中，用户从“查看列表—新增记录—编辑记录—查看统计”形成自然闭环，界面层级清晰，操作路径固定，减少跳转成本。

### 即时反馈原则
所有关键操作均提供明确反馈，包括但不限于：

+ 表单提交成功或失败提示
+ OCR 识别完成后的字段自动填充
+ 数据加载中的状态提示
+ 删除等危险操作的确认提示

通过即时反馈降低用户不确定感，增强系统可控性。

### 可解释性原则
智能推荐、数据统计等功能均提供明确的解释信息。例如推荐结果附带“推荐理由”，统计图表支持点击查看详情，确保用户理解系统行为逻辑，避免“黑箱感”。

## 核心功能模块的用户体验设计
### 观演记录管理体验设计
观演记录管理作为系统的核心功能，其体验设计重点在于**降低记录成本**与**提升查看效率**。

在记录创建环节，系统提供表单模态框形式，用户无需离开当前页面即可完成操作。OCR 票面识别功能作为辅助能力嵌入表单流程中，用户可选择手动填写或上传票面图片自动填充字段，系统在识别完成后高亮显示填充结果，便于用户校验与修改。

在记录查看环节，系统同时提供列表视图与日历视图两种方式。列表视图适合快速检索与编辑，日历视图则强化时间维度的直观感知，二者通过明显的视图切换控件进行切换，用户操作成本低且逻辑清晰。

### 数据统计与可视化体验设计
数据统计模块的体验设计目标是帮助用户“快速理解自己的观演行为”，而非提供复杂的数据分析工具。

统计页面采用“概览 + 图表”的布局方式，首先展示总记录数、本月观演次数等核心指标，帮助用户快速建立整体认知。下方固定展示类别分布、演员频率、票价区间等核心图表，图表类型选择以易理解为优先，避免信息过载。

交互层面，图表支持悬停提示与点击查看详情，用户可从宏观数据直接跳转到具体记录，形成从“统计—细节”的自然探索路径。数据导出功能集中放置于统计页面的辅助区域，避免干扰主要浏览行为。

### 智能推荐体验设计
智能推荐模块遵循“被动触发、主动解释”的体验策略。用户在访问推荐页面时无需进行任何额外操作，系统自动生成推荐列表，降低使用门槛。

推荐结果以列表形式呈现，每条推荐项除基础剧目信息外，均包含简要推荐理由，使用户明确该推荐与自身观演记录之间的关联关系。推荐数量控制在合理范围内，避免过多选择带来的决策压力。

推荐模块整体交互保持轻量化，不引入复杂设置选项，为后续推荐策略升级预留空间。

### 社区交流与扩展功能体验设计
社区模块在整体体验中扮演“补充与延伸”的角色，其界面风格与主功能模块保持一致，通过统一的主题样式与交互控件降低切换成本。用户在浏览社区内容时可便捷跳转至相关剧目或个人观演记录，增强系统内信息的联动性。

## 响应式与一致性设计
系统整体采用响应式布局，依托 Astra 主题的栅格系统与 Gutenberg 区块编辑器，确保在不同分辨率设备上均能正常显示与操作。移动端界面在不削弱功能完整性的前提下，对表格、图表与表单进行适配优化，优先保证可读性与可点击性。

在视觉层面，系统统一使用一致的按钮样式、颜色语义与交互反馈方式，使用户在不同模块中形成稳定的操作预期，减少认知负担。

## 用户体验设计总结
乐影集平台的用户体验设计以数据为核心、以流程为主线，通过简化操作路径、强化反馈机制与提升信息可解释性，构建了一个符合音乐剧观众使用习惯的垂直服务系统。该设计在保证功能完整性的同时，兼顾系统扩展性与长期使用体验，为平台后续功能迭代与用户规模增长奠定了良好的体验基础。

# 部署与运维
## <font style="color:rgb(0, 0, 0);">部署架构</font>
+ <font style="color:rgb(0, 0, 0);">服务器环境：Linux 服务器（CentOS 7+/Ubuntu 20.04+）、Nginx/Apache Web 服务器、PHP 8.1+、MySQL 8.0+、Redis 6.0+。</font>
+ <font style="color:rgb(0, 0, 0);">部署流程：</font>
    1. <font style="color:rgb(0, 0, 0);">安装配置服务器环境（Web 服务器、PHP、MySQL、Redis）。</font>
    2. <font style="color:rgb(0, 0, 0);">安装 WordPress 核心及所需插件（ACF、Profile Builder、CRP/YITH 等）。</font>
    3. <font style="color:rgb(0, 0, 0);">部署 Astra 父主题与 musicalbum-child 子主题。</font>
    4. <font style="color:rgb(0, 0, 0);">导入数据库结构与初始数据。</font>
    5. <font style="color:rgb(0, 0, 0);">配置域名、HTTPS、CDN 及缓存。</font>
    6. <font style="color:rgb(0, 0, 0);">测试系统功能与性能，通过后上线。</font>
+ <font style="color:rgb(0, 0, 0);">多环境支持：使用 Docker Compose 实现开发、测试、生产环境配置一致，简化部署与维护。</font>

## <font style="color:rgb(0, 0, 0);">维护设计</font>
+ <font style="color:rgb(0, 0, 0);">日志管理：通过 WordPress 内置日志功能与自定义插件记录系统运行日志（访问日志、错误日志、操作日志），包含用户操作、接口调用、错误信息等，便于问题定位与排查。</font>
+ <font style="color:rgb(0, 0, 0);">监控管理：使用 Zabbix/Nagios 等工具监控服务器资源（CPU、内存、磁盘）、数据库性能（查询速度、连接数）、系统可用性（响应时间、在线时长），异常情况触发告警。</font>
+ <font style="color:rgb(0, 0, 0);">更新与升级：定期更新 WordPress 核心、插件及主题，修复安全漏洞并提升性能；制定版本升级计划，确保功能升级时向后兼容。</font>
+ <font style="color:rgb(0, 0, 0);">备份策略：数据库每日增量备份、每周全量备份；静态资源（图片、文件）定期备份至云存储，防止数据丢失。</font>

# 总结
<font style="color:rgb(0, 0, 0);">本 SDD 基于 SRS 文档全面阐述了 “乐影集 - 音乐剧垂直生态服务平台” 的技术设计，涵盖系统架构、模块设计、数据设计、接口设计、非功能需求实现、部署与维护设计。设计遵循 IEEE 相关标准及项目设计原则，确保系统全面满足 SRS 中规定的功能与非功能需求。</font>

<font style="color:rgb(0, 0, 0);">系统采用模块化、松耦合的架构设计，模块分工明确、接口标准化，便于开发、测试与维护；所选技术栈成熟稳定，具备良好的扩展性与兼容性，可支持系统长期演进。后续开发过程中，团队将严格按照本设计文档实施开发，并根据实际情况及时调整优化设计，确保项目顺利完成。</font>

