<?php
/*
Plugin Name: Spam Notifier
Plugin URI: https://wordpress.org/plugins/spam-notifier/
Description: The plugin sends an email message when a comment goes to the spam folder.
Version: 1.00
Author: Flector
Author URI: https://profiles.wordpress.org/flector#content-plugins
*/ 

$pluginpage = $_SERVER["REQUEST_URI"];
if(strpos($pluginpage, 'spam-notifier.php') == true){
if ( isset($_POST['submit']) ) {
    if (!isset($_POST['opt_comments'])) $opt_comments = '0'; else $opt_comments = '1';
    if (!isset($_POST['opt_trackbacks'])) $opt_trackbacks = '0'; else $opt_trackbacks = '1';
    update_option('sn_opt_comments', $opt_comments);
	update_option('sn_opt_trackbacks', $opt_trackbacks);
}}

function sn_init() {
    $opt_comments = '1';
	$opt_trackbacks = '1';
    add_option('sn_opt_comments', $opt_comments);
	update_option('sn_opt_comments', $opt_comments);
	add_option('sn_opt_trackbacks', $opt_trackbacks);
	update_option('sn_opt_trackbacks', $opt_trackbacks);
}
add_action('activate_spam-notifier/spam-notifier.php', 'sn_init');


function wp_notify_spam($comment_id) {
	global $wpdb;
	
	$comment = $wpdb->get_row("SELECT * FROM $wpdb->comments WHERE comment_ID='$comment_id' LIMIT 1");
	$post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE ID='$comment->comment_post_ID' LIMIT 1");
	$blogname = get_bloginfo('name');
	$comment_author_domain = @gethostbyaddr($comment->comment_author_IP);
	$author  = get_userdata( $post->post_author );	

	$opt_comments = get_option('sn_opt_comments');
	$opt_trackbacks = get_option('sn_opt_trackbacks');
	if (($opt_comments == '0') AND (get_comment_type($comment) == 'comment' ))  {return;}
	if (($opt_trackbacks == '0') AND (get_comment_type($comment) != 'comment' )) {return;}
	
	if ($comment->comment_approved == 'spam') {

		$notify_message  = '[Spam Notifier] ' . sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
		$notify_message .= sprintf( __('Author : %1$s (IP: %2$s , %3$s)'), $comment->comment_author, $comment->comment_author_IP, $comment_author_domain ) . "\r\n";
		$notify_message .= sprintf( __('E-mail : %s'), $comment->comment_author_email ) . "\r\n";
		$notify_message .= sprintf( __('URL    : %s'), $comment->comment_author_url ) . "\r\n";
		$notify_message .= sprintf( __('Whois  : http://ws.arin.net/cgi-bin/whois.pl?queryinput=%s'), $comment->comment_author_IP ) . "\r\n";
		$notify_message .= __('Comment: ') . "\r\n" . $comment->comment_content . "\r\n\r\n";
		$notify_message .= __('You can see all comments on this post here: ') . "\r\n";
		$subject = sprintf( __('[%1$s] Comment: "%2$s"'), $blogname, $post->post_title );
		$notify_message .= get_permalink($comment->comment_post_ID) . "#comments\r\n\r\n";
		$del_nonce = esc_html( '_wpnonce=' . wp_create_nonce( "delete-comment_$comment->comment_ID" ) );
		$notify_message .= sprintf( __('Delete it: %s'), get_bloginfo('url') . "/wp-admin/comment.php?c=$comment_id&action=delete&$del_nonce" ) . "\r\n";
		$notify_message .= sprintf( __('Approve it: %s'), get_bloginfo('url') . "/wp-admin/comment.php?c=$comment_id&action=approve&$del_nonce" )  . "\r\n";
		$notify_message = apply_filters('comment_notification_text', $notify_message, $comment_id);
		$subject = apply_filters('comment_notification_subject', $subject, $comment_id);
		$subject = "[Spam Notifier] " . $subject;
		$message_headers = "$from\n" . "Content-Type: text/plain; charset=\"" . get_bloginfo('charset') . "\"\n";
		$message_headers = apply_filters('comment_notification_headers', $message_headers, $comment_id);
	
		@wp_mail($author->user_email, wp_specialchars_decode( $subject ), $notify_message, $message_headers);
	}
}
add_action('comment_post', 'wp_notify_spam');

function sn_options_page() {
$opt_comments = get_option('sn_opt_comments');
$opt_trackbacks = get_option('sn_opt_trackbacks');
?>
<?php if ( !empty($_POST ) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Options saved.', "spam-notifier") ?></strong></p></div>
<?php endif; ?>
<div class="wrap">
<h2><?php _e('Spam Notifier Settings', 'spam-notifier'); ?></h2>

<div class="metabox-holder" id="poststuff">
<div class="meta-box-sortables">

<?php $lang = get_locale(); ?>
<?php if ($lang != "ru_RU") { ?>
<div class="postbox">

    <h3 class="hndle"><span><?php _e("Do you use it ?", "spam-notifier"); ?></span></h3>
    <div class="inside" style="display: block;">
	<?php $purl = plugins_url(); ?>
        <img src="<?php echo $purl. '/spam-notifier/img/icon_coffee.png'; ?>" title="<?php _e("buy me a coffee", "spam-notifier"); ?>" style=" margin: 5px; float:left;" />
		
        <p><?php _e("Hi! I'm <strong>Flector</strong>, developer of this plugin.", "spam-notifier"); ?></p>
        <p><?php _e("I've been spending many hours to develop this plugin.", "spam-notifier"); ?> <br />
		<?php _e("If you like and use this plugin, you can <strong>buy me a cup of coffee</strong>.", "spam-notifier"); ?></p>

        <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
            <input type="hidden" name="cmd" value="_s-xclick">
            <input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----">
            <input type="image" src="<?php echo $purl. '/spam-notifier/img/donate.gif'; ?>" border="0" name="submit" title="<?php _e("Donate with PayPal", "spam-notifier"); ?>">
        </form>
        <div style="clear:both;"></div>
    </div>
</div>
<?php } ?>

<form action="" method="post">


<div class="postbox">

    <h3 class="hndle"><span><?php _e("Options", "spam-notifier"); ?></span></h3>
    <div class="inside" style="display: block;">

        <table class="form-table">
            <tr>
                <th><?php _e("Comments", "spam-notifier") ?></th><td><input type="checkbox" name="opt_comments" value="1" <?php if ($opt_comments == '1') echo "checked='checked'"; ?> /> <?php _e("Send an email if a regular comment was marked as spam.", "spam-notifier"); ?></td>
            </tr>
			<tr>
                <th><?php _e("Pingbacks and Trackbacks", "spam-notifier") ?></th><td><input type="checkbox" name="opt_trackbacks" value="2" <?php if ($opt_trackbacks == '1') echo "checked='checked'"; ?> /> <?php _e("Send an email if a comment containing pingback or trackback was marked as spam.", "spam-notifier"); ?></td>
            </tr>	
            <tr>
                <th></th>
                <td>
                    <input type="submit" name="submit" class="button button-primary" value="<?php _e('Update options &raquo;', "spam-notifier"); ?>" />
                </td>
            </tr>
        </table>

    </div>
</div>


</form>
</div>
</div>
<?php 
}

function sn_menu() {
	add_options_page('Spam Notifier', 'Spam Notifier', 'manage_options', 'spam-notifier.php', 'sn_options_page');
}
add_action('admin_menu', 'sn_menu');

function sn_setup(){
    load_plugin_textdomain('spam-notifier', null, dirname( plugin_basename( __FILE__ ) ) . '/lang' );
}
add_action('init', 'sn_setup');

?>