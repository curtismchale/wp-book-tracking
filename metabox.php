<?php
/**
 * Register a meta box using a class.
 */
class WPBT_Metabox {

	/**
	 * Constructor.
	 */
	public function __construct() {
		if ( is_admin() ) {
			add_action( 'load-post.php',     array( $this, 'init_metabox' ) );
			add_action( 'load-post-new.php', array( $this, 'init_metabox' ) );
		}

	}

	/**
	 * Meta box initialization.
	 */
	public function init_metabox() {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox'  )        );
		add_action( 'save_post',      array( $this, 'save_metabox' ), 10, 2 );
	}

	/**
	 * Adds the meta box.
	 */
	public function add_metabox() {
		add_meta_box(
			'wpbt-metabox',
			__( 'Markdown Notes', 'textdomain' ),
			array( $this, 'render_metabox' ),
			'wp-book',
			'advanced',
			'default'
		);

		add_meta_box(
			'wpbt-metabox',
			__( 'Related Books', 'textdomain' ),
			array( $this, 'render_posts_metabox' ),
			'post',
			'advanced',
			'default'
		);

	}

	/**
	 * Renders the meta box.
	 */
	public function render_posts_metabox( $post ) {
		// Add nonce for security and authentication.
		wp_nonce_field( 'wpbt_nonce_action', 'wpbt_nonce' );

		$books_query = array(
			'post_type' => 'wp-book',
			'posts_per_page' => -1,
			'fields' => 'ids',
		);

		$books = get_posts( $books_query );
		$related_books = get_post_meta( absint( $post->ID ), '_wpbt_related_books', true );

	?>

		<p id="wpbt_related_books_select">
			<label for="wpbt_related_books"><?php _e( 'Related Books', 'wpbt' ); ?></label>
			<select name="wpbt_related_books[]" id="wpbt_related_books" multiple="multiple">
				<option value="">Choose Books</option>
				<?php foreach( $books as $book ){ ?>
					<?php
						if( isset( $related_books ) && !empty( $related_books ) && in_array( absint( $book ), $related_books ) ){
							$selected = 'selected="selected"';
						} else {
							$selected = '';
						}
					?>
					<option value="<?php echo absint( $book ); ?>" <?php echo esc_html( $selected ); ?>><?php echo get_the_title( absint( $book ) ); ?></option>
				<?php } ?>
			</select><br />
			<span class="description"><?php _e( "Which books are related", 'wpbt' ); ?>.</span>
		</p>

	<?php
	}

	/**
	 * Renders the meta box.
	 */
	public function render_metabox( $post ) {
		// Add nonce for security and authentication.
		wp_nonce_field( 'wpbt_nonce_action', 'wpbt_nonce' );

		$wpbt_md_notes = get_post_meta( absint( $post->ID ), '_wpbt_md_notes', true );
	?>

		<p id="wpbt_md_notes">
			<label for="wpbt_md_notes"><?php _e( 'Custom message:', 'wecr' ); ?></label>
			<?php wp_editor( wp_kses_post( $wpbt_md_notes ), 'wpbt_md_notes' ); ?>
			<span class="description"><?php _e( "This is where your markdown notes go", 'wecr' ); ?>.</span>
		</p>

	<?php
	}

	/**
	 * Handles saving the meta box.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @return null
	 */
	public function save_metabox( $post_id, $post ) {

		// Add nonce for security and authentication.
		$nonce_name   = isset( $_POST['wpbt_nonce'] ) ? $_POST['wpbt_nonce'] : '';
		$nonce_action = 'wpbt_nonce_action';

		// Check if nonce is valid.
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		// Check if user has permissions to save data.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Check if not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}

/*
echo '<pre>';
print_r( $_POST );
echo '</pre>';
*/
//error_log( print_r( $_POST, true ) );


		if ( isset( $_POST['wpbt_related_books'] ) ){
			update_post_meta( absint( $post_id ), '_wpbt_related_books', array_map( 'absint', $_POST['wpbt_related_books'] ) );
		}

		if ( isset( $_POST['wpbt_md_notes'] ) ){
			update_post_meta( absint( $post_id ), '_wpbt_md_notes', wp_kses_post( $_POST['wpbt_md_notes'] ) );
		}

	}
}

new WPBT_Metabox();
