# Musicalbum Community Integration

Musicalbum 社区交流与信息共享功能模块，集成 bbPress 和 BuddyPress，与 musicalbum 观演记录系统联动。

## 功能特性

### 核心功能
- **论坛集成**：与 bbPress 集成，创建观演记录相关论坛分类
- **社交网络**：与 BuddyPress 集成，扩展用户资料和活动流
- **观演记录分享**：一键分享观演记录到论坛和活动流
- **资源分享**：文件上传和资源库管理
- **知识库**：知识文章管理和分类
- **推荐系统集成**：社区数据影响推荐算法

### 短码支持
- `[musicalbum_forum]` - 显示论坛内容
- `[musicalbum_user_activity]` - 显示用户活动
- `[musicalbum_resource_library]` - 显示资源库
- `[musicalbum_knowledge_base]` - 显示知识库

## 依赖插件

### 必需插件
- **bbPress** - WordPress 官方论坛插件
- **BuddyPress** - WordPress 官方社交网络插件

### 可选插件
- **Musicalbum Smart Recommendations** - 推荐系统集成（可选）

## 安装说明

1. 确保已安装并激活 bbPress 和 BuddyPress 插件
2. 将插件文件夹上传到 `/wp-content/plugins/` 目录
3. 在 WordPress 后台激活插件
4. 进入"设置" > "Musicalbum 社区"进行配置

## 配置说明

### 基本设置
- **启用论坛集成**：启用与 bbPress 的集成功能
- **启用资源分享**：启用资源分享功能
- **启用知识库**：启用知识库功能
- **观演记录论坛ID**：用于分享观演记录的论坛ID

### 自动创建内容
插件激活后会自动创建：
- "观演交流"论坛（bbPress）
- "观演交流"群组（BuddyPress）
- 资源分享自定义文章类型
- 知识库自定义文章类型

## 使用方法

### 分享观演记录
在观演记录详情页，会自动显示"分享到社区"表单，用户可以：
- 添加分享说明
- 选择分享到论坛或活动流
- 一键分享

### 使用短码

#### 显示论坛
```
[musicalbum_forum forum_id="1" limit="10"]
```

#### 显示用户活动
```
[musicalbum_user_activity user_id="1" limit="10"]
```

#### 显示资源库
```
[musicalbum_resource_library limit="12" category="music"]
```

#### 显示知识库
```
[musicalbum_knowledge_base limit="10" category="guide"]
```

## REST API

### 分享观演记录
```
POST /wp-json/musicalbum/v1/community/share-viewing
```

参数：
- `viewing_id` (int) - 观演记录ID
- `message` (string) - 分享说明（可选）
- `share_to_forum` (bool) - 是否分享到论坛
- `share_to_activity` (bool) - 是否分享到活动流

### 获取用户统计
```
GET /wp-json/musicalbum/v1/community/user-stats/{user_id}
```

### 获取资源列表
```
GET /wp-json/musicalbum/v1/community/resources
```

参数：
- `per_page` (int) - 每页数量
- `page` (int) - 页码
- `category` (string) - 分类slug

## 文件结构

```
musicalbum-community-integration/
├── musicalbum-community-integration.php    # 主插件文件
├── includes/
│   ├── class-bbpress-integration.php      # bbPress 集成
│   ├── class-buddypress-integration.php   # BuddyPress 集成
│   ├── class-viewing-integration.php      # 观演记录集成
│   ├── class-resource-sharing.php         # 资源分享
│   ├── class-knowledge-base.php          # 知识库
│   ├── class-customizations.php           # 样式定制
│   └── class-recommendation-integration.php # 推荐系统集成
├── assets/
│   ├── community-integration.css         # 前端样式
│   ├── community-integration.js          # 前端脚本
│   └── admin.css                         # 后台样式
├── templates/
│   ├── viewing-share-form.php            # 分享表单模板
│   └── resource-item.php                 # 资源项模板
└── README.md                             # 说明文档
```

## 开发说明

### 钩子和过滤器

#### 过滤器
- `musicalbum_recommendation_posts` - 修改推荐结果（需要推荐插件）

#### 动作钩子
- `musicalbum_viewing_shared` - 观演记录分享后触发
- `musicalbum_resource_uploaded` - 资源上传后触发

### 扩展开发

#### 添加自定义集成
```php
add_action('musicalbum_community_init', function() {
    // 你的自定义代码
});
```

## 注意事项

1. **性能考虑**：BuddyPress 和 bbPress 都是功能丰富的插件，可能影响网站性能，建议：
   - 使用缓存插件
   - 优化数据库查询
   - 考虑使用 CDN

2. **服务器要求**：
   - PHP 7.4 或更高版本
   - MySQL 5.6 或更高版本
   - 建议内存限制至少 128MB

3. **兼容性**：
   - WordPress 5.8+
   - bbPress 2.6+
   - BuddyPress 10.0+

## 更新日志

### 1.0.0
- 初始版本发布
- 集成 bbPress 和 BuddyPress
- 实现观演记录分享功能
- 添加资源分享和知识库功能
- 集成推荐系统

## 支持

如有问题或建议，请联系开发团队。

## 许可证

GPL v2 或更高版本

