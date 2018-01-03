<?php
/*
Template Name: Common Content
*/
?>



<?php get_header(); ?>



<section id="content_header">

<article id="content_header_copy">
<h1><?php wp_title("",true); ?></title></h1>


<?php while ( have_posts() ) : the_post(); ?>

<?php the_content(); // grab the page content ?>

<?php endwhile; // end of the loop. ?>


</article><!--close content_header_copy-->


</section><!--close content_header-->



<section id="content_content">

<!--article id="content_container"-->



<?php 
 $vi_page_content_category = get_post_meta($post->ID, "vi_page_content_category", true); 
 if($vi_page_content_category != "")
	echo do_shortcode(do_shortcode("[vi_content_by_category contentcat='".$vi_page_content_category."' showexpander='No']"));
?>





<!--/article><!--close content_container-->


<section id="feature_box">

        <?php
			$vi_banner_image = get_post_meta($post->ID, "vi_banner_image_url", true);
			$vi_banner_url = get_post_meta($post->ID, "vi_banner_link_url", true);
			$vi_banner_directurl = get_post_meta($post->ID, "vi_banner_link_directurl", true);			
			if($vi_banner_image != "")
			{
				$vi_banner_image_tag = "<img src='".$vi_banner_image."' alt='ad' />";
				if($vi_banner_directurl != "")
					$vi_banner_image_tag = "<a href='".$vi_banner_directurl."'>" . $vi_banner_image_tag . "</a>";
				else if($vi_banner_url != "")
					$vi_banner_image_tag = "<a href='".$vi_banner_url."'>" . $vi_banner_image_tag . "</a>";
		 		echo $vi_banner_image_tag; 
			} 
		?>

</section><!--close feature_box-->


</section><!--close content_content-->




<footer>

<?php get_footer(); ?>

</footer>


