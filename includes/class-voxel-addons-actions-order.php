<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Voxel_Addons_Actions_Order extends \Voxel\Post_Types\Order_By\Base_Search_Order {
	protected $props = [
		'type' => 'voxel-addons-actions',
		'order' => 'DESC',
	];

	public function get_label(): string {
		return __( 'Post likes', 'voxel-addons-actions' );
	}

	public function get_models(): array {
		return [
			'order' => $this->get_order_model(),
		];
	}

	public function query( \Voxel\Post_Types\Index_Query $query, array $args, array $clause_args ): void {
		global $wpdb;

		$table = $wpdb->prefix . 'voxel_likes';
		$alias = 'voxel_likes_order';
		$order = $this->props['order'] === 'ASC' ? 'ASC' : 'DESC';

		$query->join(
			sprintf(
				'LEFT JOIN (SELECT post_id, COUNT(*) AS like_count FROM `%s` WHERE liked = 1 GROUP BY post_id) AS %s ON `%s`.post_id = %s.post_id',
				esc_sql( $table ),
				esc_sql( $alias ),
				$query->table->get_escaped_name(),
				esc_sql( $alias )
			)
		);

		$query->select( sprintf( 'COALESCE(%s.like_count, 0) AS voxel_like_count', esc_sql( $alias ) ) );
		$query->orderby( sprintf( 'voxel_like_count %s', $order ) );
	}
}
