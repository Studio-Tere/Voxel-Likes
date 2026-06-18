<?php
/**
 * Plugin Name: Voxel Likes
 * Description: Adds reusable IP-based likes for Voxel posts, Voxel Actions, dynamic tags, and listing order filters.
 * Version: 0.2.2
 * Author: Studio Tere
 * Author URI: https://studiotere.io
 * Plugin URI: https://studiotere.io
 * Text Domain: voxel-likes
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Voxel_Likes_Plugin {
	const VERSION = '0.2.2';
	const PLUGIN_SLUG = 'voxel-likes';
	const ACTION_TYPE = 'voxel_like';
	const LEGACY_ACTION_TYPE = 'publicacion_like';
	const AJAX_ACTION = 'voxel_likes.toggle';
	const LEGACY_AJAX_ACTION = 'publicacion_likes.toggle';
	const NONCE_ACTION = 'voxel_likes_toggle';
	const LEGACY_NONCE_ACTION = 'publicacion_likes_toggle';
	const ORDER_KEY = 'most-liked';
	const ORDER_TYPE = 'voxel-likes';
	const LEGACY_ORDER_TYPE = 'publicacion-likes';

	private static $instance = null;

	public static function instance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
		add_filter( 'plugin_row_meta', [ $this, 'add_plugin_details_link' ], 10, 2 );
		add_filter( 'plugins_api', [ $this, 'plugin_information' ], 10, 3 );
		add_filter( 'voxel/advanced-list/actions', [ $this, 'register_voxel_action' ] );
		add_action( 'voxel/advanced-list/action:' . self::ACTION_TYPE, [ $this, 'render_voxel_action' ], 10, 2 );
		add_action( 'voxel/advanced-list/action:' . self::LEGACY_ACTION_TYPE, [ $this, 'render_voxel_action' ], 10, 2 );
		add_filter( 'voxel/orderby-types', [ $this, 'register_voxel_orderby_type' ] );
		add_filter( 'voxel/dynamic-data/groups/post/properties', [ $this, 'register_like_count_tag' ], 10, 2 );
		add_filter( 'voxel/dynamic-data/groups/simple-post/properties', [ $this, 'register_like_count_tag' ], 10, 2 );
		add_action( 'admin_init', [ $this, 'maybe_refresh_voxel_search_config' ] );

		add_action( 'voxel_ajax_' . self::AJAX_ACTION, [ $this, 'handle_toggle_request' ] );
		add_action( 'voxel_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'handle_toggle_request' ] );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, [ $this, 'handle_toggle_request' ] );
		add_action( 'wp_ajax_nopriv_' . self::AJAX_ACTION, [ $this, 'handle_toggle_request' ] );

		add_action( 'voxel_ajax_' . self::LEGACY_AJAX_ACTION, [ $this, 'handle_toggle_request' ] );
		add_action( 'voxel_ajax_nopriv_' . self::LEGACY_AJAX_ACTION, [ $this, 'handle_toggle_request' ] );
		add_action( 'wp_ajax_' . self::LEGACY_AJAX_ACTION, [ $this, 'handle_toggle_request' ] );
		add_action( 'wp_ajax_nopriv_' . self::LEGACY_AJAX_ACTION, [ $this, 'handle_toggle_request' ] );

		add_action( 'before_delete_post', [ $this, 'delete_post_likes' ], 10, 2 );
		add_action( 'wp_enqueue_scripts', [ $this, 'register_assets' ] );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain( 'voxel-likes', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	public function add_plugin_details_link( array $links, string $file ): array {
		if ( $file !== plugin_basename( __FILE__ ) ) {
			return $links;
		}

		$url = add_query_arg(
			[
				'tab' => 'plugin-information',
				'plugin' => self::PLUGIN_SLUG,
				'TB_iframe' => 'true',
				'width' => 772,
				'height' => 600,
			],
			self_admin_url( 'plugin-install.php' )
		);

		$links[] = sprintf(
			'<a href="%s" class="thickbox open-plugin-details-modal" aria-label="%s">%s</a>',
			esc_url( $url ),
			esc_attr__( 'Ver detalles de Voxel Likes', 'voxel-likes' ),
			esc_html__( 'Ver detalles', 'voxel-likes' )
		);

		return $links;
	}

	public function plugin_information( $result, string $action, object $args ) {
		if ( $action !== 'plugin_information' || ( $args->slug ?? '' ) !== self::PLUGIN_SLUG ) {
			return $result;
		}

		return (object) [
			'name' => __( 'Voxel Likes', 'voxel-likes' ),
			'slug' => self::PLUGIN_SLUG,
			'version' => self::VERSION,
			'author' => '<a href="https://studiotere.io">Studio Tere</a>',
			'author_profile' => 'https://studiotere.io',
			'homepage' => 'https://studiotere.io',
			'requires' => '6.0',
			'tested' => get_bloginfo( 'version' ),
			'requires_php' => '8.0',
			'sections' => [
				'description' => sprintf(
					'<p>%s</p><ul><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ul>',
					esc_html__( 'Adds reusable IP-based likes for Voxel posts, Voxel Actions, dynamic tags, and listing order filters.', 'voxel-likes' ),
					esc_html__( 'Stores likes in a dedicated WordPress database table.', 'voxel-likes' ),
					esc_html__( 'Uses an HMAC hash of the visitor IP instead of storing the raw IP address.', 'voxel-likes' ),
					esc_html__( 'Keeps unlike rows with liked = 0 for state history.', 'voxel-likes' ),
					esc_html__( 'Adds a Voxel search order for posts with the most likes.', 'voxel-likes' )
				),
				'actualizaciones_de_version' => sprintf(
					'<h4>%s</h4><ul><li>%s</li></ul><h4>%s</h4><ul><li>%s</li><li>%s</li></ul><h4>%s</h4><ul><li>%s</li><li>%s</li><li>%s</li></ul><h4>%s</h4><ul><li>%s</li></ul><h4>%s</h4><ul><li>%s</li><li>%s</li><li>%s</li><li>%s</li></ul>',
					esc_html__( 'Version 0.2.2', 'voxel-likes' ),
					esc_html__( 'Like counter dynamic tags now update through AJAX after toggling a like.', 'voxel-likes' ),
					esc_html__( 'Version 0.2.1', 'voxel-likes' ),
					esc_html__( 'Added the Likes dynamic tag group with total and count values.', 'voxel-likes' ),
					esc_html__( 'Removed the automatic visible text and count from the Like action.', 'voxel-likes' ),
					esc_html__( 'Version 0.2.0', 'voxel-likes' ),
					esc_html__( 'Renamed the plugin to Voxel Likes.', 'voxel-likes' ),
					esc_html__( 'Likes now work with any valid Voxel post type.', 'voxel-likes' ),
					esc_html__( 'The Most liked order is installed across Voxel post type filters.', 'voxel-likes' ),
					esc_html__( 'Version 0.1.1', 'voxel-likes' ),
					esc_html__( 'Added a Voxel dynamic tag for the like counter.', 'voxel-likes' ),
					esc_html__( 'Version 0.1.0', 'voxel-likes' ),
					esc_html__( 'Initial plugin release.', 'voxel-likes' ),
					esc_html__( 'Added the Like action for the Voxel Actions widget.', 'voxel-likes' ),
					esc_html__( 'Added the likes table with hashed IP state tracking.', 'voxel-likes' ),
					esc_html__( 'Added automatic cleanup when a post is permanently deleted.', 'voxel-likes' )
				),
			],
		];
	}

	public static function activate(): void {
		self::create_table();
		self::migrate_legacy_table();
		self::install_voxel_search_config();
	}

	public static function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table = self::table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			post_id BIGINT(20) UNSIGNED NOT NULL,
			ip_hash CHAR(64) NOT NULL,
			liked TINYINT(1) NOT NULL DEFAULT 1,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY post_ip (post_id, ip_hash),
			KEY post_liked (post_id, liked),
			KEY updated_at (updated_at)
		) {$charset_collate};";

		dbDelta( $sql );
	}

	public static function migrate_legacy_table(): void {
		global $wpdb;

		$legacy_table = $wpdb->prefix . 'publicacion_likes';
		$new_table = self::table_name();

		if ( $legacy_table === $new_table || $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy_table ) ) !== $legacy_table ) {
			return;
		}

		$wpdb->query(
			"INSERT IGNORE INTO {$new_table} (post_id, ip_hash, liked, created_at, updated_at)
			SELECT post_id, ip_hash, liked, created_at, updated_at FROM {$legacy_table}"
		);
	}

	public static function install_voxel_search_config(): void {
		$raw = get_option( 'voxel:post_types', '' );
		$config = json_decode( $raw, true );
		if ( ! is_array( $config ) ) {
			return;
		}

		$changed = false;
		foreach ( $config as $post_type_key => &$post_type_config ) {
			if ( ! is_string( $post_type_key ) || ! is_array( $post_type_config ) ) {
				continue;
			}

			$changed = self::install_post_type_search_config( $post_type_config ) || $changed;
		}
		unset( $post_type_config );

		if ( $changed ) {
			update_option( 'voxel:post_types', wp_json_encode( $config ) );
		}
	}

	public function maybe_refresh_voxel_search_config(): void {
		self::install_voxel_search_config();
	}

	private static function install_post_type_search_config( array &$post_type_config ): bool {
		$changed = false;

		if ( ! isset( $post_type_config['search'] ) || ! is_array( $post_type_config['search'] ) ) {
			$post_type_config['search'] = [];
			$changed = true;
		}

		$search = &$post_type_config['search'];

		if ( ! isset( $search['filters'] ) || ! is_array( $search['filters'] ) ) {
			$search['filters'] = [];
			$changed = true;
		}

		$has_order_filter = false;
		foreach ( $search['filters'] as $index => $filter ) {
			if ( is_array( $filter ) && ( $filter['type'] ?? '' ) === 'order-by' ) {
				$has_order_filter = true;
				if ( empty( $filter['key'] ) ) {
					$search['filters'][ $index ]['key'] = 'sort';
					$changed = true;
				}
				break;
			}
		}

		if ( ! $has_order_filter ) {
			$search['filters'][] = [
				'type' => 'order-by',
				'label' => __( 'Ordenar', 'voxel-likes' ),
				'placeholder' => __( 'Ordenar', 'voxel-likes' ),
				'key' => 'sort',
				'singular' => true,
			];
			$changed = true;
		}

		if ( ! isset( $search['order'] ) || ! is_array( $search['order'] ) ) {
			$search['order'] = [];
			$changed = true;
		}

		$has_likes_order = false;
		foreach ( $search['order'] as $index => $order ) {
			if ( ! is_array( $order ) ) {
				continue;
			}

			if ( ( $order['key'] ?? '' ) === self::ORDER_KEY ) {
				$has_likes_order = true;
				foreach ( (array) ( $order['clauses'] ?? [] ) as $clause_index => $clause ) {
					if ( is_array( $clause ) && ( $clause['type'] ?? '' ) === self::LEGACY_ORDER_TYPE ) {
						$search['order'][ $index ]['clauses'][ $clause_index ]['type'] = self::ORDER_TYPE;
						$changed = true;
					}
				}
				break;
			}
		}

		if ( ! $has_likes_order ) {
			$search['order'][] = [
				'key' => self::ORDER_KEY,
				'label' => __( 'Mas likes', 'voxel-likes' ),
				'placeholder' => __( 'Mas likes', 'voxel-likes' ),
				'icon' => '',
				'clauses' => [
					[
						'type' => self::ORDER_TYPE,
						'order' => 'DESC',
					],
				],
			];
			$changed = true;
		}

		return $changed;
	}

	public function register_voxel_action( array $actions ): array {
		$actions[ self::ACTION_TYPE ] = __( 'Like', 'voxel-likes' );
		return $actions;
	}

	public function register_voxel_orderby_type( array $types ): array {
		if ( ! class_exists( '\Voxel\Post_Types\Order_By\Base_Search_Order' ) ) {
			return $types;
		}

		require_once __DIR__ . '/includes/class-voxel-likes-order.php';
		$types[ self::ORDER_TYPE ] = \Voxel_Likes_Order::class;
		$types[ self::LEGACY_ORDER_TYPE ] = \Voxel_Likes_Order::class;

		return $types;
	}

	public function register_like_count_tag( array $properties, $group ): array {
		if ( ! class_exists( '\Voxel\Dynamic_Data\Tag' ) ) {
			return $properties;
		}

		$post = $this->get_post_from_dynamic_group( $group );
		$plugin = $this;
		$post_id_cb = function() use ( $plugin, $post ) {
			$post_id = is_object( $post ) && method_exists( $post, 'get_id' ) ? absint( $post->get_id() ) : 0;
			return $post_id && $plugin->is_valid_likable_post( $post_id ) ? $post_id : 0;
		};
		$count_cb = function() use ( $plugin, $post_id_cb ) {
			$post_id = $post_id_cb();
			return $post_id ? $plugin->get_like_count( $post_id ) : 0;
		};
		$live_count_cb = function() use ( $plugin, $post_id_cb ) {
			$post_id = $post_id_cb();
			wp_enqueue_script( 'voxel-likes' );
			return $plugin->get_like_count_markup( $post_id, $post_id ? $plugin->get_like_count( $post_id ) : 0 );
		};

		$properties['likes'] = \Voxel\Dynamic_Data\Tag::Object(
			__( 'Likes', 'voxel-likes' ),
			__( 'Like data for this post.', 'voxel-likes' )
		)->properties( function() use ( $live_count_cb ) {
			return [
				'total' => \Voxel\Dynamic_Data\Tag::String(
					__( 'Total', 'voxel-likes' ),
					__( 'Live number of active likes for this post.', 'voxel-likes' )
				)->render( $live_count_cb ),
				'count' => \Voxel\Dynamic_Data\Tag::String(
					__( 'Count', 'voxel-likes' ),
					__( 'Live number of active likes for this post.', 'voxel-likes' )
				)->render( $live_count_cb ),
			];
		} );

		$properties['like_count'] = \Voxel\Dynamic_Data\Tag::Number(
			__( 'Like count', 'voxel-likes' ),
			__( 'Number of active likes for this post.', 'voxel-likes' )
		)->render( $count_cb )->hidden();

		return $properties;
	}

	public function register_assets(): void {
		wp_register_script(
			'voxel-likes',
			plugins_url( 'assets/voxel-likes.js', __FILE__ ),
			[],
			self::VERSION,
			true
		);
	}

	public function render_voxel_action( $widget, array $action ): void {
		$post_id = $this->get_current_post_id();
		if ( ! $post_id ) {
			return;
		}

		wp_enqueue_script( 'voxel-likes' );

		$is_liked = $this->is_liked_by_current_ip( $post_id );
		$label = $this->normalize_action_text( $action['ts_acw_initial_text'] ?? '' );
		$active_label = $this->normalize_action_text( $action['ts_acw_reveal_text'] ?? '' );
		$inactive_aria_label = $label !== '' ? $label : __( 'Like', 'voxel-likes' );
		$active_aria_label = $active_label !== '' ? $active_label : ( $label !== '' ? $label : __( 'Liked', 'voxel-likes' ) );
		$aria_label = $is_liked ? $active_aria_label : $inactive_aria_label;
		$tooltip_inactive = $action['ts_tooltip_text'] ?? '';
		$tooltip_active = $action['ts_acw_tooltip_text'] ?? '';
		$columns_class = method_exists( $widget, 'get_settings_for_display' ) ? $widget->get_settings_for_display( 'ts_al_columns_no' ) : '';
		$url = add_query_arg(
			[
				'vx' => 1,
				'action' => self::AJAX_ACTION,
				'post_id' => $post_id,
				'_wpnonce' => wp_create_nonce( self::NONCE_ACTION ),
			],
			home_url( '/' )
		);
		?>
		<li class="elementor-repeater-item-<?php echo esc_attr( $action['_id'] ?? '' ); ?> flexify ts-action <?php echo esc_attr( $columns_class ); ?>"
			<?php if ( ( $action['ts_enable_tooltip'] ?? '' ) === 'yes' && $tooltip_inactive !== '' ) : ?>
				tooltip-inactive="<?php echo esc_attr( $tooltip_inactive ); ?>"
			<?php endif; ?>
			<?php if ( ( $action['ts_acw_enable_tooltip'] ?? '' ) === 'yes' && $tooltip_active !== '' ) : ?>
				tooltip-active="<?php echo esc_attr( $tooltip_active ); ?>"
			<?php endif; ?>
		>
			<a
				href="<?php echo esc_url( $url ); ?>"
				rel="nofollow"
				role="button"
				aria-pressed="<?php echo $is_liked ? 'true' : 'false'; ?>"
				aria-label="<?php echo esc_attr( wp_strip_all_tags( $aria_label ) ); ?>"
				class="ts-action-con voxel-like-action <?php echo $is_liked ? 'active' : ''; ?>"
				data-liked="<?php echo $is_liked ? '1' : '0'; ?>"
				data-label="<?php echo esc_attr( wp_strip_all_tags( $inactive_aria_label ) ); ?>"
				data-active-label="<?php echo esc_attr( wp_strip_all_tags( $active_aria_label ) ); ?>"
			>
				<span class="ts-initial">
					<div class="ts-action-icon"><?php $this->render_action_icon( $action['ts_acw_initial_icon'] ?? [] ); ?></div>
					<?php if ( $label !== '' ) : ?>
						<span class="voxel-like-label"><?php echo wp_kses_post( $label ); ?></span>
					<?php endif; ?>
				</span>
				<span class="ts-reveal">
					<div class="ts-action-icon"><?php $this->render_action_icon( $action['ts_acw_reveal_icon'] ?? ( $action['ts_acw_initial_icon'] ?? [] ) ); ?></div>
					<?php if ( $active_label !== '' ) : ?>
						<span class="voxel-like-label"><?php echo wp_kses_post( $active_label ); ?></span>
					<?php elseif ( $label !== '' ) : ?>
						<span class="voxel-like-label"><?php echo wp_kses_post( $label ); ?></span>
					<?php endif; ?>
				</span>
			</a>
		</li>
		<?php
	}

	public function handle_toggle_request(): void {
		$post_id = absint( $_REQUEST['post_id'] ?? 0 );
		$nonce = sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ?? '' ) );

		if ( ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) && ! wp_verify_nonce( $nonce, self::LEGACY_NONCE_ACTION ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid request.', 'voxel-likes' ) ], 403 );
		}

		if ( ! $this->is_valid_likable_post( $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid post.', 'voxel-likes' ) ], 404 );
		}

		$liked = $this->toggle_like( $post_id );

		wp_send_json_success(
			[
				'post_id' => $post_id,
				'liked' => $liked,
				'count' => $this->get_like_count( $post_id ),
			]
		);
	}

	public function delete_post_likes( int $post_id, \WP_Post $post ): void {
		global $wpdb;
		$wpdb->delete( self::table_name(), [ 'post_id' => $post_id ], [ '%d' ] );
	}

	private function toggle_like( int $post_id ): bool {
		global $wpdb;

		$table = self::table_name();
		$ip_hash = $this->current_ip_hash();
		$now = current_time( 'mysql' );
		$current = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT liked FROM {$table} WHERE post_id = %d AND ip_hash = %s LIMIT 1",
				$post_id,
				$ip_hash
			)
		);

		if ( $current === null ) {
			$wpdb->insert(
				$table,
				[
					'post_id' => $post_id,
					'ip_hash' => $ip_hash,
					'liked' => 1,
					'created_at' => $now,
					'updated_at' => $now,
				],
				[ '%d', '%s', '%d', '%s', '%s' ]
			);

			return true;
		}

		$new_state = absint( $current ) === 1 ? 0 : 1;
		$wpdb->update(
			$table,
			[
				'liked' => $new_state,
				'updated_at' => $now,
			],
			[
				'post_id' => $post_id,
				'ip_hash' => $ip_hash,
			],
			[ '%d', '%s' ],
			[ '%d', '%s' ]
		);

		return $new_state === 1;
	}

	private function is_liked_by_current_ip( int $post_id ): bool {
		global $wpdb;

		return (bool) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT liked FROM ' . self::table_name() . ' WHERE post_id = %d AND ip_hash = %s LIMIT 1',
				$post_id,
				$this->current_ip_hash()
			)
		);
	}

	public function get_like_count( int $post_id ): int {
		global $wpdb;

		return absint(
			$wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(*) FROM ' . self::table_name() . ' WHERE post_id = %d AND liked = 1',
					$post_id
				)
			)
		);
	}

	public function get_like_count_markup( int $post_id, int $count ): string {
		return sprintf(
			'<span class="voxel-like-count" data-voxel-likes-count="1" data-post-id="%d">%d</span>',
			$post_id,
			$count
		);
	}

	private function get_current_post_id(): int {
		if ( function_exists( '\Voxel\get_current_post' ) ) {
			$current_post = \Voxel\get_current_post();
			if ( $current_post && method_exists( $current_post, 'get_id' ) ) {
				$post_id = absint( $current_post->get_id() );
				return $this->is_valid_likable_post( $post_id ) ? $post_id : 0;
			}
		}

		$post_id = absint( get_the_ID() );
		return $this->is_valid_likable_post( $post_id ) ? $post_id : 0;
	}

	private function get_post_from_dynamic_group( $group ) {
		if ( is_object( $group ) && method_exists( $group, 'get_post' ) ) {
			return $group->get_post();
		}

		if ( is_object( $group ) && isset( $group->post ) ) {
			return $group->post;
		}

		return null;
	}

	public function is_valid_likable_post( int $post_id ): bool {
		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( in_array( $post->post_type, [ 'revision', 'nav_menu_item', 'customize_changeset', 'oembed_cache' ], true ) ) {
			return false;
		}

		return ! in_array( $post->post_status, [ 'auto-draft', 'trash' ], true );
	}

	private function normalize_action_text( $text ): string {
		$text = is_scalar( $text ) ? trim( (string) $text ) : '';
		if ( $text === '' ) {
			return '';
		}

		$default_texts = array_filter( [
			'Action',
			__( 'Action', 'voxel-elementor' ),
		] );

		return in_array( $text, $default_texts, true ) ? '' : $text;
	}

	private function current_ip_hash(): string {
		$ip = $this->current_ip();
		return hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) );
	}

	private function current_ip(): string {
		$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
		$ip = is_string( $ip ) ? trim( $ip ) : '0.0.0.0';
		return apply_filters( 'voxel_likes/current_ip', $ip );
	}

	private function render_action_icon( $icon ): void {
		if ( function_exists( '\Voxel\render_icon' ) ) {
			\Voxel\render_icon( $icon );
		}
	}

	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'voxel_likes';
	}
}

register_activation_hook( __FILE__, [ 'Voxel_Likes_Plugin', 'activate' ] );
Voxel_Likes_Plugin::instance();
