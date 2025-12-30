# 一、插件定位

**musicalbum-smart-recommendations** 现在只做一件事：

> 读取现有插件中已有的「用户观演记录」，结合 saoju 接口数据，生成【三类剧目推荐】，并在一个页面中展示，支持“不感兴趣”的弱反馈。

## 二、你这个插件最终要呈现什么（先说结果）

最终用户能看到的页面结构只有这一种：

```
🎭 为你推荐的音乐剧

【基于你的观演历史】
- 阿波罗尼亚
  推荐理由：你看过《摇滚莫扎特》，且演员 X 也参演了该剧
  [不感兴趣]

【你可能喜欢的演员相关剧目】
- 剧目 B
  推荐理由：你关注 / 常观看演员 Y
  [不感兴趣]

【近期热门演出】
- 剧目 C
  推荐理由：最近 30 天内多次上演
  [不感兴趣]
```

**就这一个页面**，不做复杂配置，不做后台 UI。

---

## 三、插件文件结构（贴近你 zip 的“扁平版”）

我不会再给你 `includes/recommend/core/...` 这种东西了。

推荐你用 **这种结构**（基本等于你现有插件风格）：

```
musicalbum-smart-recommendations/
│
├── musicalbum-smart-recommendations.php   // 插件主文件
│
├── saoju-api.php                          // 封装 saoju 接口
├── recommendation.php                    // 推荐逻辑（核心）
├── feedback.php                          // 不感兴趣处理
│
├── page-recommend.php                    // 推荐页面渲染
│
└── uninstall.php
```

特点只有一个：
**打开文件你就知道在干嘛，没有“跳文件地狱”**。

---

## 四、你现有数据如何被使用（不再假设）

### 1. 用户观演记录（重点）

你 zip 里已经有了观演记录存储方式（我不管你是：

* user_meta
* 自定义表
* 还是 post_meta

**这个插件完全不改、不重建、不抽象它**。

插件只做一件事：

```php
$history = musicalbum_get_user_viewing_history($user_id);
```

你已有的方法 / 逻辑是什么，我就直接调用。

> 插件是“消费者”，不是“数据结构改革者”。

---

### 2. 新增数据（插件自己存，最少）

#### （1）不感兴趣列表（必须）

用 `user_meta` 就够了：

```php
user_meta:
- key: msr_not_interested
- value: [ "阿波罗尼亚", "剧目B" ]
```

不用 id，不用复杂结构，**用 musical 名称即可**。

---

#### （2）关注演员（可选，但建议）

同样用 `user_meta`：

```php
user_meta:
- key: msr_favorite_artists
- value: [ "X演员", "Y演员" ]
```

如果你不想单独做“关注”，
也可以 **完全从观演记录里统计演员出现频率**。

---

## 五、saoju 接口在插件中的使用方式（关键）

你给的接口非常全，但**插件只用 4 类**。

### 1. 获取所有音乐剧

```
/api/musical/
```

用途：

* 建立「音乐剧全集」
* 做推荐结果合法性校验

---

### 2. 音乐剧 → 演员

```
/api/musicalcast/
/api/role/
/api/artist/
```

用途：

* 从“你看过的剧” → 找出演员
* 再从演员 → 反推其他剧目

---

### 3. 是否近期有演出（非常重要）

```
/api/search_musical_show/?musical={}&begin_date=&end_date=
```

用途：

* 只推荐 **“还能看的”**
* 排序时优先推荐近期有演出的

---

### 4. Trending（热门）

```
/api/search_day/?date=
```

用法很粗暴，但完全够用：

* 拉最近 N 天
* 统计 musical 出现次数
* 次数高 = 热门

**不用缓存算法，不用复杂权重。**

---

## 六、三类推荐的“傻但对”的实现逻辑

### 1. 基于观演历史推荐（Personal）

逻辑顺序（完全可 procedural）：

1. 读取用户观演历史 → 得到 musical 列表
2. 从这些 musical 中提取演员（统计次数）
3. 取 Top N 演员
4. 查这些演员参演的其他 musical
5. 排除：

   * 已看过
   * 不感兴趣
6. 检查是否近期有演出
7. 返回推荐 + 推荐理由

---

### 2. 演员关联推荐（Actor-based）

来源可以是：

* 用户关注演员
* 或历史中出现频率最高的演员

逻辑：

* 演员 → musicalcast → musical
* 排除看过 / 不感兴趣
* 按“是否有演出”排序

---

### 3. 热门 / Trending

逻辑：

1. 拉最近 7 / 30 天的 `search_day`
2. 统计 musical 出现次数
3. 排序
4. 排除用户不感兴趣

**不依赖用户历史，也不复杂。**

---

## 七、不感兴趣（弱反馈）的真实作用

不做学习，只做三件事：

1. **当前页面立即隐藏**
2. **后续推荐直接过滤**
3. **如果多次命中同一演员**

   * 在 actor 推荐中降低优先级（比如直接不再推荐该演员）

实现上你只需要一句话：

```php
if ( in_array( $musical_name, $not_interested_list ) ) continue;
```

---

## 八、页面是怎么出来的（不要想复杂）

你不需要 React、不需要 REST。

最简单、最贴合你 zip 风格的方式：

* 插件注册一个页面 / shortcode
* 页面直接 include `page-recommend.php`
* 在 PHP 里：

  * 拉数据
  * 调用推荐函数
  * echo HTML

例如：

```php
add_shortcode('musical_recommend', 'msr_render_page');
```


