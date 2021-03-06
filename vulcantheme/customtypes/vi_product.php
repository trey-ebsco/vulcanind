<?php

//-----------register custom post type
  register_post_type( 'product',
    array(
      'labels' => array(
        'name' => __( 'Products' ), //this name will be used when will will call the products in our theme
        'singular_name' => __( 'Product' ),
		'add_new' => _x('Add New', 'product'),
		'add_new_item' => __('Add New Product'), //custom name to show up instead of Add New Post. Same for the following
		'edit_item' => __('Edit Product'),
		'new_item' => __('New Product'),
		'view_item' => __('View Product'),
      ),
      	'public' => true,
 	'show_ui' => true,
  	'hierarchical' => false, //it means we cannot have parent and sub pages
  	'capability_type' => 'post', //will act like a normal post
  	//'rewrite' => 'Product', //this is used for rewriting the permalinks
  	'query_var' => false,
  	'has_archive' => true,
  	'supports' => array( 'title',	'editor', 'thumbnail', 'excerpts', 'revisions') //the editing regions that will support
    )
  );


//----------------edit custom columns display for back-end 
add_filter("manage_edit-product_columns", "vi_product_columns");
register_taxonomy("productcategories", array("product"), array("hierarchical" => true, "label" => "Product Categories", "singular_label" => "Product Category", "rewrite" => array("slug" => "slproductcategories")));
add_action("manage_posts_custom_column", "vi_product_custom_columns");
add_action( 'add_meta_boxes', 'vi_product_add_custom_box' );
add_action( 'save_post', 'vi_save_product' );
add_action( 'save_post', 'vi_save_page_options' );
add_shortcode('vi_products_by_category', 'vi_draw_products_by_category');
add_action( 'wp_head' , 'vi_add_css_to_header');


function vi_product_columns($columns) //this function display the columns headings
{
	$columns = array(
		"cb" => "<input type=\"checkbox\" />",
		"title" => "Product Name",
		"productcategories" => "Product Categories",
		"exturl" => "Image Link",
		"displayorder" => "Display Order",
		"productimage" => "Image",
		"displayproduct" => "Display?"
	);
	return $columns;
}

function vi_product_custom_columns($column)
{
	global $post;
	if ("ID" == $column)               	    echo $post->ID; //displays title
	elseif ("exturl" == $column)       	    echo get_post_meta($post->ID, "productexturl", true); 
	elseif ("displayorder" == $column) 	  	echo get_post_meta($post->ID, "productdisplayorder", true); 
	elseif ("productcategories" == $column) echo get_the_term_list($post->ID, 'productcategories', '', ', ','');
	elseif ("productimage" == $column) 	    echo get_product_photo($post, "productimage");
	elseif ("displayproduct" == $column)    echo get_post_meta($post->ID, "productdisplayproduct", true); 	
}

/* Adds a box to the main column on the Post and Page edit screens */
function vi_product_add_custom_box() {
	global $post;
    add_meta_box( 'vi_product_displayproduct', __( 'Display?', 'vi_product_displayproduct_text' ),'vi_product_inner_custom_box_displayproduct', 'product', 'side', 'high');	
    add_meta_box( 'vi_product_displayorder', __( 'Display Order', 'vi_product_displayorder_text' ),'vi_product_inner_custom_box_displayorder', 'product', 'side', 'core');
    add_meta_box( 'vi_product_exturl', __( 'Link URL', 'vi_product_exturl_text' ),'vi_product_inner_custom_box_exturl', 'product', 'side', 'core');


  	// check for a template type and post new meta box
	$template_file = get_post_meta($post->ID,'_wp_page_template',TRUE);
  	if ($template_file == 'csf_category.php' || $template_file == 'csf_sub_category.php' || $template_file == 'ss_sub_category.php' || $template_file == 'capabilities.php' || $template_file == 'insf_product.php' || $template_file == 'order_summary.php') 
	{	
		add_meta_box( 'vi_pageoptions', __( 'Page Content Options', 'vi_add_page_content_to_pages_text' ), 'vi_add_page_content_to_pages', 'page', 'side', 'default');		
	}
}

function vi_add_page_content_to_pages( $post ) 
{
	global $post;
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'vi_product_nonce_pagecontent' );
	$template_file = get_post_meta($post->ID,'_wp_page_template',TRUE);
	if ($template_file == 'csf_category.php')
	{
		echo '<label for="vi_page_content_category_field"><strong>Highlight Category</strong></label><br>';
		echo "&nbsp;&nbsp;<select id='vi_page_content_category_field' name='vi_page_content_category_field' >";
		echo "<option value=''>No Caterogy</option>";
		$vi_page_content_category = get_post_meta($post->ID, "vi_page_content_category", true);  
		$hlCategories = get_categories( array('type'=>'post', 'taxonomy'=>'productcategories', 'hide_empty'=>0) );
		foreach($hlCategories as $hlCat)
		{
			echo "<option value='" . $hlCat->cat_name . "' " . selected($hlCat->cat_name,$vi_page_content_category, false) . " >";
			echo $hlCat->cat_name . "</option>";
		}  
		echo "</select><br><br>";
		
		
	}
	

	
	if($template_file == 'csf_sub_category.php' || $template_file == 'ss_sub_category.php' || $template_file == 'insf_product.php')
	{
		$slider_pro_slidedecks = get_slider_pro_entries();
		if(sizeof($slider_pro_slidedecks) > 0)
		{
			echo '<label for="vi_product_slidreprodeck_field"><strong>Slider Pro Slide Deck</strong></label> ';
			$savedValue = get_post_meta($post->ID, "productpagesliderprodeck", true);
			echo "<br>&nbsp;&nbsp;<select id='vi_product_slidreprodeck_field' name='vi_product_slidreprodeck_field'>";
			echo "<option value=''>None</option>";
			//$form->id
			//$form->is_active
			//$form->title
			foreach($slider_pro_slidedecks as $slider_pro_slidedeck)
			{
				//if($form->is_active)
					echo "<option value='".$slider_pro_slidedeck->id."' ".selected($slider_pro_slidedeck->id, $savedValue, false).">".$slider_pro_slidedeck->name."</option>";					
			}
			echo "</select><br><br>";	
		}		
	}
	
	if($template_file == 'ss_sub_category.php')
	{
	  $vi_page_content_tabs_category = get_post_meta($post->ID, "vi_page_content_tabs_category", true);
	  echo '<label for="vi_page_content_tabs_category_field"><strong>';
		   _e("Tab Content Category", 'vi_page_content_tabs_text' );
	  echo '</strong></label> ';
	  echo "&nbsp;&nbsp;<select id='vi_page_content_tabs_category_field' name='vi_page_content_tabs_category_field' ";
	  echo "onchange='vi_toggle_page_content_suboptions();'";
	  echo ">";
	  echo "<option value=''>No Tabs</option>";
	  $tabCategories = get_categories( array('type'=>'post', 'taxonomy'=>'tabcategories', 'hide_empty'=>0 ) );
	  foreach($tabCategories as $tabCat)
	  {
		echo "<option value='" . $tabCat->cat_name . "' " . selected($tabCat->cat_name,$vi_page_content_tabs_category, false) . " >";
		echo $tabCat->cat_name . "</option>";
	  }
	  echo "</select><br><br>";	
	}
	
	if($template_file == 'csf_sub_category.php')
	{
		$gravity_forms = get_gravity_forms();
		if(sizeof($gravity_forms) > 0)
		{
			echo '<label for="vi_product_gravityform_field"><strong>Gravity Form</strong></label> ';
			$savedValue = get_post_meta($post->ID, "productpagegravityform", true);
			echo "<br>&nbsp;&nbsp;<select id='vi_product_gravityform_field' name='vi_product_gravityform_field'>";
			echo "<option value=''>None</option>";
			//$form->id
			//$form->is_active
			//$form->title
			foreach($gravity_forms as $gravity_form)
			{
				//if($form->is_active)
					echo "<option value='".$gravity_form->id."' ".selected($gravity_form->id, $savedValue, false).">".$gravity_form->title."</option>";					
			}
			echo "</select><br><br>";	
		}
	}
	
	echo '<label for="vi_product_customcss_field"><strong>Custom CSS File</strong></label> ';
	$savedValue = get_post_meta($post->ID, "productpagecss", true);
	echo "<br>&nbsp;&nbsp;<select id='vi_product_customcss_field' name='vi_product_customcss_field'>";
	echo "<option value=''>None</option>";
	foreach (glob(get_stylesheet_directory()."/pagecss/*.css") as $filename)
	{
		$urlBase = get_stylesheet_directory()."/pagecss/";
		$cssFileName = substr($filename,strlen($urlBase));
		//$filename = str_replace(get_stylesheet_directory(),bloginfo('stylesheet_directory'),$filename);
		//$cssFileName = substr($cssFileName, 0, strpos($cssFileName, ".css"));
		echo "<option value='".$cssFileName."' ".selected($cssFileName, $savedValue, false).">".$cssFileName."</option>";
	} 	
	echo "</select><br><br>";	
	//add_action( 'wp_head' , 'vi_add_css_to_header');
	
	echo '<label for="vi_product_customcss_field"><strong>Footer Banner</strong></label> ';
	  // get saved value
  $vi_banner_image = get_post_meta($post->ID, "vi_banner_image_url", true);
  $vi_banner_url = get_post_meta($post->ID, "vi_banner_link_url", true);
  $vi_banner_directurl = get_post_meta($post->ID, "vi_banner_link_directurl", true);
  // The actual fields for data entry
  echo '<br>&nbsp;&nbsp;<label for="vi_banner_field">Select an image to display</label> <br>';
  echo "&nbsp;&nbsp;<select id='vi_banner_field' name='vi_banner_field'";
  echo " onchange=\"if(this.options[this.selectedIndex].value != ''){document.getElementById('vi_banner_preview').style.display='block';document.getElementById('vi_banner_preview').src=this.options[this.selectedIndex].value;} ";
  echo " else { document.getElementById('vi_banner_preview').style.display='none'; } \" ";
  echo ">";
  echo "<option value=''>No Banner</option>";
  $query_images_args = array('post_type' => 'attachment', 'post_mime_type'=>'image', 'post_status'=>'inherit', 'posts_per_page'=>-1);
  $query_images = new WP_Query($query_images_args);
  foreach($query_images->posts as $image)
  {
	$raq_fileName = substr($image->guid, strrpos($image->guid,'/')+1);
	if (substr($raq_fileName,0,7) == 'banner_')
	{
		echo "<option value='" . $image->guid . "'";
		if ( $image->guid==$vi_banner_image)
			echo " selected ";
		echo ">";
		echo $raq_fileName . "</option>";
	}
  }
  echo "</select>";
  echo "<br><img style='padding:5px;' id='vi_banner_preview' src='".$vi_banner_image."' ";
  if($vi_banner_image == "")
  	echo "style='display:none;'";
  echo " width='250'><br>";
  echo '&nbsp;&nbsp;<label for="vi_banner_url_field">Select a page to link to</label> ';
  echo "&nbsp;&nbsp;<select id='vi_banner_url_field' name='vi_banner_url_field'>";
  echo "<option value=''>Do Not Link</option>";
  
  foreach(get_pages() as $page)
  {
	$pageURL = get_page_link( $page->ID );
	echo "<option value='" . $pageURL . "'";
	if ( $pageURL==$vi_banner_url)
		echo " selected ";
	echo ">";
	echo $page->post_title . "</option>";
  }
  echo "</select>";  

  echo '<br><br>&nbsp;&nbsp;<label for="vi_banner_directurl_field">Direct link</label> ';
  echo "&nbsp;&nbsp;<input size=35 type='textbox' id='vi_banner_directurl_field' name='vi_banner_directurl_field' value='".$vi_banner_directurl."'><br>";

	
}


function vi_add_css_to_header()
{
	global $post;
	// Get current Template file name
	//$post_id = $_GET['post'] ? $_GET['post'] : $_POST['post_ID'] ;
	$savedCSSFileName = get_post_meta($post->ID, "productpagecss", true);
	if($savedCSSFileName != "")
	{
		$template_file = get_post_meta($post->ID,'_wp_page_template',TRUE);
		//if ($template_file == 'csf_category.php' || $template_file == 'csf_sub_category.php') 
		{
			echo "<link rel='stylesheet' id='msc_tabs_main'  href='";
			//echo bloginfo('stylesheet_directory')."/pagecss/test.css";
			echo bloginfo('stylesheet_directory')."/pagecss/".$savedCSSFileName;
			echo "' type='text/css' media='screen' />";
		}
	}
}

function vi_product_inner_custom_box_displayproduct( $post ) 
{
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'vi_product_nonce_displayproduct' );

  // The actual fields for data entry
  echo '<label for="vi_product_displayproduct_field">';
       _e("Display Product?", 'vi_product_displayproduct_text' );
  echo '</label> ';
  $savedValue = get_post_meta($post->ID, "productdisplayproduct", true);
  echo "<input type='checkbox' id='vi_product_displayproduct_field' name='vi_product_displayproduct_field' value='Yes' ";
  if($savedValue == "Yes" || $savedValue == "")
	echo " checked ";
  echo ">";

}

function vi_product_inner_custom_box_exturl( $post ) 
{
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'vi_product_nonce_exturl' );

  // The actual fields for data entry
  echo '<label for="vi_product_exturl_field">';
       _e("Product link URL", 'vi_product_exturl_text' );
  echo '</label> ';
  $savedValue = get_post_meta($post->ID, "productexturl", true);
  echo "<input type='textbox' id='vi_product_exturl_field' name='vi_product_exturl_field' value='" . $savedValue . "' >";
}

function vi_product_inner_custom_box_displayorder( $post ) 
{
  // Use nonce for verification
  wp_nonce_field( plugin_basename( __FILE__ ), 'vi_product_nonce_displayorder' );

  // The actual fields for data entry
  echo '<label for="vi_product_displayorder_field">';
       _e("In Category Display Order", 'vi_product_displayorder_text' );
  echo '</label> ';
  echo "<select id='vi_product_displayorder_field' name='vi_product_displayorder_field'>";
  $productdisplayorder = get_post_meta($post->ID, "productdisplayorder", true);
  for($i=1;$i<25;$i++)
  {
	echo "<option value='" . $i . "'";
	if($productdisplayorder == "" && $i==5)
		echo " selected ";
	elseif ( $i==$productdisplayorder)
		echo " selected ";
	echo ">" . $i . "</option>";
  }
  echo "</select>";
}

function get_product_photo( $post )
{
	$productIndex = 0;

	if (has_post_thumbnail( $post->ID ) )
	{
		$featuredImage = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
		echo "<img src='".$featuredImage[0]."'>";
	}
	else
	{
		$productImages = get_children("post_parent=$post->ID&post_type=attachment&post_mime_type=image&orderby=menu_order ASC, ID ASC", ARRAY_A);
		$productImageArray = array_values($productImages);
		echo wp_get_attachment_link($productImageArray[$productIndex]['ID'], 'thumbnail', true);
	}
}

function get_gravity_forms()
{
    global $wpdb;
    $form_table_name =  $wpdb->prefix . "rg_form";
    $sql = "SELECT f.id, f.title, f.date_created, f.is_active FROM ".$form_table_name." f ORDER BY title ASC";
    //Getting all forms
    $forms = $wpdb->get_results($sql);
    return $forms;
}

function get_slider_pro_entries()
{
    global $wpdb;
	$prefix = $wpdb->prefix;
	$sliders = $wpdb->get_results("SELECT * FROM " . $prefix . "sliderpro_sliders ORDER BY id");
    return $sliders;	
}

function vi_save_page_options( $post_id )
{
  // verify if this is an auto save routine. 
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return;

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if ( !wp_verify_nonce( $_POST['vi_product_nonce_pagecontent'], plugin_basename( __FILE__ ) ) )
      return;

  
  // Check permissions
  if ( 'page' == $_POST['post_type'] ) 
  {
    if ( !current_user_can( 'edit_page', $post_id ) )
        return;
  }
  else
  {
    if ( !current_user_can( 'edit_post', $post_id ) )
        return;
  }
  
  update_post_meta($post_id, "productpagecss", $_POST['vi_product_customcss_field']);
  update_post_meta($post_id, "vi_page_content_category", $_POST['vi_page_content_category_field']);  	
  update_post_meta($post_id, "vi_banner_image_url", $_POST['vi_banner_field']);
  update_post_meta($post_id, "vi_banner_link_url", $_POST['vi_banner_url_field']);
  update_post_meta($post_id, "vi_banner_link_directurl", $_POST['vi_banner_directurl_field']);
  update_post_meta($post_id, "productpagegravityform", $_POST['vi_product_gravityform_field']);
  update_post_meta($post_id, "productpagesliderprodeck", $_POST['vi_product_slidreprodeck_field']);
  update_post_meta($post_id, "vi_page_content_tabs_category", $_POST['vi_page_content_tabs_category_field']);
  
}

function vi_save_product( $post_id ) {
  // verify if this is an auto save routine. 
  // If it is our form has not been submitted, so we dont want to do anything
  if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return;

  // verify this came from the our screen and with proper authorization,
  // because save_post can be triggered at other times

  if ( !wp_verify_nonce( $_POST['vi_product_nonce_displayorder'], plugin_basename( __FILE__ ) ) )
      return;

  
  // Check permissions
  if ( 'page' == $_POST['post_type'] ) 
  {
    if ( !current_user_can( 'edit_page', $post_id ) )
        return;
  }
  else
  {
    if ( !current_user_can( 'edit_post', $post_id ) )
        return;
  }

  // OK, we're authenticated: we need to find and save the data
  update_post_meta($post->ID, "categories", $_POST["categories"]);

  $vi_product_exturl = $_POST['vi_product_exturl_field'];
  update_post_meta($post_id, "productexturl", $vi_product_exturl);

  $vi_product_displayorder = $_POST['vi_product_displayorder_field'];
  update_post_meta($post_id, "productdisplayorder", $vi_product_displayorder);
  
  $vi_product_displayproduct = $_POST['vi_product_displayproduct_field'];
  update_post_meta($post_id, "productdisplayproduct", $vi_product_displayproduct);
  


    
}

/////////////////  SHORT CODE FXNS /////////////////////

function vi_draw_products_by_category( $atts )
{
	global $post;
	$out = "";
	//$out .= get_post_meta( $post->ID, '_wp_page_template', true ); 
	// productid: product id, e.g. "113", to show single product.  0 to not display a single product
	// productcat: category name, e.g. 'Air Force Coins', to display - ignored with producttitle set.
	// showgallery: show Lightbox gallery on click.  If 'false' then uses URL in product as link.
	// poststoshow: number of products to show - ignored with producttitle set.
	// postsperrow: number of products to show per row - ignored with producttitle set.
	// showtags: hide/show the 'New' and 'Hot' tags.
	// showonlystandard: if 'true' shows the Flagship Coin from each category
	// sectiontitle: banner text.
	//
	// order of priority: producttitle, showonlystandard, productcat
	extract(shortcode_atts(
		array(
			'productid' => 0,
			'productcat'  => '', 
			'showgallery' => 'false', 
			'poststoshow' => -1, 
			'postsperrow' => 0,
			'showtags' => 'all', 
			'showonlystandard' => 'false',
			'sectiontitle' => ''
		), $atts));
	setup_postdata($post);

	// Get Top X products by category where Display Coin = Yes Order by Display Order
	if($productid != 0)
	{
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => 1,
			'p' => $productid
	 	);
	}
	else 
	{
		$args = array(
			'post_type' => 'product',
			'posts_per_page' => $poststoshow,
			'productcategories' => get_term_by('name',$productcat,'productcategories')->slug,
			'meta_key' => 'productdisplayorder',
			'orderby' => 'meta_value_num',
			'order' => 'ASC',
			'meta_query' => array(
				array(
					'key'     => 'productdisplayproduct',
					'value'   => 'Yes',
					'compare' => '='
				)
			)
	 	);
	}
	
	query_posts($args);
	$productsDisplayed = 0;
	while (have_posts()) : the_post();
		$productIsHot = get_post_meta($post->ID, "productishot", true);
		$productDate = get_the_date();
		$postAgeInDays = date_diff(date_create(), date_create($productDate))->format("%d");
		$productExtURL = get_post_meta($post->ID, "productexturl", true);
		$productName = the_title('','',false);
		
		if (has_post_thumbnail( $post->ID ) )
		{
			$featuredImage = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'single-post-thumbnail' );
			$productImageURL= $featuredImage[0];
		}
		else
		{
			$productImages = get_children("post_parent=$post->ID&post_type=attachment&post_mime_type=image&orderby=menu_order ASC, ID ASC", ARRAY_A);
			$productImageArray = array_values($productImages);
			$productImageURL = wp_get_attachment_url($productImageArray[0]['ID'], 'thumbnail', true);
		}		
		
		//$productImages = get_children("post_parent=$post->ID&post_type=attachment&post_mime_type=image&orderby=menu_order ASC, ID ASC", ARRAY_A);
		//$productImageArray = array_values($productImages);
		//$productImageURL = wp_get_attachment_url($productImageArray[0]['ID'], 'thumbnail', true);
		$productsDisplayed++;
		
		$out .= "<article class='store_category_container'>";
		$out .= "<h1 class='store'><a href='".$productExtURL."'>".$productName."</a></h1>";
		$out .= "<article class='store_category_inside'>";
		$out .= "<a href='".$productExtURL."'><img src='".$productImageURL."' alt='".$productName."' width=124 class='tn_floatright' /></a>";
		$out .= "<p>".get_the_content()."</p>";
		$out .= "<p><a href='".$productExtURL."'><strong>>></strong> View ".$productName."</a></p>";
		$out .= "</article>";
		$out .= "</article>";

	endwhile;
	wp_reset_query();
	return $out;
}

function vi_draw_single_product( $atts )
{
	global $post;
	$out = "";
	extract(shortcode_atts(array('productid' => 0, 'producttitle' => '', 'productcat'  => '', 'showgallery' => 'false', 'showonlystandard' => 'false', 'poststoshow' => -1), $atts));
	setup_postdata($post);
	if($producttitle != "")
		query_posts( array('post_type'=>'Product','post__in'=> array($producttitle)));
	else if($productid > 0)
		query_posts( 'post_type=Product&p=' . $productid );
	else // array of categories
	{
		if($showonlystandard == "false")
		{
			query_posts( array('post_type'=>'Product','cat' => get_cat_ID($productcat),'meta_key' => 'productdisplayorder', 'orderby' => 'meta_value_num', 'order'=>'ASC'));
		}
		else
		{
			
			$args = array(
				'post_type' => 'Product',
				'posts_per_page' => $poststoshow,
				'cat' => get_cat_ID($productcat),
				'meta_key' => 'productdisplayorder',
				'orderby' => 'meta_value_num',
				'order' => 'ASC',
				'meta_query' => array(
					array(
						'key'     => 'productisflagship',
						'value'   => 'Yes',
						'compare' => '='
					)
				)
		 	);
			query_posts( $args );
		}
	}

	while (have_posts()) : the_post();
		$productIsHot = get_post_meta($post->ID, "productishot", true);
		$productExtURL = get_post_meta($post->ID, "productexturl", true);
		$productName = the_title('','',false);
		$productImages = get_children("post_parent=$post->ID&post_type=attachment&post_mime_type=image&orderby=menu_order ASC, ID ASC", ARRAY_A);
		$productImageArray = array_values($productImages);
		$productImageURL = wp_get_attachment_url($productImageArray[0]['ID'], 'thumbnail', true);

		$out .= "<div style='position:relative;display:inline-block;'>";
		if($showgallery == 'false')
		{
			$out .= "<a href='" . $productExtURL . "' border=0 >";
		}
		else
		{
			$out .= "<a href='#product_content_" . $post->ID . "' border=0 rel='product_content_" . $post->ID . "' class='colorbox-link'>";
		}
		$out .= "<img id='" . $post->ID . "_product' src='".$productImageURL."'><br>";
		$out .= $productName;
		$out .= "</a>";
	
		if($showgallery == 'true')
		{
			$out .= "<div style='display:none;'>";
			$out .= "<div style='width:600px;height:300px;' id='product_content_" . $post->ID . "' >";
			$out .= "<div style='width:340px;height:100%;float:left;'>";
			for($i=1;$i<count($productImageArray);$i++)
			{
				$out .= "<img src='" . wp_get_attachment_url($productImageArray[$i]['ID'], 'thumbnail', true) . "'><br>";
			}
			$out .= "</div>";
			$out .= "<div style='width:240px;height:100%;float:left;border:1px solid black;'>";
			$out .= str_replace("\r","<br />",get_the_content());
			$out .= "</div>";
			$out .= "</div>";
			$out .= "</div>";
		}
		if($productIsHot == "Yes")
		{
			$isHotImageURL = get_bloginfo('stylesheet_directory') . "/customtypes/images/IsHot.png";
			$out .= "<span style='position:absolute;top:1px;left:1px;'><img src='$isHotImageURL'></span>";
		}
		$out .= "</div>";
	endwhile;
	wp_reset_query();
	return $out;



}

///////////////  END SHORT CODE FXNS ///////////////////
?>

