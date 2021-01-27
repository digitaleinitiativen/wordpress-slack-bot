<?php
/**
*Plugin Name: Slackbot
*Description: Sends slack messages to the digitaleinitiativen slack channel
**/

// 1. Upload file to /wp-content/plugins/slackbot/slackbot.php
// 2. Insert PRODUCTION_CHANNEL_URL and TEST_CHANNEL_URL (get them from the Slack App Directory)
// 3. Activate plugin slackbot

add_action('transition_post_status', 'transitionPostFunction', 10, 3 );
function transitionPostFunction( $new_status, $old_status, $post ) {
    if ( $new_status == 'publish' && $old_status != 'publish' ) {
        sendSlack($post->ID);
    }
}

function sendSlack($post_id){	
	if(!(get_post_type($post_id) == 'post' || get_post_type($post_id) == 'page')) return;
	
	// send slack messages to the #bots-testfield channel instead of general
	$testChannel = false;
	
	$webhookUrl = !$testChannel ? 'PRODUCTION_CHANNEL_URL' : 'TEST_CHANNEL_URL';
		
	$content_post = get_post($post_id);
	$content = $content_post->post_content;
	$content = apply_filters('the_content', $content);
	$content = wp_filter_nohtml_kses( $content );
	$content = html_entity_decode($content);
	preg_replace( "/[\r\n]+/", "\n", $content );
		
	$excerpt = html_entity_decode(wp_filter_nohtml_kses(get_the_excerpt($post_id)));
	$excerpt = str_replace('Read more', '', $excerpt);

	$data = array();
	$attachments = array();
		
	$attachments['fallback'] = $excerpt;
	$attachments['color'] = '#FFDD00';
	$attachments['title'] = html_entity_decode(get_the_title($post_id));
	$attachments['title_link'] = get_permalink($post_id);
	$attachments['text'] = $excerpt;
	$attachments['footer'] = "digitaleinitiativen.at";
	$attachments['footer_icon'] = "https://digitaleinitiativen.at/wp-content/uploads/2019/11/cropped-di-logo-1-32x32.png";
	$attachments['ts'] = time();
		
	if (has_post_thumbnail($post_id)) {
		$attachments['thumb_url'] = get_the_post_thumbnail_url($post_id, 'thumbnail' ); // image_url // thumb_url
	}
	
	$data['attachments'] = array();
	$data['attachments'][0] = $attachments;

	$json_data = json_encode($data);
	$url = curl_init($webhookUrl);
	curl_setopt($url, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($url, CURLOPT_POSTFIELDS, $json_data);
	curl_setopt($url, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($url, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		'Content-Length: ' . strlen($json_data))
	);
	$result = curl_exec($url);
}

/*
add_action('publish_post', 'publishPostFunction',10,2); // publish_post
function publishPostFunction($post_id, $post){
    if ( !(defined( 'REST_REQUEST' ) && REST_REQUEST )) {
		// https://wordpress.stackexchange.com/a/120999
		sendSlack($post->ID);
	}
}
*/

?>
