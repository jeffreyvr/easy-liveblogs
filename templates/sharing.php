<?php if ( ! elb_display_social_sharing() ) {
		return;
} ?>
<div class="elb-liveblog-post-sharing">
	<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo elb_get_entry_url(); ?>" target="_blank" title="<?php _e( 'Share via Facebook', ELB_TEXT_DOMAIN ); ?>">
		<?php echo elb_liveblog_get_social_logo( 'facebook' ); ?>
	</a>

	<a href="https://twitter.com/intent/tweet?text=<?php elb_entry_title(); ?> <?php echo elb_get_entry_url(); ?>" target="_blank" title="<?php _e( 'Share via Twitter', ELB_TEXT_DOMAIN ); ?>">
		<?php echo elb_liveblog_get_social_logo( 'twitter-alt' ); ?>
	</a>

	<a href="mailto:?&subject=<?php elb_entry_title(); ?>&body=<?php echo elb_get_entry_url(); ?>" target="_blank" title="<?php _e( 'Share via email', ELB_TEXT_DOMAIN ); ?>">
		<?php echo elb_liveblog_get_social_logo( 'mail' ); ?>
	</a>
</div>
