<?php
/**
 * `Direct message` quick action.
 *
 * @since 2.1
 */
if ( ! (bool) get_option( 'options_messages_enabled' ) ) {
    return;
}

$post_data = json_encode( [
	'id'	=> $listing->get_id(),
	'image'	=> $listing->get_logo() ? : c27()->image( 'marker.jpg' ),
	'title'	=> $listing->get_name(),
	'link'	=> $listing->get_link(),
    'author'=> $listing->get_author_id()
] );
wp_enqueue_script( 'mylisting-open-chat' );
?>
<li id="<?php echo esc_attr( $action['id'] ) ?>" class="<?php echo esc_attr( $action['class'] ) ?>">
    <a href="#" class="cts-open-chat" data-post-data="<?php echo esc_attr( $post_data ) ?>" data-user-id="<?php echo absint( $listing->get_author_id() ) ?>">
    	<?php echo c27()->get_icon_markup( $action['icon'] ) ?>
    	<span><?php echo $action['label'] ?></span>
    </a>
</li>
