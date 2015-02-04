<?php 
add_action('wp_ajax_save_user_credit_array','save_user_credit_array');
function save_user_credit_array(){
	update_option('ta_user_credits',(array)json_decode(stripslashes($_POST['user_credit'])));
	exit;
}
add_action('wp_ajax_task_review_action','task_review_action');
function task_review_action(){
	$in_time_submit = get_option('in_time_submit');
	$review_result = (array)json_decode(stripslashes($_POST['review_result']));
		
	foreach($review_result as $each_review_id => $reviewed_task_array){
			
		if(isset($in_time_submit[$each_review_id]) and is_array($in_time_submit[$each_review_id])){
			//$in_time_submit[$each_review_id] = (array)$reviewed_task_array;
			foreach($reviewed_task_array as $task_id => $review_state){
				if(isset($in_time_submit[$each_review_id][$task_id])){
					$in_time_submit[$each_review_id][$task_id] = $review_state;
				}
			}
		}
	}
	update_option('in_time_submit',$in_time_submit);
	exit;
}
add_action('wp_ajax_remove_noti','remove_noti');
function remove_noti(){
	$user_notification = get_option('user_notification');
	unset($user_notification[$_POST['user_id']][$_POST['noti_index']]);
	update_option('user_notification',$user_notification);
	exit;
}
add_action('wp_ajax_get_post_ajx','get_post_ajx');
function get_post_ajx(){
	$post = get_post($_POST['post_id']);
	$post_staff = array(
		'title' => $post->post_title,
		'content' => nl2br($post->post_content)
	);
	print_r(json_encode($post_staff));
	exit;
}