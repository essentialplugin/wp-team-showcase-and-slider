<?php
/**
 * `wp-team-slider` Shortcode
 * 
 * @package WP Team Showcase and Slider
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function get_wp_tsas_showcase_slider( $atts, $content = null ){
	
	// SiteOrigin Page Builder Gutenberg Block Tweak - Do not Display Preview
	if( isset( $_POST['action'] ) && ($_POST['action'] == 'so_panels_layout_block_preview' || $_POST['action'] == 'so_panels_builder_content_json') ) {
		return "[wp-team-slider]";
	}

	// Divi Frontend Builder - Do not Display Preview
	if( function_exists( 'et_core_is_fb_enabled' ) && isset( $_POST['is_fb_preview'] ) && isset( $_POST['shortcode'] ) ) {
		return '<div class="tsas-builder-shrt-prev">
					<div class="tsas-builder-shrt-title"><span>'.esc_html__('Team Showcase Grid View - Shortcode', 'wp-team-showcase-and-slider').'</span></div>
					wp-team-slider
				</div>';
	}

	// Fusion Builder Live Editor - Do not Display Preview
	if( class_exists( 'FusionBuilder' ) && (( isset( $_GET['builder'] ) && $_GET['builder'] == 'true' ) || ( isset( $_POST['action'] ) && $_POST['action'] == 'get_shortcode_render' )) ) {
		return '<div class="tsas-builder-shrt-prev">
					<div class="tsas-builder-shrt-title"><span>'.esc_html__('Team Showcase Grid View - Shortcode', 'wp-team-showcase-and-slider').'</span></div>
					wp-team-slider
				</div>';
	}

	// Global Some Variable
	global $post;

	// setup the query
	extract(shortcode_atts(array(
			'limit'				=> -1,
			'category'			=> '',
			'design'			=> 'design-1',
			'slides_column'		=> 3,
			'slides_scroll'		=> 1,
			'dots'				=> 'true',
			'arrows'			=> 'true',
			'autoplay'			=> 'true',
			'autoplay_interval'	=> 3000,
			'speed'				=> 300,
			'popup'				=> 'true',
			'order'				=> 'DESC',
			'orderby'			=> 'date',
			'lazyload'			=> '',
			'extra_class'		=> '',
			'className'			=> '',
			'align'				=> '',
	), $atts, 'wp-team-slider'));

	$shortcode_designs 	= tsas_designs();
	$popup_id			= wp_tsas_get_unique();
	$limit				= ! empty( $limit )					? $limit						: -1;
	$category			= ! empty( $category )				? explode(',', $category)		: '';
	$design				= ( $design && ( array_key_exists( trim( $design ), $shortcode_designs ) ) )	? trim( $design )	: 'design-1';
	$teampopup			= ( $popup == 'false' )				? 'false'						: 'true';
	$dots				= ( $dots == 'false' )				? 'false'						: 'true';
	$arrows				= ( $arrows == 'false' )			? 'false'						: 'true';
	$autoplay			= ( $autoplay == 'false' )			? 'false'						: 'true';
	$autoplay_interval	= ! empty( $autoplay_interval )		? $autoplay_interval			: 3000;
	$speed				= ! empty( $speed )					? $speed						: 300;
	$slides_column		= ! empty( $slides_column )			? $slides_column				: 3;
	$slides_scroll		= ! empty( $slides_scroll )			? $slides_scroll				: 1;
	$order				= ( strtolower( $order ) == 'asc' )	? 'ASC'							: 'DESC';
	$orderby			= ! empty( $orderby )				? $orderby						: 'date';
	$popup_class		= ( $teampopup == "true" )			? 'tsas-enable-popup'			: '';
	$lazyload			= ( $lazyload == 'ondemand' || $lazyload == 'progressive' ) ? $lazyload : ''; // ondemand or progressive
	$align				= ! empty( $align )					? 'align'.$align				: '';
	$extra_class		= $extra_class .' '. $align .' '. $className;
	$extra_class		= wp_tsas_sanitize_html_classes( $extra_class );

	// Popup Configuration
	$slider_conf = compact('slides_column', 'slides_scroll', 'dots', 'arrows', 'autoplay', 'autoplay_interval', 'speed', 'lazyload');
	
	// Shortcode file
	$design_file_path	= WP_TSAS_DIR . '/templates/designs/' . $design . '.php';
	$design_file		= (file_exists($design_file_path)) ? $design_file_path : '';

	/***** Enqueus Required Script *****/
	// First Dequeue if grid shortcode is placed before the slider shortcode
	wp_dequeue_script( 'tsas-public-script' );
	wp_enqueue_script( 'wpos-slick-jquery' );
	wp_enqueue_script( 'wpos-magnific-popup-jquery' );
	wp_enqueue_script( 'tsas-public-script' );

	// Some Variables
	$popup_html	= '';
	$count		= 0;
	$i			= 1;

	ob_start();	

	// defualt variables
	$post_type	= 'team_showcase_post';

	// Post argument
	$args = array ( 
				'post_type'			=> $post_type,
				'orderby'			=> $orderby,
				'order'				=> $order,
				'posts_per_page'	=> $limit,
	);
 
 	// taxonomy query
	if( $category != "" ){
		$args['tax_query'] = array( array( 'taxonomy' => 'tsas-category', 'field' => 'term_id', 'terms' => $category) );
	}

	// wp Query
	$query = new WP_Query( $args );
	$post_count = $query->post_count;

	// wp query condition
	if ( $query->have_posts() ) { ?>
		<div class="wp-tsas-slider-wrap <?php echo esc_attr($extra_class); ?>">
			<div id="wp-tsas-slider-<?php echo esc_attr($popup_id); ?>" class="wp_teamshowcase_slider <?php echo esc_attr($popup_class); ?> <?php echo esc_attr($design); ?>">
				<?php
				while ( $query->have_posts() ) : $query->the_post();
					$count++;
					$feat_image			= wp_get_attachment_url( get_post_thumbnail_id() );
					$member_designation	= get_post_meta( $post->ID, '_member_designation', true );
					$member_department	= get_post_meta( $post->ID, '_member_department', true );
					$skills				= get_post_meta( $post->ID, '_skills', true );
					$member_experience	= get_post_meta( $post->ID, '_member_experience', true );
					$facebook_link		= get_post_meta( $post->ID, '_facebook_link', true );
					$google_link		= get_post_meta( $post->ID, '_google_link', true ); 
					$likdin_link		= get_post_meta( $post->ID, '_likdin_link', true );
					$twitter_link		= get_post_meta( $post->ID, '_twitter_link', true );
					$css_class			="team-slider";
					$class				= '';

					// Include shortcode html file
					if( $design_file ) {
						include( $design_file );
					}
					// Include Popup html file
					if( $teampopup == "true" ) {
						ob_start();
						include(WP_TSAS_DIR. '/templates/popup/popup-design-1.php');
						$popup_html .= ob_get_clean();
					}

				$i++;
				endwhile;
				 ?>
			</div>
			<?php echo $popup_html; // Print Popup HTML ?>
			<div class="slider-conf-data" data-conf="<?php echo htmlspecialchars(json_encode($slider_conf)); ?>"></div>
		</div>
	<?php
	wp_reset_postdata(); 
	return ob_get_clean();
	}
}
// slider shortcode action
add_shortcode( 'wp-team-slider', 'get_wp_tsas_showcase_slider' );