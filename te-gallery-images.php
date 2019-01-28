<?php
/*
 Plugin Name: Te Gallery Images
 Plugin URI: https://www.themeseye.com/
 Description: Use to create and display gallery images.
 Author: Themeseye
 Version: 0.2
 Author URI: https://www.themeseye.com/
*/

define( 'TE_GALLERY_IMAGES_VERSION', '0.2' );

add_action( 'init', 'te_gallery_images_init' );

function te_gallery_images_init() {
	register_post_type( 'te_gallery', array(
		'labels' => array(
			'name'               => __( 'Gallery','te-gallery-images' ),
			'singular_name'      => __( 'Gallery','te-gallery-images' ),
			'add_new'            => __( 'Add New Gallery','te-gallery-images' ),
			'add_new_item'       => __( 'Add New Gallery','te-gallery-images' ),
			'edit_item'          => __( 'Edit Gallery', 'te-gallery-images' ),
			'new_item'           => __( 'New Gallery', 'te-gallery-images' ),
			'view_item'          => __( 'View Gallery', 'te-gallery-images' ),
			'search_items'       => __( 'Search Gallery', 'te-gallery-images' ),
			'not_found'          => __( 'No Gallery found.', 'te-gallery-images' ),
			'not_found_in_trash' => __( 'No Gallery found in trash.', 'te-gallery-images' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'TE Gallery', 'te-gallery-images' ),
			),
		'public'              => true,
		'exclude_from_search' => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'rewrite'             => false,
		'query_var'           => false,
		'menu_position'       => '',
		'menu_icon'           => 'dashicons-format-gallery',
		'supports'            => array( 'title' ),
		) );

}

function pw_add_image_sizes() {
    add_image_size( 'te-gallery-image-medium', 300, 300, true );
}
add_action( 'init', 'pw_add_image_sizes' );
 
function pw_show_image_sizes($sizes) {
    $sizes['te-gallery-image-medium'] = __( 'Custom Thumb', 'pippin' );
 
    return $sizes;
}
add_filter('image_size_names_choose', 'pw_show_image_sizes');


// Including the CSS and JS for the front end
add_action('wp_enqueue_scripts', 'te_gallery_images_callback_for_setting_up_scripts');
function te_gallery_images_callback_for_setting_up_scripts() {
	wp_enqueue_script( 'pretty-custom-js', plugins_url( '/js/jquery.prettycustom.js', __FILE__ ), array('jquery') );
	wp_enqueue_script( 'pretty-photo-js', plugins_url( '/js/jquery.prettyPhoto.js', __FILE__ ), array('jquery') );
	
    wp_enqueue_style( 'prettyPhoto-css', plugins_url( 'css/prettyPhoto.css', __FILE__ ), '', '1.0' );

}


function te_gallery_images_metabox_enqueue($hook) {
	if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
		wp_enqueue_script('te-gallery-images-metabox', plugin_dir_url( __FILE__ ) . '/js/te-gm.js', array('jquery', 'jquery-ui-sortable'));
		wp_enqueue_style('te-gallery-images-metabox', plugin_dir_url( __FILE__ ) . '/css/te-gm.css');

		global $post;
		if ( $post ) {
			wp_enqueue_media( array(
					'post' => $post->ID,
				)
			);
		}

	}
}

add_action('admin_enqueue_scripts', 'te_gallery_images_metabox_enqueue');

function te_gallery_images_add_gallery_metabox($post_type) {
	$types = array('te_gallery');

	if (in_array($post_type, $types)) {
		add_meta_box(
			'te-gallery-image-metabox',
			__( 'Gallery Images', 'te-gallery-images' ),
			'te_gallery_images_meta_callback',
			$post_type,
			'normal',
			'high'
			);
	}
}

add_action('add_meta_boxes', 'te_gallery_images_add_gallery_metabox');

function te_gallery_images_meta_callback( $post ) {
	wp_nonce_field( basename( __FILE__ ), 'te_gallery_images_meta_nonce' );
	$ids = get_post_meta( $post->ID, 'te_gallery_images_gal_id', true );

	?>
	<table class="form-table">
		<tr>
			<td>
				<a class="gallery-add button" href="#" data-uploader-title="<?php esc_attr_e( 'Add image(s) to gallery', 'te-gallery-images' ); ?>" data-uploader-button-text="<?php esc_attr_e( 'Add image(s)', 'te-gallery-images' ); ?>"><?php esc_html_e( 'Add image(s)', 'te-gallery-images' ); ?></a>

				<ul id="te-gallery-images-item-list">
					<?php if ( $ids ) : foreach ( $ids as $key => $value ) : $image = wp_get_attachment_image_src( $value ); ?>

						<li>
							<input type="hidden" name="te_gallery_images_gal_id[<?php echo $key; ?>]" value="<?php echo $value; ?>">
							<img class="image-preview" src="<?php echo esc_url( $image[0] ); ?>">
							<a class="change-image button button-small" href="#" data-uploader-title="<?php esc_attr_e( 'Change image', 'te-gallery-images' ) ; ?>" data-uploader-button-text="<?php esc_attr_e( 'Change image', 'te-gallery-images' ) ; ?>"><?php esc_html_e( 'Change image', 'te-gallery-images' ) ; ?></a><br>
							<small><a class="remove-image" href="#"><?php esc_html_e( 'Remove image', 'te-gallery-images' ) ; ?></a></small>
						</li>

					<?php endforeach;
					endif; ?>
				</ul>
			</td>
		</tr>
	</table>
	<?php
}

function te_gallery_images_meta_save($post_id) {
	if (!isset($_POST['te_gallery_images_meta_nonce']) || !wp_verify_nonce($_POST['te_gallery_images_meta_nonce'], basename(__FILE__))) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if(isset($_POST['te_gallery_images_gal_id'])) {
		$sanitized_values = array_map('intval', $_POST['te_gallery_images_gal_id']);
		update_post_meta($post_id, 'te_gallery_images_gal_id', $sanitized_values );
	} else {
		delete_post_meta($post_id, 'te_gallery_images_gal_id');
	}
}
add_action('save_post', 'te_gallery_images_meta_save');

function te_gallery_images_get_custom_post_type_template( $single_template ) {
	global $post;
	if ($post->post_type == 'te_gallery') {
		if ( file_exists( get_template_directory() . '/page-template/gallery.php' ) ) {
			$single_template = get_template_directory() . '/page-template/gallery.php';
		}
	}
	return $single_template;
}

add_filter( 'single_template', 'te_gallery_images_get_custom_post_type_template' );

/*Shortcode for Gallery*/
function te_gallery_images_gallery_show($gallery_id,$numberofitem, $bootstraponecolsize) {
	// add_thickbox();
	$get_post_id = isset( $gallery_id['te_gallery'] ) ? absint( $gallery_id['te_gallery'] ) : 0;
	$numberofitem = isset( $gallery_id['numberofitem'] ) ? absint( $gallery_id['numberofitem'] ) : 8;
	$bootstraponecolsize = isset( $gallery_id['bootstraponecolsize'] ) ? absint( $gallery_id['bootstraponecolsize'] ) : 2;

	if ( ! $get_post_id ) {
		return;
	}

	$images = get_post_meta($get_post_id, 'te_gallery_images_gal_id', true);

	$res = '';
	if(empty($images)){
		$res = '<p>' . esc_html__( 'No Image Found', 'te-gallery-images' ) . '</p>';
	}
	else{
		$gal_i=1;
		$res .= '<ul class="te_gallery_front row clearfix">';
		foreach ($images as $image) {
			global $post;
			$image_uri_medium = wp_get_attachment_image( $image, 'te-gallery-image-medium' );
			$image_uri_large = wp_get_attachment_image_url( $image, 'full' );
			$full = wp_get_attachment_link($image, 'large');
			$attachment_title = get_the_title($image);
			$res .= '<li class="col-md-'.$bootstraponecolsize.' col-sm-6 col-6 p-0">
			<a href="'.$image_uri_large.'" rel="prettyPhoto[gallery_name]" title="'.$attachment_title.'">'.$image_uri_medium.'<div class="icon_overlay"><i class="fa fa-plus"></i></div></a>
			</li>';
			if($gal_i == $numberofitem) {
				break;
			}
			$gal_i++;
		}
		$res .= '</ul>';
	}

	return $res;
}

add_shortcode( 'te-galleryshow', 'te_gallery_images_gallery_show' );
?>