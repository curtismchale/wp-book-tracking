<?php
/**
* Plugin Name: WP Book Tracker
* Plugin URI: https://curtismchale.ca/
* Description: Lets you track your reading and your library
* Version: 0.1
* Author: curtismchale
* Author URI: https://curtismchale.ca/
**/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/**

- [x] add CPT
- [x] register taxonomy (cat ) for fiction/non fiction
- [x] register taxonomy (cat) for genre
- [x] register taxonomy (tag) for Author
- [x] need a way to add markdown to the post as well that just works like standard markdown and has a copy button
	- for me this would be for members so I need to add a filter on the content display
- [x] add way to link to posts that reference this book
- [x] rating
- [x] restrict access to my notes
- [x] copy markdown button
- [x] date picker for read dates

** 8.2 issues

- [ ] learndash breaks taxonomies somehow
	- they don't expand at all so i can't enter anything and I already updated
	- with wp_debug false this does work so...

** Next **

- [ ] book_notes line 131
	- the indents are not being kept for the content
- [ ] basic text search
- [ ] ISBN/Title look up so that we can populate all the data that way
- [ ] block to include book in Gutenberg
- [ ] provide an option of stars or thumbs up/down as a rating system
- [ ] faceted searching
- [ ] sorting based on genre, rating ...

 **/


class Book_Tracking{

	private static $instance;

	/**
	 * Spins up the instance of the plugin so that we don't get many instances running at once
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 *
	 * @uses $instance->init()                      The main get it running function
	 */
	public static function instance(){

		if ( ! self::$instance ){
			self::$instance = new Book_Tracking();
			self::$instance->init();
		}

	} // instance

	/**
	 * Spins up all the actions/filters in the plugin to really get the engine running
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 *
	 * @uses $this->constants()                 Defines our constants
	 * @uses $this->includes()                  Gets any includes we have
	 */
	public function init(){

		$this->constants();
		$this->includes();

		add_action( 'init', array( $this, 'add_cpt' ) );
		add_action( 'init', array( $this, 'add_tax' ) );

		add_filter( 'the_content', array( __CLASS__, 'book_notes' ) );

		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_init', array( $this, 'enqueue' ) );

		// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

	} // init

	/**
	* Registers and enqueues scripts and styles
	*
	* @uses    wp_enqueue_style
	* @uses    wp_enqueue_script
	*
	* @since   0.1
	* @author  SFNdesign, Curtis McHale
	*/
	public function admin_enqueue(){

		$plugin_data = get_plugin_data( __FILE__ );
		$screen = get_current_screen();

		if ( 'post' != $screen->post_type ) return;

		// styles plugin
		if ( 'production' != wp_get_environment_type() ){
			wp_enqueue_script('wpbt-admin-scripts', plugins_url( '/wp-book-tracking/wpbt-admin-scripts.js' ), array('jquery'), time(), true);

			wp_enqueue_script('wpbt-tom-select', plugins_url( '/wp-book-tracking/node_modules/tom-select/dist/js/tom-select.complete.min.js' ), array('jquery'), time(), true);
			wp_enqueue_style( 'wpbt-tom-select-styles', plugins_url( '/wp-book-tracking/node_modules/tom-select/dist/css/tom-select.bootstrap4.min.css' ), '', time(), 'all');
		} else {
			wp_enqueue_script('wpbt-admin-scripts', plugins_url( '/wp-book-tracking/wpbt-admin-scripts.js' ), array('jquery'), esc_attr( $plugin_data['Version'] ), true);

			wp_enqueue_script('wpbt-tom-select', plugins_url( '/wp-book-tracking/node_modules/tom-select/dist/js/tom-select.complete.min.js' ), array('jquery'), esc_attr( $plugin_data['Version'] ), true);
			wp_enqueue_style( 'wpbt-tom-select-styles', plugins_url( '/wp-book-tracking/node_modules/tom-select/dist/css/tom-select.bootstrap4.min.css' ), '', esc_attr( $plugin_data['Version'] ), 'all');
		}


	} // admin_enqueue

	/**
	* Registers and enqueues scripts and styles
	*
	* @uses    wp_enqueue_style
	* @uses    wp_enqueue_script
	*
	* @since   0.1
	* @author  SFNdesign, Curtis McHale
	*/
	public function enqueue(){

		$plugin_data = get_plugin_data( __FILE__ );

		if ( is_admin() ) return;

		// styles plugin
		if ( 'production' != wp_get_environment_type() ){
			wp_enqueue_style( 'wpbt-styles', plugins_url( '/wp-book-tracking/wpbt-styles.css' ), '', time(), 'all');
			wp_enqueue_script('wpbt-scripts', plugins_url( '/wp-book-tracking/wpbt-scripts.js' ), array('jquery'), time(), true);
		} else {
			wp_enqueue_style( 'wpbt-styles', plugins_url( '/wp-book-tracking/wpbt-styles.css' ), '', esc_attr( $plugin_data['Version'] ), 'all');
			wp_enqueue_script('wpbt-scripts', plugins_url( '/wp-book-tracking/wpbt-scripts.js' ), array('jquery'), esc_attr( $plugin_data['Version'] ), true);
		}

	} // enqueue

	/**
	 * Appending the book notes to the_content
	 *
	 * @since 0.1
	 * @access public
	 * @author Curtis McHale
	 *
	 * @param	$content					Content of the main post area
	 * @uses	get_post_meta()				Returns post meta given post_id and key
	 * @uses	get_queried_object_id()				Returns the post_id
	 * @uses	wpautop()					Regex to make double line breaks paragraphs
	 * @return	string		$content		old content with appended content
	 */
	public static function book_notes( $content ){

		// return early if we are not on a book CPT
		if ( 'wp-book' !== get_post_type( get_queried_object_id() ) ){
			return $content;
		}

		// return early if the user doesn't have access to book content
		if ( ! self::can_access_content( get_queried_object_id() ) ){
			return self::member_message( $content );
		} // is_user_member

		remove_filter( 'the_content', array( __CLASS__, 'book_notes' ), 10 );
		$book_notes = get_post_meta( get_queried_object_id(), '_wpbt_md_notes', true );

		$html = '';

		$html .= '<section class="book-notes">';
			$html .= self::get_copy_button_html();
			$html .= '<code id="book_notes_to_copy">';
				$html .= wpautop( wp_kses_post( $book_notes ) );
			$html .= '</code>';
		$html .= '</section>';

		$content = $content . $html;

		return $content;

	} // book_notes

	private static function get_copy_button_html(){

		$copy_button_html = '';

		$copy_button_html .= '<div class="wp-block-buttons">';
			$copy_button_html .= '<div class="wp-block-button">';
				$copy_button_html .= '<button id="wpbt_copy_button" class="wpbt-copy-book-notes wp-block-button__link wp-element-button">Copy Notes</button>';
			$copy_button_html .= '</div>';
		$copy_button_html .= '</div>';

		return apply_filters( 'wpbt_copy_button_html', $copy_button_html );

	}

	/**
	 * Decides if a user can access the content
	 *
	 * @since 0.1
	 * @access private
	 * @author Curtis McHale
	 *
	 * @param		int			$post_id		optional		The ID of the post we're checking
	 * @return		bool		$access							True if the user can access the content
	 */
	private static function can_access_content( $post_id = null ){

		$access = true;

		/**
		 * True if the user can access the content
		 *
		 * @param	int		$post_id			The post we're seeing if the user has access to
		 */
		return apply_filters( 'wpbt_can_access_content', (bool) $access, absint( $post_id ) );

	} // is_user_member

	/**
	 * Adds a custom member message to the content if you don't have access
	 *
	 * @since 0.1
	 * @access private
	 * @author Curtis McHale
	 *
	 * @param		string			$content			required				Content of the post
	 * @return		string			$content.$message							content with member message added
	 */
	private static function member_message( $content ){

		$message = "<p>If you'd like access to this member only content then <a href=\"https://curtismchale.ca/membership\">become a member</a></p>";

		$message = apply_filters( 'wpbt_member_message', $message, get_queried_object_id() );

		return $content . $message;

	}


	/**
	 * Adds a custom taxonomy
	 */
	public function add_tax(){

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Ratings', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Rating', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Ratings', 'textdomain' ),
			'all_items'         => __( 'All Ratings', 'textdomain' ),
			'parent_item'       => __( 'Parent Rating', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Rating:', 'textdomain' ),
			'edit_item'         => __( 'Edit Rating', 'textdomain' ),
			'update_item'       => __( 'Update Rating', 'textdomain' ),
			'add_new_item'      => __( 'Add New Rating', 'textdomain' ),
			'new_item_name'     => __( 'New Rating Name', 'textdomain' ),
			'menu_name'         => __( 'Rating', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true, // true == like cats
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'		=> true,
			'rewrite'           => array( 'slug' => 'book-rating' ),
		);

		register_taxonomy( 'book-rating', array( 'wp-book' ), $args );

		// authors tax
		$labels = array(
			'name'              => _x( 'Authors', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Author', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Authors', 'textdomain' ),
			'all_items'         => __( 'All Authors', 'textdomain' ),
			'parent_item'       => __( 'Parent Author', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Author:', 'textdomain' ),
			'edit_item'         => __( 'Edit Author', 'textdomain' ),
			'update_item'       => __( 'Update Author', 'textdomain' ),
			'add_new_item'      => __( 'Add New Author', 'textdomain' ),
			'new_item_name'     => __( 'New Author Name', 'textdomain' ),
			'menu_name'         => __( 'Author', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => false, // like tags
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'		=> true,
			'rewrite'           => array( 'slug' => 'book-author' ),
		);

		register_taxonomy( 'book-author', array( 'wp-book' ), $args );

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Styles', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Style', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Styles', 'textdomain' ),
			'all_items'         => __( 'All Styles', 'textdomain' ),
			'parent_item'       => __( 'Parent Style', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Style:', 'textdomain' ),
			'edit_item'         => __( 'Edit Style', 'textdomain' ),
			'update_item'       => __( 'Update Style', 'textdomain' ),
			'add_new_item'      => __( 'Add New Style', 'textdomain' ),
			'new_item_name'     => __( 'New Style Name', 'textdomain' ),
			'menu_name'         => __( 'Style', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'		=> true,
			'rewrite'           => array( 'slug' => 'book-style' ),
		);

		register_taxonomy( 'book-style', array( 'wp-book' ), $args );


		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => _x( 'Genre', 'taxonomy general name', 'textdomain' ),
			'singular_name'     => _x( 'Genres', 'taxonomy singular name', 'textdomain' ),
			'search_items'      => __( 'Search Genre', 'textdomain' ),
			'all_items'         => __( 'All Genre', 'textdomain' ),
			'parent_item'       => __( 'Parent Genres', 'textdomain' ),
			'parent_item_colon' => __( 'Parent Genres:', 'textdomain' ),
			'edit_item'         => __( 'Edit Genres', 'textdomain' ),
			'update_item'       => __( 'Update Genres', 'textdomain' ),
			'add_new_item'      => __( 'Add New Genres', 'textdomain' ),
			'new_item_name'     => __( 'New Genres Name', 'textdomain' ),
			'menu_name'         => __( 'Genres', 'textdomain' ),
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'show_in_rest'		=> true,
			'rewrite'           => array( 'slug' => 'book-genre' ),
		);

		register_taxonomy( 'book-genre', array( 'wp-book' ), $args );
	}

	/**
	 * Builds out the custom post types for the site
	 *
	 * @uses    register_post_type
	 *
	 * @since   1.0
	 * @author  SFNdesign, Curtis McHale
	 */
	public function add_cpt(){

		register_post_type( 'wp-book', // http://codex.wordpress.org/Function_Reference/register_post_type
			array(
				'labels'                => array(
					'name'                  => __('Book'),
					'singular_name'         => __('Book'),
					'add_new'               => __('Add New'),
					'add_new_item'          => __('Add New Book'),
					'edit'                  => __('Edit'),
					'edit_item'             => __('Edit Book'),
					'new_item'              => __('New Book'),
					'view'                  => __('View Book'),
					'view_item'             => __('View Book'),
					'search_items'          => __('Search Book'),
					'not_found'             => __('No Book Found'),
					'not_found_in_trash'    => __('No Book found in Trash')
				), // end array for labels
				'public'                => true,
				'menu_position'         => 5, // sets admin menu position
				'menu_icon'             => 'dashicons-book-alt',
				'hierarchical'          => false, // funcions like posts
				'show_in_rest'			=> true,
				'supports'              => array('title', 'editor', 'revisions', 'excerpt', 'thumbnail'),
				'rewrite'               => array('slug' => 'book', 'with_front' => true,), // permalinks format
				'can_export'            => true,
			)
		);

	}

	/**
	 * Gives us any constants we need in the plugin
	 *
	 * @since 1.0
	 */
	public function constants(){

		define( 'BOOK_TRACK_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

	}

	/**
	 * Includes any externals
	 *
	 * @since 1.0
	 * @author SFNdesign, Curtis McHale
	 * @access public
	 */
	public function includes(){
		require_once( BOOK_TRACK_PLUGIN_DIR . 'metabox.php' );
	}

	/**
	 * Fired when plugin is activated
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function activate( $network_wide ){

	} // activate

	/**
	 * Fired when plugin is deactivated
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function deactivate( $network_wide ){

	} // deactivate

	/**
	 * Fired when plugin is uninstalled
	 *
	 * @param   bool    $network_wide   TRUE if WPMU 'super admin' uses Network Activate option
	 */
	public function uninstall( $network_wide ){

	} // uninstall

} // Book_Tracking

Book_Tracking::instance();
