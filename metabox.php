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
			__( 'Mardown Notes', 'textdomain' ),
			array( $this, 'render_metabox' ),
			'wp-book',
			'advanced',
			'default'
		);

	}

	/**
	 * Renders the meta box.
	 */
	public function render_metabox( $post ) {
		// Add nonce for security and authentication.
		wp_nonce_field( 'wpbt_nonce_action', 'wpbt_nonce' );

		$wpbt_md_notes = get_post_meta( absint( $post->ID ), '_wpbt_md_notes', true );
	?>
			<label for="wpbt_md_notes" />
			<textarea name="wpbt_md_notes" type="textarea" id="wpbt_md_notes"><?php
				if ( isset( $wpbt_md_notes ) && ! empty( $wpbt_md_notes ) ){
					echo $wpbt_md_notes; // @todo this is insecure fix it
				}
			?></textarea>
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

		if ( isset( $_POST['wpbt_md_notes'] ) ){
			update_post_meta( absint( $post_id ), '_wpbt_md_notes', sanitize_text_field( $_POST['wpbt_md_notes'] ) );
		}

	}
}

new WPBT_Metabox();
