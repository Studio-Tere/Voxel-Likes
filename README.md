# Voxel Addons Actions

Add reusable IP-based likes, post views, reading time, Actions, dynamic tags, and sorting filters for Voxel posts.

Voxel Addons Actions is built for WordPress sites using the Voxel theme. It adds a reusable like/unlike system that works for Voxel-managed post types without storing raw visitor IP addresses.

## Features

- Adds `Like`, `Post views`, and `Reading time` actions to the Voxel Actions widget.
- Supports guests and logged-in users.
- Stores only an HMAC hash of the visitor IP.
- Keeps unlike rows with `liked = 0`.
- Uses a dedicated WordPress database table: `wp_voxel_likes`.
- Adds dynamic tags:
  - `@post(likes.total)`
  - `@post(likes.count)`
  - `@post(views.total)`
  - `@post(views.count)`
  - `@post(views.unique_total)`
  - `@post(reading_time.minutes)`
  - `@post(reading_time.label)`
  - `@post(reading_time.words)`
- Updates like counters through AJAX after toggling a like.
- Adds a `Mas likes` order option to Voxel post type filters and listings.
- Cleans likes automatically when a post is permanently deleted.
- Automatically adds the order filter to current and future Voxel post types when the admin loads.

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Voxel theme

## Installation

1. Upload the `voxel-addons-actions` folder to `wp-content/plugins/`.
2. Activate **Voxel Addons Actions** from the WordPress Plugins screen.
3. Open or refresh the WordPress admin once so Voxel post type search settings can be updated.

## Usage

### Voxel Actions

In the **Actions (VX)** widget, add the **Like** action.

The action does not force visible text by default. Use the action icon and the widget's own text field if you want a label.

You can also add **Post views** and **Reading time** actions. If their text field is empty, they display the all-time view count or localized reading-time label. If you add text or a dynamic tag in the widget field, that custom text is used instead.

### Dynamic Tags

Use these Voxel dynamic tags anywhere the current post is available:

```text
@post(likes.total)
@post(likes.count)
@post(views.total)
@post(views.count)
@post(views.unique_total)
@post(reading_time.minutes)
@post(reading_time.label)
@post(reading_time.words)
```

The likes tags output the active like count and update through AJAX after a visitor toggles a like on the same page.

The views tags read Voxel's native traffic statistics. Voxel statistics must be enabled for the relevant post type in **Voxel > Settings > Statistics**; otherwise, the tags return `0`.

Reading time is calculated from the WordPress post content at 200 words per minute by default. Developers can adjust this with the `voxel_addons_actions/reading_time_words_per_minute` filter. The previous `voxel_likes/reading_time_words_per_minute` filter is still supported for compatibility.

The legacy hidden tag `@post(like_count)` remains for compatibility, but new templates should use `@post(likes.total)` or `@post(likes.count)`.

### Listings And Sorting

Voxel Addons Actions adds a **Mas likes** order option to Voxel post type search settings.

For the **Post Feed (VX)** widget, use the **Filters** data source and add/use the `Ordenar` filter. The **Mas likes** option sorts posts by active like count.

## Data Storage

Likes are stored in the dedicated table:

```text
wp_voxel_likes
```

Columns:

- `post_id`: WordPress post ID.
- `ip_hash`: HMAC hash of the visitor IP using WordPress salts.
- `liked`: `1` for liked, `0` for unliked.
- `created_at`: first row creation time.
- `updated_at`: last state change time.

Raw IP addresses are not stored.

## Compatibility Notes

- The old action type `publicacion_like` is still rendered internally for existing templates, but it is hidden from the new action selector.
- The old order types `voxel-likes` and `publicacion-likes` are still accepted internally and migrated to `voxel-addons-actions` in Voxel post type settings.
- Existing data from `wp_publicacion_likes` is copied into `wp_voxel_likes` during activation if the old table exists.

## Changelog

### 0.3.1

- Renamed the plugin to **Voxel Addons Actions**.
- Renamed the plugin slug, main file, text domain, language files, and asset handle to `voxel-addons-actions`.
- Kept legacy action, AJAX, data table, and order type compatibility.

### 0.3.0

- Added `Post views` and `Reading time` actions for the Voxel Actions widget.
- Added dynamic tags for Voxel post views and estimated reading time.

### 0.2.2

- Like counter dynamic tags update through AJAX after toggling a like.
- `@post(likes.total)` and `@post(likes.count)` output live counter markup.

### 0.2.1

- Added the `likes` dynamic tag group with `total` and `count` values.
- Removed automatic visible text/count from the Like action.

### 0.2.0

- Renamed the plugin to Voxel Likes.
- Made likes work with any valid Voxel post type.
- Installed the **Mas likes** order across Voxel post type filters.

### 0.1.1

- Added a Voxel dynamic tag for the like counter.

### 0.1.0

- Initial plugin release.

## Author

Studio Tere  
https://studiotere.io
