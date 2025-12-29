# Musicalbum Smart Recommendations

Musicalbum Smart Recommendations 是一个基于用户行为与音乐剧内容标签的智能帖子推荐插件，
用于为 Musicalbum 社区中的用户提供个性化内容推荐。

该插件可独立运行，并可与 Musicalbum Community Integration 插件深度集成，
结合社区行为数据提升推荐准确度。

---

## 功能概述

### 核心功能

- 基于用户行为的内容推荐
  - 浏览帖子
  - 收藏帖子
  - 发布帖子
  - 观演记录分享
- 基于音乐剧标签（Taxonomy）的内容匹配
- 个性化推荐结果生成
- 推荐结果缓存与性能优化
- 推荐结果前端展示（页面 / 短码）

---

## 推荐系统设计思路

推荐系统采用「内容 + 行为」的混合推荐方式：

- 内容维度
  - 音乐剧标签（自定义 taxonomy）
  - 帖子类型（社区帖子、资源、知识文章等）
- 行为维度
  - 用户浏览历史
  - 用户收藏记录
  - 社区活跃度

推荐结果通过加权评分方式生成，保证逻辑可解释、结果稳定。

---

## 插件架构

```

用户行为
↓
用户行为追踪模块
↓
推荐引擎（评分 / 排序）
↓
WP_Query 获取推荐帖子
↓
前端展示（短码 / 页面）

```

---

## 插件目录结构

```

musicalbum-smart-recommendations/
├── musicalbum-smart-recommendations.php
├── includes/
│   ├── class-user-behavior-tracker.php
│   ├── class-recommendation-engine.php
│   ├── class-community-adapter.php
│   ├── class-shortcodes.php
│   └── class-admin-settings.php
├── assets/
│   ├── recommendations.css
│   └── recommendations.js
└── README.md

```

---

## 与其他插件的集成

### Musicalbum Community Integration（可选）

- 获取社区行为数据
- 获取观演记录分享信息
- 获取用户活跃度指标

### BuddyPress（可选）

- 用户资料
- 用户活动流
- 社交行为追踪

### bbPress（可选）

- 论坛主题与回复
- 用户讨论参与度

---

## 短码支持

### 显示智能推荐列表

```

[musicalbum_smart_recommendations limit="10"]

```

参数说明：

- `limit`：推荐帖子数量（默认 5）

---

## 推荐算法说明（示例）

推荐评分由以下部分组成：

- 音乐剧标签匹配度 × 权重
- 用户历史行为权重
- 社区活跃度权重

最终按综合评分排序输出推荐结果。

---

## 系统要求

- WordPress 5.8+
- PHP 7.4+
- MySQL 5.6+

---

## 开发说明

### 可扩展接口

#### 过滤器

- `musicalbum_smart_recommendation_posts`
  - 用于修改最终推荐结果

#### 动作钩子

- `musicalbum_sr_user_behavior_recorded`
- `musicalbum_sr_recommendation_generated`

---

## 版本记录

### 1.0.0

- 插件初始版本
- 实现基础用户行为推荐
- 支持音乐剧标签匹配
- 支持前端推荐展示


