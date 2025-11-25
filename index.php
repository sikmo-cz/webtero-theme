<?php

	declare(strict_types = 1);

	defined( 'ABSPATH' ) || exit;

	$load_view = apply_filters( 'webtero/theme/load_view', 'template-parts/single' );

	get_header();

		get_template_part( $load_view );

	get_footer();