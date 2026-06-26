<?php

if ( ! defined('ABSPATH') ) {
	exit;
}

$listing_feed = new \MyListing\Src\Queries\Listing_Feed();
$listings = $listing_feed->run($data);

if (! $data['template'] || in_array( $data['template'], [ 'grid' ], true ) ) {
	require locate_template( 'templates/listing-feed/grid.php' );
}

if ( $data['template'] === 'carousel' ) {
	require locate_template( 'templates/listing-feed/carousel.php' );
}

?>

<?php if ($data['is_edit_mode']): ?>
    <script type="text/javascript">case27_ready_script(jQuery); MyListing.Listing_Feed(); MyListing.ListingFeed_Carousel()</script>
<?php endif ?>