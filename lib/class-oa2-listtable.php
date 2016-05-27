<?php
/**
 * OAuth 2.0 list table for listing consumers on admin
 *
 * Based on WP REST API - OAuth 1.0a Server (https://github.com/WP-API/OAuth1).
 * Used under GPL3 license.
 */
class OA2_ListTable extends WP_List_Table {

	public function prepare_items() {
		$paged = $this->get_pagenum();

		$additional_args = array(
			'paged' => $paged
		);

		$query = OA2_Client::get_clients_query($additional_args);
		$this->items = $query->posts;

		$pagination_args = array(
			'total_items' => $query->found_posts,
			'total_pages' => $query->max_num_pages,
			'per_page' => $query->get('posts_per_page')
		);
		$this->set_pagination_args($pagination_args);
	}

	public function get_columns() {
		$c = array(
			'cb'          => '<input type="checkbox" />',
			'name'        => __( 'Name', 'wp_rest_oauth2' ),
			'description' => __( 'Description', 'wp_rest_oauth2' ),
		);

		return $c;
	}

	public function column_cb( $item ) {
		?>
		<label class="screen-reader-text"
			for="cb-select-<?php echo esc_attr( $item->ID ) ?>"><?php esc_html_e( 'Select consumer', 'wp_rest_oauth2' ); ?></label>

		<input id="cb-select-<?php echo esc_attr( $item->ID ) ?>" type="checkbox"
			name="consumers[]" value="<?php echo esc_attr( $item->ID ) ?>" />

		<?php
	}

	protected function column_name( $item ) {
		$title = get_the_title( $item->ID );
		if ( empty( $title ) ) {
			$title = '<em>' . esc_html__( 'Untitled', 'wp_rest_oauth2' ) . '</em>';
		}

		$edit_link = add_query_arg(
			array(
				'page'   => 'rest-oauth2-apps',
				'action' => 'edit',
				'id'     => $item->ID,
			),
			admin_url( 'users.php' )
		);
		$delete_link = add_query_arg(
			array(
				'page'   => 'rest-oauth2-apps',
				'action' => 'delete',
				'id'     => $item->ID,
			),
			admin_url( 'users.php' )
		);
		$delete_link = wp_nonce_url( $delete_link, 'rest-oauth2-delete:' . $item->ID );

		$actions = array(
			'edit' => sprintf( '<a href="%s">%s</a>', esc_url( $edit_link ), esc_html__( 'Edit', 'wp_rest_oauth2' ) ),
			'delete' => sprintf( '<a href="%s">%s</a>', esc_url( $delete_link ), esc_html__( 'Delete', 'wp_rest_oauth2' ) ),
		);
		$action_html = $this->row_actions( $actions );

		return $title . ' ' . $action_html;
	}

	protected function column_description( $item ) {
		return $item->post_content;
	}
}
