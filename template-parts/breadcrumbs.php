<?php
	declare(strict_types = 1);

	defined( 'ABSPATH' ) || exit;

	$breadcrumbs = new Breadcrumbs();
?>
<section class="breadcrumbs">
	<div class="container">
		<?php echo $breadcrumbs; ?>
	</div>
</section>