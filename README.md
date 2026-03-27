# Watermark Manager

[![License: GPL-2.0-or-later](https://img.shields.io/badge/license-GPL--2.0--or--later-blue.svg)](./LICENSE)
[![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b.svg)](https://wordpress.org/)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)

Protect your media and brand every published image with configurable watermarks in WordPress without modifying original files.

Watermark Manager generates and serves watermark copies of images for selected post types. It supports image and text watermarks, multiple positions, opacity, scale, and bulk generation for existing content.

## 🚀 Features

- 🖼️ Dual watermark modes: use an image watermark (recommended PNG) or text watermark.
- 🎯 Post type targeting: apply only to selected post types (including custom post types).
- 📍 Flexible placement: `top-left`, `top-right`, `center`, `bottom-left`, `bottom-right`.
- 🎚️ Fine-grained controls: adjust scale, text opacity, and margin.
- ⚡ Frontend-safe delivery: serves watermarked URLs on the frontend while keeping originals untouched.
- 🧩 Content + featured images: processes inline content images (`the_content`) and featured images (`post_thumbnail_html`).
- 📚 Existing content support: bulk-generate watermarked files for already published posts.
- 🗂️ Smart cache folders: stores generated images under `/uploads/watermarks/<settings-hash>/...`.

## 📦 Installation

1. Build plugin ZIP (optional if you install from source):

```bat
build-plugin.bat
```

2. In WordPress Admin, open `Plugins -> Add New -> Upload Plugin`.
3. Upload `dist/watermark-manager.zip` and activate the plugin.
4. Open `Settings -> Watermark Manager`.

Install from source instead of ZIP:

1. Copy this repository into `wp-content/plugins/watermark-manager`.
2. Activate **Watermark Manager** in WordPress Admin.

## 🛠 Usage

1. Go to `Settings -> Watermark Manager`.
2. Enable watermarking.
3. Select post types where watermarking should apply.
4. Choose watermark type:
- `Image`: select a watermark asset from Media Library.
- `Text`: set text, color, and opacity.
5. Set position, scale, and margin.
6. Save settings.
7. Click bulk generation to prepare watermark files for existing posts.

Example flow for image watermark:

```text
Settings -> Watermark Manager
Enable -> Post types: post, product -> Type: Image -> Position: bottom-right
Scale: 24 -> Margin: 24 -> Save -> Bulk generate
```

## ⚙️ Configuration

Default configuration:

```php
[
  'enabled' => 1,
  'post_types' => ['materials'],
  'watermark_type' => 'image',
  'position' => 'bottom-right',
  'scale_percent' => 24,
  'margin' => 24,
  'text_color' => '#FFFFFF',
  'text_opacity' => 55,
]
```

Technical notes:

- Requires PHP GD extension.
- Supported image formats: JPEG, PNG, GIF, WEBP (if WEBP functions are available in GD).
- Original media files are not overwritten.
- New watermark files are generated lazily on first request and cached by settings hash.

## 📌 Use Cases

- Content websites that want to protect original editorial images.
- Marketplace/catalog sites that need branded product imagery.
- Agencies managing multiple post types and media-heavy content.
- Membership sites that display preview images with visible branding.

## 🤝 Contributing

Contributions are welcome and appreciated.

- Read [CONTRIBUTING.md](./CONTRIBUTING.md)
- Open an issue for bugs or feature ideas
- Submit a focused pull request with clear reproduction and test steps

## 📄 License

GPL-2.0-or-later. See [LICENSE](./LICENSE).

## 🔐 Security

Please report vulnerabilities privately. See [SECURITY.md](./SECURITY.md).

## 🗒️ Changelog

See [CHANGELOG.md](./CHANGELOG.md) for release history.
