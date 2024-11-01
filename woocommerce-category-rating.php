<?php
/*
Plugin Name: Woocommerce Category Rating
Plugin URI: http://69.73.148.227/woocommerce-category-rating.zip
Description: This plugin calculates the average rating of a Woocommerce product category based on the reviews added to each individual product it contains. The rating is added in Schema.org format and it will create Rich Snippets in Google for each category that contains reviewed products.
Version: 1.0
Author: Dorian Barosan
Author URI: http://www.craftingwp.com
License: GPL2

Copyright 2014  Dorian_Barosan  (email : tib_lm@yahoo.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

defined('ABSPATH') or die("Te pup!");


function super_plugin_install(){
	    //Do some installation work
}
register_activation_hook(__FILE__,'super_plugin_install');


//HOOKS
add_action('init','super_plugin_init');
/********************************************************/
/*                FUNCTIONS
********************************************************/

function super_plugin_init(){
	    
	add_action( 'woocommerce_after_shop_loop','show_rating_stars' );
	
		function show_rating_stars() {
			if (is_paged()) return;
			
			global $woocommerce, $post, $wpdb, $wp_query;

			if ( comments_open() ) :
			
			// get the query object
			$cat_obj = $wp_query->get_queried_object();
			
			 if($cat_obj)    {
			    $category_name = $cat_obj->name;
			    $category_desc = $cat_obj->description;
			    $category_ID  = $cat_obj->term_id;
			}
			
			$post_ids = get_posts(array(
			    'numberposts'   => -1, // get all posts.
			    'post_type'             => 'product',
			    'tax_query'     => array(
			        array(
			            'taxonomy'  => 'product_cat',
			            'field'     => 'id',
			            'terms'     => $category_ID,
			        ),
			    ),
			    'fields'        => 'ids', // Only get post IDs
			));
			
			$sumrating = 0;
			$sumcount = 0;

			foreach ($post_ids as $pId) {
				$count = $wpdb->get_var("
				SELECT COUNT(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = $pId
				AND comment_approved = '1'
				AND meta_value > 0
				");
				
				$sumcount = $sumcount + $count;
				
				$rating = $wpdb->get_var("
				SELECT SUM(meta_value) FROM $wpdb->commentmeta
				LEFT JOIN $wpdb->comments ON $wpdb->commentmeta.comment_id = $wpdb->comments.comment_ID
				WHERE meta_key = 'rating'
				AND comment_post_ID = $pId
				AND comment_approved = '1'
				");
				
				$sumrating = $sumrating + $rating;
			
			}
			 
				
				if ( $sumcount > 0 ) :
				 
				$average = number_format($sumrating / $sumcount, 2);
				
				$adminratingtext = esc_attr(get_option('wcr_ratings_text'));
				if ($adminratingtext=='') {
					$ratingstext ='';
				} else {
					$ratingstext ='<span class="ratings-text">'.$adminratingtext.' '.$category_name.':</span> ';
				}
				
				echo '<div itemprop="aggregateRating" itemscope="" itemtype="http://schema.org/AggregateRating">'.$ratingstext.'<span itemprop="ratingValue">'.$average.'</span>/<span itemprop="bestRating">5</span> (<span itemprop="ratingCount">'.$sumcount.'</span> recenzii)</div>';
				
				endif;
			 
			endif;
		 
		}
		
}

function wcr_init(){
	register_setting('wcr_options','wcr_ratings_text');
}

add_action('admin_init','wcr_init');

function wcr_options_page(){
?>
	
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2>Woocommerce Category Rating Options</h2>
		
		<form action="options.php" method="post" id="wcr_options_form">
		<?php settings_fields('wcr_options'); ?>
			<h3><label for="rating-text">Custom rating text:</label>
			<input type="text" id="wcr_ratings_text" name="wcr_ratings_text" placeholder="Rating for: %category%" value="<?php echo esc_attr(get_option('wcr_ratings_text')); ?>" /></h3>
			
			<p><input class="button-primary" type="submit" name="submit" value="Save options" /></p>
		</form>
		
	</div>	
	
<?php
}

function wcr_plugin_menu(){
	add_options_page( 'Woocommerce Category Rating Settings', 'Category Ratings', 'manage_options', 'woocommerce-category-rating-plugin', 'wcr_options_page');
}

add_action('admin_menu', 'wcr_plugin_menu');


function my_plugin_action_links( $links ) {
   $links[] = '<a href="'. get_admin_url(null, 'options-general.php?page=woocommerce-category-rating-plugin') .'">Settings</a>';
   return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'my_plugin_action_links' );


?>