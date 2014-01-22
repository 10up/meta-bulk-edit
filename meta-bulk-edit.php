<?php
/*
Plugin Name: Meta Field Bulk Edit
Description: Map a meta field to a CPT for quick-edit & bulk-edit capabilities.
Version: 1.0.0
Author: Mike Jordan, 10up
Author URI: http://knowmike.com
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action( 'init', 'Storm_Meta_Bulk_Edit::get_instance' );

class Storm_Meta_Bulk_Edit {
	
	/**
	 * @var Storm_Meta_Bulk_Edit Instance of the class.
	 */
	private static $instance = false;
	
	/**
	 * @var array Configuration
	 */
	private $meta_settings = array(
		'title' => 'Meta Name', // human-readable name
		'slug' => 'meta-name', // meta_key as in the wp_postmeta table
		'input_type' => 'input', // string input type
		'post_type' => 'post',
		'empty_message' => 'None' // default text when no data found
	);
	
	/**
	 * Don't use this. Use ::get_instance() instead.
	 */
	public function __construct() {
		if ( !self::$instance ) {
			$message = '<code>' . __CLASS__ . '</code> is a singleton.<br/> Please get an instantiate it with <code>' . __CLASS__ . '::get_instance();</code>';
			wp_die( $message );
		}	
	}
	
	public static function get_instance() {
		if ( !is_a( self::$instance, __CLASS__ ) ) {
			self::$instance = true;
			self::$instance = new self();
			self::$instance->init();
		}
		return self::$instance;
	}
	
	/**
	 * Initial setup. Called by get_instance.
	 */
	protected function init() {
		add_action( 'admin_print_scripts-edit.php', array( $this, 'enqueue_edit_scripts' ) );		
		add_filter( 'manage_'.$this->meta_settings['post_type'].'_posts_columns', array( $this, 'edit_meta_columns' ), 1 );
		add_action( 'manage_'.$this->meta_settings['post_type'].'_posts_custom_column', array( $this, 'manage_meta_columns' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $this, 'add_to_bulk_quick_edit_custom_box' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'add_to_bulk_quick_edit_custom_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_post' ), 10, 2 );
		add_action( 'wp_ajax_storm_save_bulk_edit', array( $this, 'storm_save_bulk_edit' ) );
	}

	/**
	 * Enqueue js script for loading / saving Quick Edit / Bulk Edit 
	 */
	function enqueue_edit_scripts() {
		wp_enqueue_script( 'storm-meta-bulk', plugin_dir_url( __FILE__ ).'storm-meta-bulk.js', array( 'jquery', 'inline-edit-post' ), '', true );
	}
	
	/**
	 * Add column for Meta on CPT
	 */
	function edit_meta_columns( $columns ) {
		
		$columns[$this->meta_settings['slug']] = $this->meta_settings['title'];
		
		return $columns;
	}
	
	/**
	 * Populate column
	 */
	function manage_meta_columns( $column, $post_id ) {
		global $post;
	
		switch( $column ) {
	
			/* If displaying our custom meta column. */
			case $this->meta_settings['slug'] :
			
				/* Get the post meta. */
				$custom_meta = get_post_meta( $post_id, $this->meta_settings['slug'], true );
				
				/* If no custom meta is found, output a default message. */
				if ( empty( $custom_meta ) )
					echo $this->meta_settings['empty_message'];
				else
					echo '<div class="storm-meta-input" id="storm-meta-input-' . $post_id . '">' . get_post_meta( $post_id, $this->meta_settings['slug'], true ) . '</div>';
				break;
				
			default :
				break;
		}
	}
	
	/**
	 * Input field output for bulk edit / quick edit
	 */
	function add_to_bulk_quick_edit_custom_box( $column_name, $post_type ) {
	   switch ( $post_type ) {
	      case $this->meta_settings['post_type']:
	
	         switch( $column_name ) {
	            case $this->meta_settings['slug']:
	               ?><fieldset class="inline-edit-col-right">
	                  <div class="inline-edit-group">
	                     <label>
	                        <span class="title"><?php echo $this->meta_settings['title']; ?></span>
	                        <input type="<?php echo $this->meta_settings['input_type']; ?> " name="storm_meta_input" value="" />
	                     </label>
	                  </div>
	               </fieldset><?php
	               break;
	         }
	         break;
	   }
	}
	
	/**
	 * Save meta field
	 */
	function save_post( $post_id, $post ) {
	
		// don't save for autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
			return $post_id;
		}
		// dont save for revisions
		if ( isset( $post->post_type ) && $post->post_type == 'revision' ){
			return $post_id;
		}
	
		switch( $post->post_type ) {
			case $this->meta_settings['post_type']:
	
			if ( array_key_exists( 'storm_meta_input', $_POST ) )
				update_post_meta( $post_id, $this->meta_settings['slug'], $_POST[ 'storm_meta_input' ] );
			
		 	break;
	   }
	}
	
	/**
	 * Ajax save quick edit / bulk
	 */
	function storm_save_bulk_edit() {
		// get our variables
		$post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
		$meta_value = ( isset( $_POST[ 'storm_meta_input' ] ) && !empty( $_POST[ 'storm_meta_input' ] ) ) ? $_POST[ 'storm_meta_input' ] : NULL;
		// if everything is in order
		if ( !empty( $post_ids ) && is_array( $post_ids ) && !empty( $meta_value ) ) {
			foreach( $post_ids as $post_id ) {
				update_post_meta( $post_id, $this->meta_settings['slug'], $meta_value );
			}
		}
	}
}
