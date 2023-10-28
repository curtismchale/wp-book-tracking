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
- [ ] need a way to add markdown to the post as well that just works like standard markdown and has a copy button
	- for me this would be for members so I need to add a filter on the content display
- [ ] add way to link to posts that reference this book

** Next **

- [ ] ISBN look up so that we can populate all the data that way
- [ ] block to include book in Gutenberg
- [ ] rating
	- provide an option of stars or thumbs up/down


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

		// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
		register_activation_hook( __FILE__, array( $this, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $this, 'deactivate' ) );
		register_uninstall_hook( __FILE__, array( __CLASS__, 'uninstall' ) );

	} // init


	/**
	 * Adds a custom taxonomy
	 */
	public function add_tax(){

// Add new taxonomy, make it hierarchical (like categories)
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
			'hierarchical'      => false,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
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
