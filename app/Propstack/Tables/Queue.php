<?php
/**
 * This file contains the list for the queue of files, which will be imported, using WP_List_Table.
 *
 * @package connector-for-propstack
 */

namespace ConnectorForPropstack\Propstack\Tables;

// prevent direct access.
defined( 'ABSPATH' ) || exit;

use ConnectorForPropstack\Plugin\Helper;
use ConnectorForPropstack\Plugin\ProcessHandler;
use ConnectorForPropstack\Plugin\Settings;
use WP_List_Table;
use WP_Post;

/**
 * Initialize the log viewer.
 */
class Queue extends WP_List_Table {
	/**
	 * Override the parent columns method. Defines the columns to use in your listing table.
	 *
	 * @return array<string,string>
	 */
	public function get_columns(): array {
		$columns = array(
			'options' => __( 'Options', 'connector-for-propstack' ),
			'date'    => __( 'Date', 'connector-for-propstack' ),
			'file'    => __( 'URL', 'connector-for-propstack' ),
		);

		/**
		 * Filter the columns for the queue table before output.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,string> $columns List of columns.
		 */
		return apply_filters( 'cfprop_queue_table_columns', $columns );
	}

	/**
	 * Get the table data
	 *
	 * @return array<int,WP_Post>
	 */
	private function table_data(): array {
		add_filter(
			'propstack_connector_queue_query',
			static function ( array $query ) {
				$query['posts_per_page'] = -1;
				return $query;
			}
		);
		return \ConnectorForPropstack\Propstack\Queue::get_instance()->get_queue();
	}

	/**
	 * Get the log-table for the table-view.
	 *
	 * @return void
	 */
	public function prepare_items(): void {
		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();

		$per_page     = 50;
		$current_page = $this->get_pagenum();
		$total_items  = count( $data );

		$this->set_pagination_args(
			array(
				'total_items' => $total_items,
				'per_page'    => $per_page,
			)
		);

		$data = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $data;
	}

	/**
	 * Define, which columns are hidden
	 *
	 * @return array<string,array<string,bool>>
	 */
	public function get_hidden_columns(): array {
		return array();
	}

	/**
	 * Define the sortable columns
	 *
	 * @return array<string,array<int,string|false>>
	 */
	public function get_sortable_columns(): array {
		return array( 'date' => array( 'date', false ) );
	}

	/**
	 * Define what data to show on each column of the table
	 *
	 * @param  WP_Post $item        The single WP_Post object.
	 * @param  string  $column_name - Current iterated column name.
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		$content = match ( $column_name ) {
			'options' => $this->get_options(),
			'date' => Helper::get_format_date_time( $item->post_date ),
			'file' => $this->get_url( $item ),
			default => '',
		};

		/**
		 * Filter the content of a single column for the queue table.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 *
		 * @param string $content The content of the column.
		 * @param string $column_name The name of the column.
		 * @param WP_Post $item The item object.
		 */
		return apply_filters( 'cfprop_queue_table_column_content', $content, $column_name, $item );
	}

	/**
	 * Add a process-button on top of the table.
	 *
	 * @param string $which The position.
	 * @return void
	 */
	public function extra_tablenav( $which ): void {
		if ( 'top' === $which ) {
			// define process-URL.
			$process_url = add_query_arg(
				array(
					'action' => 'propstack_connector_queue_process',
					'nonce'  => wp_create_nonce( 'propstack-connector-queue-process' ),
				),
				get_admin_url() . 'admin.php'
			);

			// create empty-dialog.
			$process_dialog = array(
				'className' => 'propstack-connector-dialog',
				'title'     => __( 'Process the queue now', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'Are you sure you want to process the queue now?', 'connector-for-propstack' ) . '</strong></p>',
					/* translators: %1$s will be replaced by a number, %2$s by a URL. */
					'<p>' . sprintf( __( 'Only max. %1$d entries will be processed according <a href="%2$s">to your setting</a>.', 'connector-for-propstack' ), absint( get_option( 'propstack_connector_queue_limit' ) ), esc_url( Settings::get_instance()->get_url( 'propstack_connector_queue' ) ) . '#propstack_connector_queue_limit' ) . '</p>',
					'<p>' . __( 'This might take some time. You have to be patient.', 'connector-for-propstack' ) . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'propstack_connector_queue_process("' . ProcessHandler::get_instance()->create_id() . '");',
						'variant' => 'primary',
						'text'    => __( 'Yes, process the queue', 'connector-for-propstack' ),
					),
					array(
						'action'  => 'closeDialog();',
						'variant' => 'secondary',
						'text'    => __( 'Cancel', 'connector-for-propstack' ),
					),
				),
			);

			?>
			<a href="<?php echo esc_url( $process_url ); ?>" class="button button-secondary easy-dialog-for-wordpress<?php echo ( 0 === count( $this->items ) ? ' disabled' : '' ); ?>" data-dialog="<?php echo esc_attr( Helper::get_json( $process_dialog ) ); ?>"><?php echo esc_html__( 'Process queue', 'connector-for-propstack' ); ?></a>
			<?php

			// define clear-URL.
			$clear_url = add_query_arg(
				array(
					'action' => 'propstack_connector_queue_clear',
					'nonce'  => wp_create_nonce( 'propstack-connector-queue-clear' ),
				),
				get_admin_url() . 'admin.php'
			);

			// create clear-dialog.
			$clear_dialog = array(
				'className' => 'propstack-connector-dialog',
				'title'     => __( 'Clear the queue now', 'connector-for-propstack' ),
				'texts'     => array(
					'<p><strong>' . __( 'Are you sure you want to clear the queue now?', 'connector-for-propstack' ) . '</strong></p>',
					'<p>' . __( 'Every entry in the queue will be deleted.', 'connector-for-propstack' ) . '</p>',
				),
				'buttons'   => array(
					array(
						'action'  => 'location.href="' . esc_url( $clear_url ) . '";',
						'variant' => 'primary',
						'text'    => __( 'Yes, clear the queue', 'connector-for-propstack' ),
					),
					array(
						'action'  => 'closeDialog();',
						'variant' => 'secondary',
						'text'    => __( 'Cancel', 'connector-for-propstack' ),
					),
				),
			);

			?>
			<a href="<?php echo esc_url( $clear_url ); ?>" class="button button-secondary easy-dialog-for-wordpress<?php echo ( 0 === count( $this->items ) ? ' disabled' : '' ); ?>" data-dialog="<?php echo esc_attr( Helper::get_json( $clear_dialog ) ); ?>"><?php echo esc_html__( 'Clear queue', 'connector-for-propstack' ); ?></a>
			<?php
		}
	}

	/**
	 * Message to be displayed when no items are available.
	 */
	public function no_items(): void {
		echo esc_html__( 'The queue to import files from Propstack is empty.', 'connector-for-propstack' );
	}

	/**
	 * Define filter for log table.
	 *
	 * @return array<string,string>
	 */
	protected function get_views(): array {
		// get the main URL without the filter.
		$url = remove_query_arg( array( 'errors' ) );

		// get called error-parameter.
		$errors = absint( filter_input( INPUT_GET, 'errors', FILTER_SANITIZE_NUMBER_INT ) );

		// define initial list.
		$list = array(
			'all' => '<a href="' . esc_url( $url ) . '"' . ( 0 === $errors ? ' class="current"' : '' ) . '>' . esc_html__( 'All', 'connector-for-propstack' ) . '</a>',
		);

		// add the filter for errors.
		$url            = add_query_arg( array( 'errors' => 1 ) );
		$list['errors'] = '<a href="' . esc_url( $url ) . '"' . ( 1 === $errors ? ' class="current"' : '' ) . '>' . esc_html__( 'Errors', 'connector-for-propstack' ) . '</a>';

		/**
		 * Filter the list before output.
		 *
		 * @since 1.0.0 Available since 1.0.0.
		 * @param array<string,string> $list List of filter.
		 */
		return apply_filters( 'cfprop_queue_table_filter', $list );
	}

	/**
	 * Return the list of options for a single entry.
	 *
	 * @return string
	 */
	private function get_options(): string {
		return '<a class="propstack-connector-pro-hint" href="' . Helper::get_pro_url() . '" target="_blank">' . esc_html__( 'Get options with Pro', 'connector-for-propstack' ) . '</a>';
	}

	/**
	 * Return the URL to import.
	 *
	 * @param WP_Post $item The post-object.
	 *
	 * @return string
	 */
	private function get_url( WP_Post $item ): string {
		// get the document URL.
		$url = (string) get_post_meta( $item->ID, 'url', true );

		// if no URL is set, get the image URL.
		if ( empty( $url ) ) {
			$url = (string) get_post_meta( $item->ID, get_option( 'propstack_connector_image_size', 'big_url' ), true );
		}

		// if still no URL is set, get the doc URL.
		if ( empty( $url ) ) {
			$urls = (array) get_post_meta( $item->ID, 'doc', true );
			if ( ! empty( $urls ) ) {
				$url = Helper::shorten_url( $urls['url'] );
			}
		}

		// get the document URL.
		return '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_url( $url ) . '</a>';
	}

	/**
	 * Generates the table navigation above or below the table
	 *
	 * @param string $which The location of the navigation: Either 'top' or 'bottom'.
	 */
	protected function display_tablenav( $which ): void {
		if ( 'bottom' === $which && ! $this->has_items() ) {
			return;
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

			<?php if ( $this->has_items() ) : ?>
				<div class="alignleft actions bulkactions">
					<?php
					$this->bulk_actions( $which ); // @phpstan-ignore argument.type
					?>
				</div>
				<?php
			endif;
			$this->extra_tablenav( $which ); // @phpstan-ignore argument.type
			$this->pagination( $which ); // @phpstan-ignore argument.type
			?>

			<br class="clear" />
		</div>
		<?php
	}
}
