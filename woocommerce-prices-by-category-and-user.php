<?php
/*
Woocommerce category discounts by user 
*/
if ( !class_exists( 'Woo_Discounted_Prices' ) ):
class lV_Woo_Discounted_Prices {
	var $products_discount_calculated = false;
	var $discount_details = array();
	public function __construct() {
		
		$this->register_post_types();
		
		add_action('add_meta_boxes', array($this,'lv_add_custom_meta_box'));
		add_action( 'save_post', array($this,'lv_save_meta_fields'));
		add_action( 'new_to_publish', array($this,'lv_save_meta_fields') );
		add_filter('manage_product_discounts_posts_columns', array($this,'lv_columns_head'));
		add_action('manage_product_discounts_posts_custom_column', array($this,'lv_columns_content'), 10, 2);
		
		if (is_user_logged_in()){
			$user_id = get_current_user_id();
			$this->discount_details = $this->get_discounts_by_user_id($user_id);
		}
		
		add_filter('woocommerce_get_price', array($this,'lv_woo_custom_price'), 10, 2);		
		
		add_filter( 'woocommerce_variation_prices', array($this,'lv_woo_variation_prices'),10,3);
		
	}	
	
	function get_discounts_by_user_id($user_id){
		$discounts = array();
		$args = array(
			'post_type'              => array( 'product_discounts' ),
			'post_status'            => array( 'publish' ),
			'meta_query'			 =>array(
										array(
											'key'     => 'discount_user',
											'value'   => $user_id,
											'compare' => '='
									     )
									   )
		);
		$query = new WP_Query( $args );
		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				$product_cat = get_post_meta(get_the_id(),"product_cat",true);
				$discount_type = get_post_meta(get_the_id(),"discount_type",true);
				$discount_number = get_post_meta(get_the_id(),"discount_number",true);
				if($product_cat != "" && $discount_type != "" && $discount_number != ""){
					$temp = array("product_cat"=>$product_cat,"discount_type"=>$discount_type,"discount_number"=>$discount_number);
					$discounts[]=$temp;
				}
			}
		} else {
		}
		wp_reset_postdata();	
		return $discounts;
	}
	
	function get_discounted_price($price,$discount){
		$discount_type = $discount['discount_type'];
		$discount_number = $discount['discount_number'];
		if($discount_type == "percentage_discount"){
			$discount_amount = ($discount_number/100)*$price;
			$price = $price - $discount_amount;
		}else{
			$discount_amount = $discount_number;
			$price = $price - $discount_amount;
		}
		if($price < 0) $price = 0;
		return $price;
	}
	
	function lv_woo_custom_price($price, $product) {
		if (!is_user_logged_in()) return $price;
		$discounts = $this->discount_details;
		$caliculated = false;
		if(is_array($discounts) && sizeof($discounts) > 0):
			foreach($discounts as $discount){
				if($caliculated == false && (has_term( $discount['product_cat'], 'product_cat' ,$product->ID) || has_term( $discount['product_cat'], 'product_cat' ,$product->id))){
					$price = $this->get_discounted_price($price,$discount);
					$caliculated = true;
				}
			}			
		endif;
		return $price;
	}
	function lv_woo_variation_prices($prices_array_hash, $product, $display ){
		if (!is_user_logged_in()) return $price;
		$discounts = $this->discount_details;
		$caliculated = false;
		if(is_array($discounts) && sizeof($discounts) > 0):
			foreach($discounts as $discount){
				if($caliculated == false && (has_term( $discount['product_cat'], 'product_cat' ,$product->ID) || has_term( $discount['product_cat'], 'product_cat' ,$product->id))){
					foreach($prices_array_hash['price'] as $k=>$v){
						$price = $this->get_discounted_price($v,$discount); 	
						$prices_array_hash['price'][$k]=$price;
					}					
				}
			}
		endif;
		return $prices_array_hash;
	}
	
	public static function register_post_types() {
		if ( post_type_exists('product_discounts') ) {
			return;
		}

		register_post_type( 'product_discounts',
			apply_filters( 'woocommerce_register_post_type_product_discounts',
				array(
					'labels'              => array(
							'name'                  => __( 'Product Discounts', 'woocommerce' ),
							'singular_name'         => __( 'Product Discount', 'woocommerce' ),
							'menu_name'             => _x( 'Product Diacounts', 'Admin menu name', 'woocommerce' ),
							'add_new'               => __( 'Add Discount', 'woocommerce' ),
							'add_new_item'          => __( 'Add New Discount', 'woocommerce' ),
							'edit'                  => __( 'Edit', 'woocommerce' ),
							'edit_item'             => __( 'Edit Discount', 'woocommerce' ),
							'new_item'              => __( 'New Discount', 'woocommerce' ),
							'view'                  => __( 'View Discount', 'woocommerce' ),
							'view_item'             => __( 'View Discounts', 'woocommerce' ),
							'search_items'          => __( 'Search Discounts', 'woocommerce' ),
							'not_found'             => __( 'No Discounts found', 'woocommerce' ),
							'not_found_in_trash'    => __( 'No Discounts found in trash', 'woocommerce' ),
							'parent'                => __( 'Parent Discount', 'woocommerce' ),
							'featured_image'        => __( 'Discount Image', 'woocommerce' ),
							'set_featured_image'    => __( 'Set Discount image', 'woocommerce' ),
							'remove_featured_image' => __( 'Remove Discount image', 'woocommerce' ),
							'use_featured_image'    => __( 'Use as Discount image', 'woocommerce' ),
							'insert_into_item'      => __( 'Insert into Discount', 'woocommerce' ),
							'uploaded_to_this_item' => __( 'Uploaded to this Discount', 'woocommerce' ),
							'filter_items_list'     => __( 'Filter Discounts', 'woocommerce' ),
							'items_list_navigation' => __( 'Discounts navigation', 'woocommerce' ),
							'items_list'            => __( 'Discounts list', 'woocommerce' ),
						),
					'description'         => __( 'This is where you can add new product Discounts to your store.', 'woocommerce' ),
					'public'              => false,
					'show_ui'             => true,
					'capability_type'     => 'product',
					'map_meta_cap'        => true,
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'hierarchical'        => false, // Hierarchical causes memory issues - WP loads all records!
					'rewrite'             => false,
					'query_var'           => true,
					'supports'            => array( 'title'),
					'has_archive'         => false,
					'show_in_nav_menus'   => false,
					'show_in_menu'        => 'edit.php?post_type=product',
				)
			)
		);

		
	}
	
	function lv_add_custom_meta_box(){
		add_meta_box(  'discounts_meta_box', 'Options', array($this,'lv_show_discounts_meta_box'), 'product_discounts', 'normal', 'high' );	
	}
	
	function lv_show_discounts_meta_box(){
		global $post;
		$selected_cat=get_post_meta($post->ID,"product_cat",true);
		$selected_cat = ($selected_cat)?$selected_cat:0;
		$args = array(
			'show_option_none' => __( 'Select Product category', "woocommerce" ),
			'show_count'       => 0,
			'orderby'          => 'name',
			'echo'             => 0,
			'hide_empty'       => 0,
			'selected'         => $selected_cat,
			'hierarchical'     => 1,
			'name'			   =>'product_cat',
			'taxonomy'         => 'product_cat',
		);	
		$categories  = wp_dropdown_categories( $args );
		
		$selected_user=get_post_meta($post->ID,"discount_user",true);
		$selected_user = ($selected_user)?$selected_user:0;		
		$args = array(
				'show_option_none'        => __( 'Select User', "woocommerce" ),
				'orderby'                 => 'display_name',
				'order'                   => 'ASC',
				'show'                    => 'display_name',
				'echo'             		  => 0,
				'selected'                => $selected_user,
				'name'					  =>'discount_user'
		
		);
		$users = wp_dropdown_users($args);
		
		$discount_type=get_post_meta($post->ID,"discount_type",true);
		
		$discount_number=get_post_meta($post->ID,"discount_number",true);
		$discount_number = ($discount_number)?$discount_number:0;
		?>
        <table class="form-table">
        	<tbody>
            	<tr>
                	<td>Select Product Category</td>
                    <td><?php echo $categories;?></td>
                </tr>
                <tr>
                	<td>Select User</td>
                    <td><?php echo $users;?></td>
                </tr>
                <tr>
                	<td>Discount Type</td>
                    <td>
                    	<select name="discount_type">
                        	<option value="">Discount Type</option>
                            <option <?php echo ($discount_type == "fixed_amount")?"selected=\"selected\"":"";?>  value="fixed_amount">Price Discount</option>
                        	<option <?php echo ($discount_type == "percentage_discount")?"selected=\"selected\"":"";?> value="percentage_discount">Percentage Discount</option>
                        </select>
                    </td>
                </tr>
                <tr>
                	<td>Discount</td>
                    <td>
                    	<input type="number" value="<?php echo $discount_number;?>"  name="discount_number" />
                    </td>
                </tr>
            </tbody>
        </table>
        <?php
		wp_nonce_field( basename( __FILE__ ), 'lv_our_nonce' );
	}
	
	function lv_save_meta_fields( $post_id ){
		if (!isset($_POST['lv_our_nonce']) || !wp_verify_nonce($_POST['lv_our_nonce'], basename(__FILE__)))
      		return 'nonce not verified';

		  // check autosave
		  if ( wp_is_post_autosave( $post_id ) )
			  return 'autosave';
		
		  //check post revision
		  if ( wp_is_post_revision( $post_id ) )
			  return 'revision';
			  
		  // check permissions
		  if ( 'product_discounts' == $_POST['post_type'] ) {
			  if ( ! current_user_can( 'edit_page', $post_id ) )
				  return 'cannot edit page';
			  } elseif ( ! current_user_can( 'edit_post', $post_id ) ) {
				  return 'cannot edit post';
		  }
		  
		  $product_cat = $_POST['product_cat']; 
		  update_post_meta($post_id,"product_cat",$product_cat);
		  
		  $discount_user = $_POST['discount_user']; 
		  update_post_meta($post_id,"discount_user",$discount_user);
		  
		  $discount_type = $_POST['discount_type']; 
		  update_post_meta($post_id,"discount_type",$discount_type);
		  
		  $discount_number = $_POST['discount_number']; 
		  update_post_meta($post_id,"discount_number",$discount_number);
		  
	 }
	 function lv_columns_head($defaults){
		 unset($defaults['date']);
		 $defaults['product_cat'] = "Category";
		 $defaults['discount'] = "Discount";
		 $defaults['date'] = "Date";
		 return $defaults;
	 }
	 
	 function lv_columns_content($column_name, $post_id){
		 if($column_name == "product_cat"){
			 	$product_cat = get_post_meta($post_id,"product_cat",true);
				if($product_cat){
					$category = get_term_by('id', $product_cat, 'product_cat'); 
					echo $category->name;
				}
		 }
		 if($column_name == "discount"){
			 	$discount_type = get_post_meta($post_id,"discount_type",true);
				$discount_number = get_post_meta($post_id,"discount_number",true);
				if($discount_type == "percentage_discount"){
					echo $discount_number."%";	
				}else{
					echo $discount_number;	
				}
		 }
		 
	 }
		  
}
new lV_Woo_Discounted_Prices();
endif;
?>
