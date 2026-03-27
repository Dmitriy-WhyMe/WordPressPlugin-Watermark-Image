<?php
/**
 * Plugin Name: Watermark Manager
 * Description: Adds configurable image/text watermarks to generated images for selected post types.
 * Version: 1.0.0
 * Author: WhyMe
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: watermark-manager
 * License: GPL-2.0-or-later
 * Update URI: false
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function site_watermark_default_settings() {
	return array(
		'enabled'              => 1,
		'post_types'           => array( 'materials' ),
		'watermark_type'       => 'image',
		'watermark_attachment' => 0,
		'watermark_text'       => '',
		'text_color'           => '#FFFFFF',
		'text_opacity'         => 55,
		'position'             => 'bottom-right',
		'scale_percent'        => 24,
		'margin'               => 24,
	);
}

function site_watermark_get_settings() {
	$saved = get_option( 'site_watermark_settings', array() );

	return wp_parse_args( is_array( $saved ) ? $saved : array(), site_watermark_default_settings() );
}

function site_watermark_get_selected_post_types() {
	$settings   = site_watermark_get_settings();
	$post_types = isset( $settings['post_types'] ) && is_array( $settings['post_types'] ) ? $settings['post_types'] : array();

	return array_values( array_filter( array_map( 'sanitize_key', $post_types ) ) );
}

function site_watermark_is_post_type_selected( $post_type ) {
	return in_array( $post_type, site_watermark_get_selected_post_types(), true );
}

function site_watermark_has_valid_source() {
	$settings = site_watermark_get_settings();

	if ( empty( $settings['enabled'] ) ) {
		return false;
	}

	if ( 'text' === $settings['watermark_type'] ) {
		return '' !== trim( (string) $settings['watermark_text'] );
	}

	return ! empty( $settings['watermark_attachment'] );
}

function site_watermark_settings_hash() {
	return substr( md5( wp_json_encode( site_watermark_get_settings() ) ), 0, 12 );
}

function site_watermark_register_settings_page() {
	add_options_page(
		'Водяной знак',
		'Водяной знак',
		'manage_options',
		'site-watermark',
		'site_watermark_render_settings_page'
	);
}
add_action( 'admin_menu', 'site_watermark_register_settings_page' );

function site_watermark_register_settings() {
	register_setting(
		'site_watermark_group',
		'site_watermark_settings',
		'site_watermark_sanitize_settings'
	);
}
add_action( 'admin_init', 'site_watermark_register_settings' );

function site_watermark_get_available_post_types() {
	$post_types = get_post_types(
		array(
			'public' => true,
		),
		'objects'
	);

	unset( $post_types['attachment'] );

	return $post_types;
}

function site_watermark_sanitize_settings( $input ) {
	$defaults             = site_watermark_default_settings();
	$available_post_types = array_keys( site_watermark_get_available_post_types() );
	$post_types           = isset( $input['post_types'] ) && is_array( $input['post_types'] ) ? array_map( 'sanitize_key', $input['post_types'] ) : $defaults['post_types'];
	$post_types           = array_values( array_intersect( $post_types, $available_post_types ) );
	$watermark_type       = isset( $input['watermark_type'] ) && in_array( $input['watermark_type'], array( 'image', 'text' ), true ) ? $input['watermark_type'] : $defaults['watermark_type'];
	$text_color           = isset( $input['text_color'] ) ? sanitize_hex_color( $input['text_color'] ) : $defaults['text_color'];

	return array(
		'enabled'              => empty( $input['enabled'] ) ? 0 : 1,
		'post_types'           => ! empty( $post_types ) ? $post_types : $defaults['post_types'],
		'watermark_type'       => $watermark_type,
		'watermark_attachment' => isset( $input['watermark_attachment'] ) ? absint( $input['watermark_attachment'] ) : 0,
		'watermark_text'       => isset( $input['watermark_text'] ) ? sanitize_text_field( $input['watermark_text'] ) : '',
		'text_color'           => $text_color ? $text_color : $defaults['text_color'],
		'text_opacity'         => isset( $input['text_opacity'] ) ? max( 5, min( 100, absint( $input['text_opacity'] ) ) ) : $defaults['text_opacity'],
		'position'             => isset( $input['position'] ) ? sanitize_key( $input['position'] ) : $defaults['position'],
		'scale_percent'        => isset( $input['scale_percent'] ) ? max( 5, min( 60, absint( $input['scale_percent'] ) ) ) : $defaults['scale_percent'],
		'margin'               => isset( $input['margin'] ) ? max( 0, min( 100, absint( $input['margin'] ) ) ) : $defaults['margin'],
	);
}

function site_watermark_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Недостаточно прав для доступа к этой странице.' );
	}

	$message = '';

	if (
		isset( $_POST['site_watermark_bulk_generate'] ) &&
		check_admin_referer( 'site_watermark_bulk_action', 'site_watermark_bulk_nonce' )
	) {
		$processed = site_watermark_bulk_generate();
		$message   = 'Готово. Подготовлено изображений с водяным знаком: ' . intval( $processed ) . '.';
	}

	$settings           = site_watermark_get_settings();
	$available_types    = site_watermark_get_available_post_types();
	$watermark_image_id = ! empty( $settings['watermark_attachment'] ) ? absint( $settings['watermark_attachment'] ) : 0;
	$watermark_image    = $watermark_image_id ? wp_get_attachment_image_url( $watermark_image_id, 'medium' ) : '';
	?>
	<div class="wrap">
		<h1>Водяной знак</h1>
		<p>На фронте сайт будет отдавать водяные копии изображений для выбранных типов записей. Оригиналы в медиатеке не изменяются.</p>

		<?php if ( $message ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $message ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="options.php">
			<?php settings_fields( 'site_watermark_group' ); ?>
			<table class="form-table" role="presentation">
				<tbody>
					<tr>
						<th scope="row">Включить водяной знак</th>
						<td>
							<label>
								<input type="checkbox" name="site_watermark_settings[enabled]" value="1" <?php checked( ! empty( $settings['enabled'] ) ); ?>>
								Применять к выбранным типам записей
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Типы записей</th>
						<td>
							<?php foreach ( $available_types as $post_type ) : ?>
								<label style="display:block;margin-bottom:6px;">
									<input
										type="checkbox"
										name="site_watermark_settings[post_types][]"
										value="<?php echo esc_attr( $post_type->name ); ?>"
										<?php checked( in_array( $post_type->name, $settings['post_types'], true ) ); ?>
									>
									<?php echo esc_html( $post_type->labels->singular_name ); ?> (`<?php echo esc_html( $post_type->name ); ?>`)
								</label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row">Тип водяного знака</th>
						<td>
							<label style="margin-right:16px;">
								<input type="radio" name="site_watermark_settings[watermark_type]" value="image" <?php checked( 'image', $settings['watermark_type'] ); ?>>
								Изображение
							</label>
							<label>
								<input type="radio" name="site_watermark_settings[watermark_type]" value="text" <?php checked( 'text', $settings['watermark_type'] ); ?>>
								Текст
							</label>
						</td>
					</tr>
					<tr class="site-watermark-image-row">
						<th scope="row">Файл водяного знака</th>
						<td>
							<input type="hidden" id="site-watermark-attachment" name="site_watermark_settings[watermark_attachment]" value="<?php echo esc_attr( $watermark_image_id ); ?>">
							<div id="site-watermark-preview" style="margin-bottom:12px;">
								<?php if ( $watermark_image ) : ?>
									<img src="<?php echo esc_url( $watermark_image ); ?>" alt="" style="max-width:280px;height:auto;border:1px solid #ccd0d4;padding:6px;background:#fff;">
								<?php else : ?>
									<div style="padding:10px 12px;border:1px dashed #ccd0d4;display:inline-block;">PNG с прозрачностью работает лучше всего</div>
								<?php endif; ?>
							</div>
							<button type="button" class="button" id="site-select-watermark">Выбрать изображение</button>
							<button type="button" class="button button-link-delete" id="site-remove-watermark">Удалить</button>
						</td>
					</tr>
					<tr class="site-watermark-text-row">
						<th scope="row">Текст водяного знака</th>
						<td>
							<input type="text" class="regular-text" name="site_watermark_settings[watermark_text]" value="<?php echo esc_attr( $settings['watermark_text'] ); ?>" placeholder="Например: example.com">
						</td>
					</tr>
					<tr class="site-watermark-text-row">
						<th scope="row">Цвет текста</th>
						<td>
							<input type="text" class="regular-text" name="site_watermark_settings[text_color]" value="<?php echo esc_attr( $settings['text_color'] ); ?>" placeholder="#FFFFFF">
						</td>
					</tr>
					<tr class="site-watermark-text-row">
						<th scope="row">Прозрачность текста</th>
						<td>
							<input type="number" min="5" max="100" name="site_watermark_settings[text_opacity]" value="<?php echo esc_attr( $settings['text_opacity'] ); ?>"> %
						</td>
					</tr>
					<tr>
						<th scope="row">Позиция</th>
						<td>
							<select name="site_watermark_settings[position]">
								<option value="top-left" <?php selected( $settings['position'], 'top-left' ); ?>>Сверху слева</option>
								<option value="top-right" <?php selected( $settings['position'], 'top-right' ); ?>>Сверху справа</option>
								<option value="center" <?php selected( $settings['position'], 'center' ); ?>>По центру</option>
								<option value="bottom-left" <?php selected( $settings['position'], 'bottom-left' ); ?>>Снизу слева</option>
								<option value="bottom-right" <?php selected( $settings['position'], 'bottom-right' ); ?>>Снизу справа</option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row">Размер</th>
						<td>
							<input type="number" min="5" max="60" name="site_watermark_settings[scale_percent]" value="<?php echo esc_attr( $settings['scale_percent'] ); ?>"> % от ширины изображения
						</td>
					</tr>
					<tr>
						<th scope="row">Отступ от края</th>
						<td>
							<input type="number" min="0" max="100" name="site_watermark_settings[margin]" value="<?php echo esc_attr( $settings['margin'] ); ?>"> px
						</td>
					</tr>
				</tbody>
			</table>
			<?php submit_button( 'Сохранить настройки' ); ?>
		</form>

		<hr>

		<h2>Массовая обработка уже загруженных фото</h2>
		<p>Кнопка ниже заранее создаст водяные копии для миниатюр и картинок из контента у всех записей выбранных типов. Оригиналы файлов не изменяются.</p>
		<form method="post">
			<?php wp_nonce_field( 'site_watermark_bulk_action', 'site_watermark_bulk_nonce' ); ?>
			<input type="hidden" name="site_watermark_bulk_generate" value="1">
			<?php submit_button( 'Подготовить водяные копии', 'secondary', 'submit', false ); ?>
		</form>
	</div>

	<script>
	jQuery(function($) {
		var frame;

		function toggleWatermarkMode() {
			var type = $('input[name="site_watermark_settings[watermark_type]"]:checked').val();
			$('.site-watermark-image-row').toggle(type === 'image');
			$('.site-watermark-text-row').toggle(type === 'text');
		}

		$('#site-select-watermark').on('click', function(e) {
			e.preventDefault();

			if (frame) {
				frame.open();
				return;
			}

			frame = wp.media({
				title: 'Выберите изображение водяного знака',
				button: { text: 'Использовать изображение' },
				library: { type: 'image' },
				multiple: false
			});

			frame.on('select', function() {
				var attachment = frame.state().get('selection').first().toJSON();
				$('#site-watermark-attachment').val(attachment.id);
				$('#site-watermark-preview').html(
					'<img src="' + attachment.url + '" alt="" style="max-width:280px;height:auto;border:1px solid #ccd0d4;padding:6px;background:#fff;">'
				);
			});

			frame.open();
		});

		$('#site-remove-watermark').on('click', function(e) {
			e.preventDefault();
			$('#site-watermark-attachment').val('');
			$('#site-watermark-preview').html(
				'<div style="padding:10px 12px;border:1px dashed #ccd0d4;display:inline-block;">PNG с прозрачностью работает лучше всего</div>'
			);
		});

		$('input[name="site_watermark_settings[watermark_type]"]').on('change', toggleWatermarkMode);
		toggleWatermarkMode();
	});
	</script>
	<?php
}

function site_watermark_admin_assets( $hook ) {
	if ( 'settings_page_site-watermark' !== $hook ) {
		return;
	}

	wp_enqueue_media();
}
add_action( 'admin_enqueue_scripts', 'site_watermark_admin_assets' );

function site_watermark_get_current_post_type() {
	global $post;

	if ( $post instanceof WP_Post ) {
		return $post->post_type;
	}

	if ( is_singular() ) {
		return get_post_type();
	}

	return '';
}

function site_watermark_is_current_context_supported() {
	if ( is_admin() || ! site_watermark_has_valid_source() ) {
		return false;
	}

	$post_type = site_watermark_get_current_post_type();

	return $post_type && site_watermark_is_post_type_selected( $post_type );
}

function site_watermark_filter_thumbnail_html( $html, $post_id ) {
	if ( is_admin() || ! site_watermark_has_valid_source() || ! site_watermark_is_post_type_selected( get_post_type( $post_id ) ) ) {
		return $html;
	}

	return site_watermark_replace_image_tag_urls( $html );
}
add_filter( 'post_thumbnail_html', 'site_watermark_filter_thumbnail_html', 10, 2 );

function site_watermark_filter_content_images( $content ) {
	if ( ! site_watermark_is_current_context_supported() ) {
		return $content;
	}

	return preg_replace_callback(
		'/<img[^>]+>/i',
		function( $matches ) {
			return site_watermark_replace_image_tag_urls( $matches[0] );
		},
		$content
	);
}
add_filter( 'the_content', 'site_watermark_filter_content_images', 20 );

function site_watermark_replace_image_tag_urls( $html ) {
	if ( ! preg_match( '/\ssrc=["\']([^"\']+)["\']/i', $html, $src_match ) ) {
		return $html;
	}

	$original_url  = html_entity_decode( $src_match[1] );
	$attachment_id = site_watermark_resolve_attachment_id( $html, $original_url );

	if ( ! $attachment_id ) {
		return $html;
	}

	$watermarked_url = site_watermark_get_image_url( $attachment_id, 'full', $original_url );

	if ( ! $watermarked_url ) {
		return $html;
	}

	$html = site_watermark_replace_single_image_url_in_html( $html, $watermarked_url );

	if ( preg_match( '/\ssrcset=["\']([^"\']+)["\']/i', $html, $srcset_match ) ) {
		$items      = array_map( 'trim', explode( ',', html_entity_decode( $srcset_match[1] ) ) );
		$new_srcset = array();

		foreach ( $items as $item ) {
			$parts = preg_split( '/\s+/', trim( $item ), 2 );
			$url   = isset( $parts[0] ) ? $parts[0] : '';
			$desc  = isset( $parts[1] ) ? $parts[1] : '';

			if ( ! $url ) {
				continue;
			}

			$candidate = site_watermark_get_image_url( $attachment_id, 'full', $url );
			$new_srcset[] = trim( ( $candidate ? $candidate : $url ) . ' ' . $desc );
		}

		if ( ! empty( $new_srcset ) ) {
			$html = preg_replace(
				'/\ssrcset=["\']([^"\']+)["\']/i',
				' srcset="' . esc_attr( implode( ', ', $new_srcset ) ) . '"',
				$html
			);
		}
	}

	return $html;
}

function site_watermark_resolve_attachment_id( $html, $url ) {
	$attachment_id = attachment_url_to_postid( $url );

	if ( $attachment_id ) {
		return (int) $attachment_id;
	}

	if ( preg_match( '/wp-image-([0-9]+)/i', $html, $class_match ) ) {
		return (int) $class_match[1];
	}

	if ( preg_match( '/\sdata-id=["\']([0-9]+)["\']/i', $html, $data_match ) ) {
		return (int) $data_match[1];
	}

	$normalized_url = site_watermark_normalize_image_url( $url );

	if ( $normalized_url && $normalized_url !== $url ) {
		$attachment_id = attachment_url_to_postid( $normalized_url );
		if ( $attachment_id ) {
			return (int) $attachment_id;
		}
	}

	return 0;
}

function site_watermark_normalize_image_url( $url ) {
	$url = preg_replace( '/-\d+x\d+(?=\.[a-zA-Z0-9]+($|\?))/i', '', $url );
	$url = preg_replace( '/-scaled(?=\.[a-zA-Z0-9]+($|\?))/i', '', $url );

	return $url;
}

function site_watermark_replace_single_image_url_in_html( $html, $new_url ) {
	return preg_replace(
		'/\ssrc=["\']([^"\']+)["\']/i',
		' src="' . esc_url( $new_url ) . '"',
		$html,
		1
	);
}

function site_watermark_get_image_url( $attachment_id, $size = 'full', $preferred_url = '' ) {
	if ( ! site_watermark_has_valid_source() || ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}

	$source = site_watermark_get_attachment_source( $attachment_id, $size, $preferred_url );

	if ( empty( $source['path'] ) || empty( $source['url'] ) || ! file_exists( $source['path'] ) ) {
		return false;
	}

	$upload_dir   = wp_upload_dir();
	$base_dir     = trailingslashit( $upload_dir['basedir'] ) . 'watermarks/' . site_watermark_settings_hash() . '/';
	$base_url     = trailingslashit( $upload_dir['baseurl'] ) . 'watermarks/' . site_watermark_settings_hash() . '/';
	$relative_dir = ltrim( str_replace( wp_normalize_path( $upload_dir['basedir'] ), '', wp_normalize_path( dirname( $source['path'] ) ) ), '/' );
	$target_dir   = trailingslashit( $base_dir . $relative_dir );
	$target_url   = trailingslashit( $base_url . $relative_dir );
	$target_file  = wp_basename( $source['path'] );
	$target_path  = $target_dir . $target_file;
	$target_link  = $target_url . rawurlencode( $target_file );

	if ( file_exists( $target_path ) ) {
		return $target_link;
	}

	wp_mkdir_p( $target_dir );

	if ( ! site_watermark_apply_to_file( $source['path'], $target_path ) ) {
		return false;
	}

	return file_exists( $target_path ) ? $target_link : false;
}

function site_watermark_get_attachment_source( $attachment_id, $size = 'full', $preferred_url = '' ) {
	if ( $preferred_url ) {
		$preferred_path = site_watermark_url_to_path( $preferred_url );
		if ( $preferred_path && file_exists( $preferred_path ) ) {
			return array(
				'url'  => $preferred_url,
				'path' => $preferred_path,
			);
		}
	}

	$image = wp_get_attachment_image_src( $attachment_id, $size );

	if ( is_array( $image ) && ! empty( $image[0] ) ) {
		$image_path = site_watermark_url_to_path( $image[0] );
		if ( $image_path && file_exists( $image_path ) ) {
			return array(
				'url'  => $image[0],
				'path' => $image_path,
			);
		}
	}

	return array(
		'url'  => wp_get_attachment_url( $attachment_id ),
		'path' => get_attached_file( $attachment_id ),
	);
}

function site_watermark_url_to_path( $url ) {
	$upload_dir = wp_upload_dir();
	$baseurl    = trailingslashit( $upload_dir['baseurl'] );

	if ( 0 !== strpos( $url, $baseurl ) ) {
		return false;
	}

	$relative = ltrim( substr( $url, strlen( $baseurl ) ), '/' );

	return wp_normalize_path( trailingslashit( $upload_dir['basedir'] ) . $relative );
}

function site_watermark_apply_to_file( $source_path, $target_path ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return false;
	}

	$source = site_watermark_load_image_resource( $source_path );

	if ( ! $source ) {
		return false;
	}

	$source_width  = imagesx( $source );
	$source_height = imagesy( $source );
	$watermark     = site_watermark_create_overlay( $source_width, $source_height );

	if ( ! $watermark ) {
		imagedestroy( $source );
		return false;
	}

	$overlay_width  = imagesx( $watermark );
	$overlay_height = imagesy( $watermark );
	list( $dest_x, $dest_y ) = site_watermark_calculate_position(
		$source_width,
		$source_height,
		$overlay_width,
		$overlay_height
	);

	imagealphablending( $source, true );
	imagesavealpha( $source, true );
	imagecopy( $source, $watermark, $dest_x, $dest_y, 0, 0, $overlay_width, $overlay_height );

	$saved = site_watermark_save_image_resource( $source, $target_path, $source_path );

	imagedestroy( $source );
	imagedestroy( $watermark );

	return $saved;
}

function site_watermark_create_overlay( $source_width, $source_height ) {
	$settings = site_watermark_get_settings();

	if ( 'text' === $settings['watermark_type'] ) {
		return site_watermark_create_text_overlay( $source_width, $source_height, $settings );
	}

	return site_watermark_create_image_overlay( $source_width, $source_height, $settings );
}

function site_watermark_create_image_overlay( $source_width, $source_height, $settings ) {
	$watermark_path = get_attached_file( $settings['watermark_attachment'] );

	if ( empty( $watermark_path ) || ! file_exists( $watermark_path ) ) {
		return false;
	}

	$stamp = site_watermark_load_image_resource( $watermark_path );

	if ( ! $stamp ) {
		return false;
	}

	$stamp_width   = imagesx( $stamp );
	$stamp_height  = imagesy( $stamp );
	$max_width     = max( 1, (int) floor( $source_width * ( (int) $settings['scale_percent'] / 100 ) ) );
	$scale_ratio   = min( 1, $max_width / max( 1, $stamp_width ) );
	$resized_width = max( 1, (int) floor( $stamp_width * $scale_ratio ) );
	$resized_height = max( 1, (int) floor( $stamp_height * $scale_ratio ) );

	$resized = imagecreatetruecolor( $resized_width, $resized_height );
	imagealphablending( $resized, false );
	imagesavealpha( $resized, true );
	$transparent = imagecolorallocatealpha( $resized, 0, 0, 0, 127 );
	imagefilledrectangle( $resized, 0, 0, $resized_width, $resized_height, $transparent );
	imagecopyresampled( $resized, $stamp, 0, 0, 0, 0, $resized_width, $resized_height, $stamp_width, $stamp_height );

	imagedestroy( $stamp );

	return $resized;
}

function site_watermark_create_text_overlay( $source_width, $source_height, $settings ) {
	$text = trim( (string) $settings['watermark_text'] );

	if ( '' === $text ) {
		return false;
	}

	$font_path = site_watermark_find_font_file();

	if ( $font_path && function_exists( 'imagettfbbox' ) && function_exists( 'imagettftext' ) ) {
		return site_watermark_create_ttf_text_overlay( $text, $source_width, $source_height, $settings, $font_path );
	}

	return site_watermark_create_builtin_text_overlay( $text, $source_width, $source_height, $settings );
}

function site_watermark_find_font_file() {
	$candidates = array(
		get_template_directory() . '/fonts/arial.ttf',
		'C:/Windows/Fonts/arial.ttf',
		'C:/Windows/Fonts/segoeui.ttf',
		'C:/Windows/Fonts/tahoma.ttf',
	);

	foreach ( $candidates as $candidate ) {
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
	}

	return false;
}

function site_watermark_create_ttf_text_overlay( $text, $source_width, $source_height, $settings, $font_path ) {
	$font_size = max( 12, (int) floor( $source_width * ( (int) $settings['scale_percent'] / 100 ) / 4 ) );
	$angle     = 0;
	$bbox      = imagettfbbox( $font_size, $angle, $font_path, $text );

	if ( ! is_array( $bbox ) ) {
		return false;
	}

	$text_width  = max( 1, abs( $bbox[2] - $bbox[0] ) );
	$text_height = max( 1, abs( $bbox[7] - $bbox[1] ) );
	$canvas      = imagecreatetruecolor( $text_width + 20, $text_height + 20 );

	imagealphablending( $canvas, false );
	imagesavealpha( $canvas, true );
	$transparent = imagecolorallocatealpha( $canvas, 0, 0, 0, 127 );
	imagefilledrectangle( $canvas, 0, 0, imagesx( $canvas ), imagesy( $canvas ), $transparent );

	list( $r, $g, $b ) = site_watermark_hex_to_rgb( $settings['text_color'] );
	$alpha             = 127 - (int) round( 127 * ( (int) $settings['text_opacity'] / 100 ) );
	$color             = imagecolorallocatealpha( $canvas, $r, $g, $b, max( 0, min( 127, $alpha ) ) );

	imagettftext( $canvas, $font_size, $angle, 10, $text_height + 10, $color, $font_path, $text );

	return $canvas;
}

function site_watermark_create_builtin_text_overlay( $text, $source_width, $source_height, $settings ) {
	$font         = 5;
	$text_width   = imagefontwidth( $font ) * strlen( $text );
	$text_height  = imagefontheight( $font );
	$base_canvas  = imagecreatetruecolor( $text_width + 8, $text_height + 8 );
	$scale_target = max( $text_width + 8, (int) floor( $source_width * ( (int) $settings['scale_percent'] / 100 ) ) );
	$ratio        = $scale_target / max( 1, $text_width + 8 );
	$final_width  = max( 1, (int) floor( ( $text_width + 8 ) * $ratio ) );
	$final_height = max( 1, (int) floor( ( $text_height + 8 ) * $ratio ) );

	imagealphablending( $base_canvas, false );
	imagesavealpha( $base_canvas, true );
	$transparent = imagecolorallocatealpha( $base_canvas, 0, 0, 0, 127 );
	imagefilledrectangle( $base_canvas, 0, 0, imagesx( $base_canvas ), imagesy( $base_canvas ), $transparent );

	list( $r, $g, $b ) = site_watermark_hex_to_rgb( $settings['text_color'] );
	$alpha             = 127 - (int) round( 127 * ( (int) $settings['text_opacity'] / 100 ) );
	$color             = imagecolorallocatealpha( $base_canvas, $r, $g, $b, max( 0, min( 127, $alpha ) ) );

	imagestring( $base_canvas, $font, 4, 4, $text, $color );

	$resized = imagecreatetruecolor( $final_width, $final_height );
	imagealphablending( $resized, false );
	imagesavealpha( $resized, true );
	$transparent_resized = imagecolorallocatealpha( $resized, 0, 0, 0, 127 );
	imagefilledrectangle( $resized, 0, 0, $final_width, $final_height, $transparent_resized );
	imagecopyresampled( $resized, $base_canvas, 0, 0, 0, 0, $final_width, $final_height, imagesx( $base_canvas ), imagesy( $base_canvas ) );

	imagedestroy( $base_canvas );

	return $resized;
}

function site_watermark_hex_to_rgb( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	return array(
		hexdec( substr( $hex, 0, 2 ) ),
		hexdec( substr( $hex, 2, 2 ) ),
		hexdec( substr( $hex, 4, 2 ) ),
	);
}

function site_watermark_calculate_position( $image_width, $image_height, $overlay_width, $overlay_height ) {
	$settings = site_watermark_get_settings();
	$margin   = (int) $settings['margin'];

	switch ( $settings['position'] ) {
		case 'top-left':
			return array( $margin, $margin );

		case 'top-right':
			return array( max( 0, $image_width - $overlay_width - $margin ), $margin );

		case 'bottom-left':
			return array( $margin, max( 0, $image_height - $overlay_height - $margin ) );

		case 'center':
			return array(
				max( 0, (int) floor( ( $image_width - $overlay_width ) / 2 ) ),
				max( 0, (int) floor( ( $image_height - $overlay_height ) / 2 ) )
			);

		case 'bottom-right':
		default:
			return array(
				max( 0, $image_width - $overlay_width - $margin ),
				max( 0, $image_height - $overlay_height - $margin )
			);
	}
}

function site_watermark_load_image_resource( $path ) {
	$mime = wp_check_filetype( $path );
	$type = isset( $mime['type'] ) ? $mime['type'] : '';

	switch ( $type ) {
		case 'image/jpeg':
			return imagecreatefromjpeg( $path );

		case 'image/png':
			return imagecreatefrompng( $path );

		case 'image/gif':
			return imagecreatefromgif( $path );

		case 'image/webp':
			return function_exists( 'imagecreatefromwebp' ) ? imagecreatefromwebp( $path ) : false;
	}

	return false;
}

function site_watermark_save_image_resource( $resource, $target_path, $original_path ) {
	$mime = wp_check_filetype( $original_path );
	$type = isset( $mime['type'] ) ? $mime['type'] : '';

	switch ( $type ) {
		case 'image/jpeg':
			return imagejpeg( $resource, $target_path, 90 );

		case 'image/png':
			return imagepng( $resource, $target_path, 6 );

		case 'image/gif':
			return imagegif( $resource, $target_path );

		case 'image/webp':
			return function_exists( 'imagewebp' ) ? imagewebp( $resource, $target_path, 90 ) : false;
	}

	return false;
}

function site_watermark_collect_attachment_ids( $post_id ) {
	$attachment_ids = array();
	$thumbnail_id   = get_post_thumbnail_id( $post_id );
	$post           = get_post( $post_id );

	if ( $thumbnail_id ) {
		$attachment_ids[] = (int) $thumbnail_id;
	}

	if ( $post instanceof WP_Post && ! empty( $post->post_content ) ) {
		preg_match_all( '/<img[^>]+src=["\']([^"\']+)["\']/i', $post->post_content, $matches );

		if ( ! empty( $matches[1] ) ) {
			foreach ( $matches[1] as $url ) {
				$attachment_id = attachment_url_to_postid( html_entity_decode( $url ) );
				if ( $attachment_id ) {
					$attachment_ids[] = (int) $attachment_id;
				}
			}
		}
	}

	return array_values( array_unique( array_filter( $attachment_ids ) ) );
}

function site_watermark_bulk_generate() {
	if ( ! site_watermark_has_valid_source() ) {
		return 0;
	}

	$processed  = 0;
	$post_types = site_watermark_get_selected_post_types();

	foreach ( $post_types as $post_type ) {
		$posts = get_posts(
			array(
				'post_type'      => $post_type,
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);

		foreach ( $posts as $post_id ) {
			$attachment_ids = site_watermark_collect_attachment_ids( $post_id );

			foreach ( $attachment_ids as $attachment_id ) {
				if ( site_watermark_get_image_url( $attachment_id, 'full' ) ) {
					$processed++;
				}

				$metadata = wp_get_attachment_metadata( $attachment_id );
				if ( ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
					foreach ( array_keys( $metadata['sizes'] ) as $size_name ) {
						site_watermark_get_image_url( $attachment_id, $size_name );
					}
				}
			}
		}
	}

	return $processed;
}

function site_watermark_get_url_by_original( $url ) {
	if ( ! site_watermark_is_current_context_supported() ) {
		return $url;
	}

	$attachment_id = attachment_url_to_postid( $url );

	if ( ! $attachment_id ) {
		return $url;
	}

	$watermarked_url = site_watermark_get_image_url( $attachment_id, 'full', $url );

	return $watermarked_url ? $watermarked_url : $url;
}

function site_watermark_plugin_action_links( $links ) {
	$settings_url  = admin_url( 'options-general.php?page=site-watermark' );
	$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'Настройки', 'watermark-manager' ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'site_watermark_plugin_action_links' );
