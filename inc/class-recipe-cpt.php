<?php

/**
 * Class to register our Recipe CPT
 *
 * @package wholegrain_test
 **/

if ( ! class_exists( 'wholegrain_test_recipe_cpt' ) ) {

	class wholegrain_test_recipe_cpt {
	
		public $cpt = 'recipe';
	
		public function __construct() {
			// Code to fire on hooks here
			add_action( 'init', array( $this, 'register_cpt' ) );
			add_action( 'admin_init', array( $this, 'add_ingredients_metabox' ) );
			add_action( 'save_post', array( $this, 'save_ingredients_metabox' ) );		
			add_action( 'init', array( $this, 'cpt_rest_support' ) );
			add_filter( 'rest_prepare_recipe', array( $this, 'filter_ingredients_json') );

		}
	
		public function register_cpt() {
			// CPT code here
	    	register_post_type( $this->cpt, array(
				'labels'              => array(
					'name'               => __( 'Recipes', 'wholegrain_test' ),
					'singular_name'      => __( 'Recipe', 'wholegrain_test' ),
					'add_new'            => __( 'Add New Recipe', 'wholegrain_test' ),
					'add_new_item'       => __( 'Add New Recipe', 'wholegrain_test' ),
					'edit_item'          => __( 'Edit Recipe', 'wholegrain_test' ),
					'new_item'           => __( 'New Recipe', 'wholegrain_test' ),
					'view_item'          => __( 'View Recipe', 'wholegrain_test' ),
					'search_items'       => __( 'Search Recipes', 'wholegrain_test' ),
					'not_found'          => __( 'No Recipe found', 'wholegrain_test' ),
					'not_found_in_trash' => __( 'No Recipe found in Trash', 'wholegrain_test' ),
					'parent_item_colon'  => __( 'Parent Recipe:', 'wholegrain_test' ),
					'menu_name'          => __( 'Recipes', 'wholegrain_test' ),
			    ),
				'show_ui'             => true,
				'show_in_menu'        => true,
				'supports'            => array( 'title', 'editor', ),
				'taxonomies'		  => array( 'category' ),
	    	) ); // end register_post_type
		} // end register_cpt
	
		/**
		* Add custom field
		*/
		public function add_ingredients_metabox() {
			// Adds Ingredients meta box to the post editing screen
    		add_meta_box( 'ingredients_metabox_id', 'Ingredients', array( $this, 'display_ingredients_metabox'), 'recipe', 'normal', 'default');
		}

		/**
		* Render custom field
		*/
		public function display_ingredients_metabox() {
        	global $post;
			// Get saved meta data
			$ingredients = get_post_meta( $post->ID, 'ingredients_metabox_id', TRUE); 
			if (!$ingredients) $ingredients = '';
			wp_nonce_field( 'ing_wysiwyg'. $post->ID, 'cpt_ings_nonce');
			// Render editor meta box
		    wp_editor( htmlspecialchars_decode($ingredients), 'ing_wysiwyg', array('textarea_rows' => '5') );
		}

		/**
		* Save custom field data
		*/
		public function save_ingredients_metabox( $post_id )
		{
			// Bail if we're doing an auto save
			if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
			// if our nonce isn't there, or we can't verify it, bail
			if( !isset( $_POST['cpt_ings_nonce'] ) || !wp_verify_nonce( $_POST['cpt_ings_nonce'], 'ing_wysiwyg'.$post_id ) ) return;
			// if our current user can't edit this post, bail
			if( !current_user_can( 'edit_post' ) ) return;
			
			// Make sure our data is set before trying to save it
			if( isset( $_POST['ing_wysiwyg'] ) ) update_post_meta( $post_id, 'ingredients_metabox_id', $_POST['ing_wysiwyg'] );
		}

		/**
		* Add REST API support to an already registered post type.
		*/
		public function cpt_rest_support() {
			global $wp_post_types;
			//be sure to set this to the name of your post type!
			if( isset( $wp_post_types[ $this->cpt ] ) ) {
				$wp_post_types[$this->cpt]->show_in_rest = true;
				$wp_post_types[$this->cpt]->rest_base = $this->cpt;
				$wp_post_types[$this->cpt]->rest_controller_class = 'WP_REST_Posts_Controller';
			}
		}

		/**
		* Add REST API support for metabox and category names.
		*/
		public function filter_ingredients_json( $data, $post, $context ) {
        	global $post;
        	// Get metabox data
			$ing = get_post_meta( $post->ID, 'ingredients_metabox_id', true );
			if( $ing ) { $data->data['ing'] = $ing; }
			// Get category names
    		$cats = wp_get_post_categories($post->ID);
    		$data->data['cat_names'] = [];
    		foreach ($cats as $cat) {
			    $c = get_category( $cat );
   		    	$data->data['cat_names'] = $c->name;
    		}
			return $data;
		}

	} // END class
	
	new wholegrain_test_recipe_cpt();

}