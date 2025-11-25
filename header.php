<?php
	defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta content='width=device-width, viewport-fit=cover, initial-scale=1.0' name='viewport'/>
		<link rel="profile" href="http://gmpg.org/xfn/11">
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

		<?php wp_head(); ?>
	</head>

	<body <?php body_class(); ?>>
		<div id="site" class="site">
			<?php get_template_part( 'parts/header' ); ?>
			
			<main id="site-content" class="content">