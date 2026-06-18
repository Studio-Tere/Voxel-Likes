# Voxel Likes

Add reusable IP-based likes for Voxel posts, Actions, dynamic tags, and sorting filters in listings.

## Features

- Adds a `Like` action to the Voxel Actions widget.
- Supports guests and logged-in users.
- Stores only an HMAC hash of the visitor IP.
- Keeps unlike rows with `liked = 0`.
- Adds dynamic tags:
  - `@post(likes.total)`
  - `@post(likes.count)`
- Updates like counters through AJAX after toggling a like.
- Adds a `Mas likes` order option to Voxel post type filters.
- Cleans likes automatically when a post is permanently deleted.

## Requirements

- WordPress 6.0+
- PHP 8.0+
- Voxel theme

## Author

Studio Tere  
https://studiotere.io
