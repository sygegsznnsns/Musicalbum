# 社区插件使用指南

## 快速开始

### 方法一：创建新页面（推荐）

1. **创建社区中心页面**
   - 进入 WordPress 后台：**页面** > **新建页面**
   - 标题：`社区中心` 或 `Community Center`
   - 在右侧"页面属性"中选择模板：**Musicalbum Community Center**
   - 发布页面

2. **访问页面**
   - 发布后访问该页面即可看到所有社区功能

### 方法二：在现有页面中使用短码

在任何页面或文章编辑器中，直接插入以下短码：

#### 1. 显示论坛内容
```
[musicalbum_forum]
```

**参数说明：**
- `forum_id` - 论坛ID（可选，默认使用观演记录论坛）
- `limit` - 显示主题数量（默认10）
- `category` - 分类（可选）

**示例：**
```
[musicalbum_forum forum_id="1" limit="15"]
```

#### 2. 显示用户活动
```
[musicalbum_user_activity]
```

**参数说明：**
- `user_id` - 用户ID（可选，默认当前登录用户）
- `limit` - 显示活动数量（默认10）

**示例：**
```
[musicalbum_user_activity user_id="2" limit="20"]
```

#### 3. 显示资源库
```
[musicalbum_resource_library]
```

**参数说明：**
- `limit` - 显示资源数量（默认12）
- `category` - 资源分类slug（可选）

**示例：**
```
[musicalbum_resource_library limit="20" category="music"]
```

#### 4. 显示知识库
```
[musicalbum_knowledge_base]
```

**参数说明：**
- `limit` - 显示文章数量（默认10）
- `category` - 知识分类slug（可选）

**示例：**
```
[musicalbum_knowledge_base limit="15" category="guide"]
```

## 功能使用说明

### 1. 分享观演记录

**自动功能：**
- 在观演记录详情页会自动显示"分享到社区"表单
- 用户可以选择分享到论坛或活动流
- 可以添加分享说明

**手动分享：**
- 进入观演记录详情页
- 滚动到页面底部
- 填写分享表单并提交

### 2. 使用论坛

**访问论坛：**
- 如果安装了 bbPress，可以使用 bbPress 的论坛页面
- 或使用 `[musicalbum_forum]` 短码在自定义页面显示

**创建主题：**
- 在论坛页面点击"新建主题"
- 标题和内容会自动关联到观演记录（如果是从观演记录分享的）

### 3. 查看用户活动

**BuddyPress 活动流：**
- 如果安装了 BuddyPress，可以使用 BuddyPress 的活动页面
- 或使用 `[musicalbum_user_activity]` 短码显示

**用户资料：**
- 在 BuddyPress 用户资料页会自动显示观演记录统计
- 点击"观演记录"标签页查看详细列表

### 4. 上传和分享资源

**上传资源：**
1. 进入 WordPress 后台
2. 找到"共享资源"菜单
3. 点击"添加资源"
4. 填写标题、描述
5. 上传文件
6. 选择分类和标签
7. 发布

**前端显示：**
- 使用 `[musicalbum_resource_library]` 短码显示资源库
- 用户可以浏览和下载资源

### 5. 管理知识库

**创建知识文章：**
1. 进入 WordPress 后台
2. 找到"知识库"菜单
3. 点击"添加文章"
4. 编写内容
5. 选择分类和标签
6. 发布

**前端显示：**
- 使用 `[musicalbum_knowledge_base]` 短码显示知识库
- 用户可以浏览和搜索知识文章

## 页面布局建议

### 社区首页布局示例

```
[标题：社区中心]

[论坛区域]
[musicalbum_forum limit="10"]

[活动流区域]
[musicalbum_user_activity limit="10"]

[资源库区域]
[musicalbum_resource_library limit="12"]

[知识库区域]
[musicalbum_knowledge_base limit="10"]
```

### 侧边栏布局示例

可以在侧边栏小工具中添加：

```
[最新活动]
[musicalbum_user_activity limit="5"]

[热门资源]
[musicalbum_resource_library limit="6"]
```

## 配置检查清单

使用前请确认：

- [ ] bbPress 插件已安装并激活
- [ ] BuddyPress 插件已安装并激活
- [ ] Musicalbum Community Integration 插件已激活
- [ ] 进入"设置" > "Musicalbum 社区"检查配置
- [ ] 确认"观演记录论坛ID"已设置（插件会自动创建）

## 常见问题

### Q: 短码不显示内容？
A: 检查：
1. 插件是否已激活
2. bbPress/BuddyPress 是否已激活
3. 是否有内容（论坛主题、活动等）

### Q: 分享功能不工作？
A: 检查：
1. 用户是否已登录
2. bbPress/BuddyPress 是否正常工作
3. 浏览器控制台是否有错误

### Q: 如何自定义样式？
A: 
1. 在子主题的 `style.css` 中添加自定义样式
2. 或使用 WordPress 自定义器
3. 参考 `assets/community-integration.css` 中的类名

### Q: 如何添加更多功能？
A: 
1. 查看 `includes/` 目录中的类文件
2. 使用 WordPress 钩子和过滤器扩展功能
3. 参考 README.md 中的开发说明

## 技术支持

如有问题，请检查：
1. WordPress、bbPress、BuddyPress 版本是否兼容
2. 插件设置是否正确
3. 服务器 PHP 版本是否符合要求（7.4+）

