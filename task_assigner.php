<?php 
/*
Plugin Name: Task Assigner
Description: A plugin to help you assign different task to different users
Author: Cybercraft Technologies
Text Domain: ta
Version: 1.0
*/
include 'ajax.php';
class task_assigner{
	private $in_time_submit;
	private $scheduled_time_post;
	private $user_credits;
	private $user_notification;
	function __construct(){
		add_action( 'admin_bar_menu', array($this,'modify_admin_bar') );
		$this->user_notification = get_option('user_notification');
		!is_array($this->user_notification)?($this->user_notification = array()):'';
		//echo '<pre>';print_r($this->user_notification);echo '</pre>';
		
		$this->in_time_submit = get_option('in_time_submit');
		!is_array($this->in_time_submit)?($this->in_time_submit = array()):'';
		//echo '<pre>';print_r($this->in_time_submit);echo '</pre>';
		
		$this->scheduled_time_post = get_option('scheduled_time_post');
		!is_array($this->scheduled_time_post)?($this->scheduled_time_post = array()):'';
		//echo '<pre>';print_r($this->scheduled_time_post);echo '</pre>';
		//echo time();
		$this->user_credits = get_option('ta_user_credits');
		!is_array($this->user_credits)?($this->user_credits = array()):'';
		//echo '<pre>';print_r($this->user_credits);echo '</pre>';
		
		if(!empty($this->scheduled_time_post)){
			foreach($this->scheduled_time_post as $task_id => $schedule_array){
				if($schedule_array['deadline'] < time()){
					foreach($schedule_array['user_id'] as $each_user_id){
						if( $this->in_time_submit[$each_user_id][$task_id] == '' /*!= 'true'*/ || !isset($this->in_time_submit[$each_user_id][$task_id]) ){
							$this->task_submission_time_fail($each_user_id,$task_id);
						}elseif( trim($this->in_time_submit[$each_user_id][$task_id]) == 'done' ){
							//make if true and success
							$this->task_submission_time_success($each_user_id,$task_id);
						
						}/*else{
							$this->task_submission_time_success($each_user_id,$task_id);
						}*/
					}
	
				}
			}
		}
		add_action('init',array($this,'create_task_post_type'));
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_submitted_task_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
		add_action( 'save_post', array( $this, 'save_submitted_task' ) );
		add_action('admin_menu',array($this,'create_ta_page'));
		add_action( 'admin_notices', array($this,'show_notification') );
		add_action( 'admin_enqueue_scripts', array($this,'load_custom_wp_admin_style') );
	}
	function create_task_post_type(){
		if(current_user_can('manage_options')){
			$labels = array(
					'name' => _x('Task', 'post type general name'),
					'singular_name' => _x('Task', 'post type singular name'),
					'menu_name' => _x( 'Task', 'admin menu'),
					'name_admin_bar' => _x( 'Task', 'add new on admin bar'),
					'add_new' => _x('Add New Task', 'Task'),
					'add_new_item' => __('Add New Task'),
					'edit_item' => __('Edit Task'),
					'new_item' => __('New Task'),
					'view_item' => __('View Task'),
					'all_items' => __( 'All Task' ),
					'search_items' => __('Search Task'),
					'not_found' =>  __('Nothing found'),
					'not_found_in_trash' => __('Nothing found in Trash'),
					'parent_item_colon' => '',
					
				);
				$args = array(
					'labels' => $labels,
					'public' => true,
					'publicly_queryable' => true,
					'show_ui' => true,
					'show_in_menu' => true,
					'query_var' => true,
					'rewrite' => array('slug' => 'task'),
					'capability_type' => 'post',
					'has_archive' => false,
					'hierarchical' => false,
					'menu_position' => 3,
					'supports' => array(
						'title',
						'editor',
						'featured-image'
						),
			
				);
				register_post_type( 'task' , $args );
		}
		else{
			$labels = array(
				'name' => _x('Submitted Task', 'post type general name'),
				'singular_name' => _x('Submitted Task', 'post type singular name'),
				'menu_name' => _x( 'Submitted Task', 'admin menu'),
				'name_admin_bar' => _x( 'Submitted Task', 'add new on admin bar'),
				'add_new' => _x('Submit a task', 'Submitted Task'),
				'add_new_item' => __('Add New Submitted Task'),
				'edit_item' => __('Edit Submitted Task'),
				'new_item' => __('New Submitted Task'),
				'view_item' => __('View Submitted Task'),
				'all_items' => __( 'All Submitted Task' ),
				'search_items' => __('Search Submitted Task'),
				'not_found' =>  __('Nothing found'),
				'not_found_in_trash' => __('Nothing found in Trash'),
				'parent_item_colon' => '',
				
			);
			$args = array(
				'labels' => $labels,
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => true,
				'query_var' => true,
				'rewrite' => array('slug' => 'submitted_task'),
				'capability_type' => 'post',
				'has_archive' => false,
				'hierarchical' => false,
				'menu_position' => 3,
				'supports' => array(
					'title',
					'editor',
					'featured-image'
					),
		
			);
			register_post_type( 'submitted_task' , $args );
		}
	}
	function add_meta_box($post_type){
            if ( $post_type == 'task') {
				add_meta_box(
					'task_meta_box'
					,__( 'Assign to user', 'ta' )
					,array( $this, 'render_meta_box_content' )
					,$post_type
					,'advanced'
					,'high'
				);
			}
	}
	function render_meta_box_content($post){
		$user_ids = get_post_meta( $post->ID, '_assigned_users', true );
		!is_array($user_ids)?($user_ids = array()):'';
		$users = get_users();
		?>
        <ul>
        	<?php 
			foreach($users as $user){
				?>
                <li>
                	<label>
                    	<input type="checkbox" name="assigned_user[]" value="<?php echo $user->ID;?>" <?php echo in_array($user->ID,$user_ids)?'checked':''; ?> />
                        <input type="hidden" name="user_data[<?php echo $user->ID;?>][nicename]" value="<?php echo $user->user_nicename;?>" />
                        <input type="hidden" name="user_data[<?php echo $user->ID;?>][email]" value="<?php echo $user->user_email;?>" />
                         <?php echo $user->user_nicename.'/'.$user->user_email;?></label></li>
                <?php
			}
			?>
        </ul>
		<h3>Credit</h3>
		<input type="text" name="task_credit" value="<?php echo get_post_meta($post->ID,'task_credit',true); ?>" />
		<h3>Time to complete</h3>
        <?php $task_time = get_post_meta($post->ID,'task_time',true);
		?>
        <select name="task_time[y]">
			<?php 
			for($y = 0;$y<=10;$y++){
				?>
				<option value="<?php echo $y?>" <?php echo ( isset($task_time['y']) && $task_time['y'] == $y)?'selected':''; ?> ><?php echo $y; ?></option>
				<?php
			}
			?>
		</select> Years
		<select name="task_time[m]">
			<?php 
			for($m = 0;$m<=11;$m++){
				?>
				<option value="<?php echo $m?>" <?php echo (isset($task_time['m']) && $task_time['m'] == $m)?'selected':''; ?> ><?php echo $m; ?></option>
				<?php
			}
			?>
		</select> Months
		<select name="task_time[d]">
			<?php 
			for($d = 0;$d<=29;$d++){
				?>
				<option value="<?php echo $d; ?>" <?php echo (isset($task_time['d']) && $task_time['d'] == $d)?'selected':''; ?> ><?php echo $d; ?></option>
				<?php
			}
			?>
		</select> Day(s)
		<select name="task_time[h]">
			<?php 
			for($h = 0;$h<=23;$h++){
				?>
				<option value="<?php echo $h; ?>" <?php echo  (isset($task_time['h']) && $task_time['h'] == $h)?'selected':''; ?> ><?php echo $h; ?></option>
				<?php
			}
			?>
		</select> Hour(s)
		<select name="task_time[min]">
			<?php 
			for($min = 0;$min<=59;$min++){
				?>
				<option value="<?php echo $min;?>" <?php echo (isset($task_time['min']) && $task_time['min'] == $min)?'selected':''; ?> ><?php echo $min; ?></option>
				<?php
			}
			?>
		</select> Minute(s)
        <?php
	}
	
	function save($post_id){
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		/*if ( ! isset( $_POST['myplugin_inner_custom_box_nonce'] ) )
			return $post_id;*/

		//$nonce = $_POST['myplugin_inner_custom_box_nonce'];

		// Verify that the nonce is valid.
		/*if ( ! wp_verify_nonce( $nonce, 'myplugin_inner_custom_box' ) )
			return $post_id;*/

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}
		if(get_post_type() == 'task'){

			// Sanitize the user input.
			$assigned_user_data =  $_POST['assigned_user'];
			// Update the meta field.
			$task_time = $_POST['task_time'];
			update_post_meta( $post_id, '_assigned_users', $assigned_user_data );
			update_post_meta( $post_id, 'task_credit', sanitize_text_field($_POST['task_credit']));
			update_post_meta( $post_id, 'task_time', $_POST['task_time']);
        	$scheduled_time_post = $this->scheduled_time_post;
			is_array($scheduled_time_post)?'':($scheduled_time_post = array());
			if(!empty($assigned_user_data)){
				$user_data_temp = $_POST['user_data'];
				$user_data = array();
				foreach($assigned_user_data as $each_assigned_id){
					$user_data[$each_assigned_id] = $user_data_temp[$each_assigned_id];
				}
				
				$scheduled_time_post[$post_id] = array(
					'permalink' => get_permalink($post_id),
					'title' => get_the_title($post_id),
					'deadline' => strtotime(' + '.$task_time['d'].' days +'.$task_time['m'].' months +'.$task_time['y'].' years +'.$task_time['h'].' hour +'.$task_time['min'].' minute'),
					'user_id' => $assigned_user_data,
					'credit' => sanitize_text_field($_POST['task_credit']),
					'user_data' => $user_data
				);
				if(!isset($scheduled_time_post[$post_id]['mail']) || $scheduled_time_post[$post_id]['mail'] == 0){
					$scheduled_time_post[$post_id]['mail'] = 1;
				}
				foreach($scheduled_time_post[$post_id]['user_data'] as $user_id=> $u_staffs){
					$headers[] = 'From: Admin <'.get_option('admin_email').'>';
						wp_mail($u_staffs['email'],'A task has been assigned to you','The following task has been assigned to you<br><a href="'.$scheduled_time_post[$post_id]['permalink'].'">'.$scheduled_time_post[$post_id]['title'].'</a>',$headers);
						$noti_string = 'The following task has been assigned to you<br><a class="modal_show" data-post_id="'.$post_id.'">'.$scheduled_time_post[$post_id]['title'].'</a>';// href="'.$scheduled_time_post[$post_id]['permalink'].'"
						$this->set_new_task_notification($user_id,$noti_string);
				}
				//assign the user ids of the ssigned post to the in_time_post array as user_id[post_id] = '' 
				$all_u_id = array_keys($user_data_temp);
				foreach($all_u_id as $each_id){
					if(in_array($each_id,$scheduled_time_post[$post_id]['user_id'])){
						$this->in_time_submit[$each_id][$post_id] = '';
					}else{
						unset($this->in_time_submit[$each_id][$post_id]);
					}
				}
				//echo 'in time submit<pre>';print_r($this->in_time_submit);echo '</pre>';
//				exit;
				update_option('scheduled_time_post',$scheduled_time_post);
				update_option('in_time_submit',$this->in_time_submit);
				
			}


		}

	}
	
	function add_submitted_task_meta_box($post_type){
		if ( $post_type == 'submitted_task') {
				add_meta_box(
					'submitted_task_meta_box'
					,__( 'Submitted Task', 'ta' )
					,array( $this, 'render_submitted_task_meta_box_content' )
					,$post_type
					,'advanced'
					,'high'
				);
			}
	}
	function render_submitted_task_meta_box_content($post){
		$current_user = wp_get_current_user();
		$scheduled_time_post = $this->scheduled_time_post;
		!is_array($scheduled_time_post)?($scheduled_time_post = array()):'';
		$post_ids_to_grab = array();
		if(isset( $this->in_time_submit[get_current_user_id()]) && is_array($this->in_time_submit[get_current_user_id()]) ){
			foreach($this->in_time_submit[get_current_user_id()] as $post_id => $status){
				if( $status != 'false' ){
					$post_ids_to_grab[] = $post_id;
				}
			}
		}
		if(!empty($post_ids_to_grab)){
			$args = array(
				'post_type' => 'task',
				'post__in' => $post_ids_to_grab
			);
			$tasks_posts = get_posts($args);
		}else{
			$tasks_posts = array();
		}
		
		
		if(!empty($tasks_posts)){
			?>
			<h3>Submit the writing for which task?</h3>
			<ul>
			<?php
			$linked_task = get_post_meta($post->ID,'linked_task',true);
			!is_array($linked_task)?($linked_task = array()):'';
			foreach($tasks_posts as $each_post){
				?>
				<li><label><input type="checkbox" name="task_post[]" class="task_post" value="<?php echo $each_post->ID; ?>" <?php echo in_array($each_post->ID,$linked_task)?'checked':'';?>/> <?php echo $each_post->post_title;?></label></li>
				<?php
			}
			?>
			</ul>
			<?php
		}
		
	}
	//when a task is submitted
	function save_submitted_task($post_id){
		
		if(get_post_type() == 'submitted_task'){
			$current_user = wp_get_current_user();
			
			$linked_tasks = $_POST['task_post'];
			
			update_post_meta($post_id,'linked_task',$linked_tasks);
			
			if(!empty($linked_tasks) && is_array($linked_tasks) ){

				$scheduled_time_post = $this->scheduled_time_post;
				!is_array($scheduled_time_post)?($scheduled_time_post = array()):'';

				$in_time_submit = $this->in_time_submit;
				!is_array($in_time_submit)?($in_time_submit = array()):'';

				foreach($linked_tasks as $each_linked_task){
					if(time() > $scheduled_time_post[$each_linked_task]['deadline']){
						//the value will be empty, so when reloding the page, system will check if it is empty and the time is greater. if it is, then time_fail.
						$in_time_submit[$current_user->ID][$each_linked_task] = ''; 
						
					}elseif(time() <= $scheduled_time_post[$each_linked_task]['deadline']){
						//pending for admin approval
						$in_time_submit[$current_user->ID][$each_linked_task] = 'pending';
						$headers[] = $scheduled_time_post[$each_linked_task]['user_data'][$current_user->ID]['email']; 
						wp_mail(get_option('admin_email'),'A task has been submitted to you','The following task has been submitted to you<br><a href="'.$scheduled_time_post[$post_id]['permalink'].'">'.$scheduled_time_post[$post_id]['title'].'</a>',$headers);
						$noti_string = 'A submitted task by '.$scheduled_time_post[$each_linked_task]['user_data'][get_current_user_id()]['nicename'].' is awaiting for review';
						$this->set_new_task_notification('admin',$noti_string);
					};
				
				}
				update_option('in_time_submit',$in_time_submit);
				
			}

			
		}
		
		
	}
	
	function create_ta_page(){
		add_submenu_page( 'edit.php?post_type=task','User Credits', 'User Credits', 'manage_options',__FILE__, array($this,'render_ta_page_content'));
		add_submenu_page( 'edit.php?post_type=task','Task Submitted', 'Task Submitted', 'manage_options','task_submitted', array($this,'render_submitted_post_content'));
		add_submenu_page( 'edit.php?post_type=submitted_task','Assigned Task/Credits/History', 'Assigned Task/Credits/History', 'edit_post', 'ta_assigned_task', array($this,'create_task_list_page')); 
		//add_submenu_page( 'edit.php?post_type=submitted_task','Credits And History', 'Credits And History', 'edit_post', 'credits_and_history', array($this,'create_credits_and_history')); 
	}
	
function render_submitted_post_content(){
?>
<div class="panel-group task_submitted_container" id="accordion" role="tablist" aria-multiselectable="true">
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingOne">
      <h4 class="panel-title">
        <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
          Task Submitted
        </a>
      </h4>
    </div>
    <div id="collapseOne" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="headingOne">
      <div class="panel-body">
        <div class="review_list">
			<ul>
				<?php
				$completed_list = '';
				$pending_list = '';
				$failed_list = '';
				foreach($this->in_time_submit as $submitter_id => $submitted_task_array){
					$submitter_data = get_userdata($submitter_id);
					foreach($submitted_task_array as $task_id => $task_status){
						if($task_status == 'pending'){
							$completed_list .= '<li>Submite by '.$submitter_data->user_nicename.'<input type="hidden" class="submitter_id" value="'.$submitter_id.'" /> | <a class="task_link" data-id="'.$task_id.'" href="'.get_permalink($task_id).'" target="new">'.get_the_title($task_id).'</a><input type="radio" name="task_done_['.$task_id.']" class="is_task_done" value="done"> Task Done<input type="radio" name="task_done_['.$task_id.']"  class="is_task_done"  value=""> Task Not Done</li>';
						}elseif(empty($task_status)){
							$pending_list .= '<li>Name: '.$this->scheduled_time_post[$task_id]['user_data'][$submitter_id]['nicename'].', 
								email: '.$this->scheduled_time_post[$task_id]['user_data'][$submitter_id]['nicename'].',
								Task: <a href="'.$this->scheduled_time_post[$task_id]['permalink'].'">'.$this->scheduled_time_post[$task_id]['title'].'</a>
							</li>';
						}elseif($task_status == 'false'){
							$failed_list .= '<li>Name: '.$this->scheduled_time_post[$task_id]['user_data'][$submitter_id]['nicename'].', 
								email: '.$this->scheduled_time_post[$task_id]['user_data'][$submitter_id]['nicename'].',
								Task: <a href="'.$this->scheduled_time_post[$task_id]['permalink'].'">'.$this->scheduled_time_post[$task_id]['title'].'</a>
							</li>';
						}
					}
				}
				if(!empty($completed_list)){
					echo $completed_list;
				}
				?>
				
			</ul>
            <?php if(!empty($completed_list)):?>
	            <input type="submit" name="Save" class="save_task_review" value="Done !" />
                <?php else:?>
                No New Task Yet !
            <?php endif;?>
		</div>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingTwo">
      <h4 class="panel-title">
        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
          Who did not submit task yet
        </a>
      </h4>
    </div>
    <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
      <div class="panel-body">
       <div class="task_pending">
	        <h3>Who did not submit task yet</h3>
            <ul>
			<?php 
			if($pending_list)echo $pending_list;
			else echo 'No one in this list';
			
			?>
            </ul>
        </div>
      </div>
    </div>
  </div>
  <div class="panel panel-default">
    <div class="panel-heading" role="tab" id="headingThree">
      <h4 class="panel-title">
        <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
          Who Failed to submit the task
        </a>
      </h4>
    </div>
    <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
      <div class="panel-body">
        <div class="task_failed">
            <ul>
			<?php 
			if($failed_list)echo $failed_list;
			else echo 'No one in this list';
			?>
            </ul>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(function($){
	$('.save_task_review').click(function(){
		var is_done = '';
		review_result = {}
		$('.review_list li').each(function(index, element) {
			$('.is_task_done',this).each(function(index, element) {
				if($(this).is(':checked')){
					is_done = $(this).val();
				}
			});
			if(review_result[ $('.submitter_id',this).val() ] == null){
				review_result[ $('.submitter_id',this).val() ] = {}
			}
			review_result[ $('.submitter_id',this).val() ][$('.task_link',this).attr('data-id')] = is_done ;
			is_done = ''
		});
		console.log(review_result);
		$.post(
			ajaxurl,
			{
				'action':'task_review_action',
				'review_result' : JSON.stringify(review_result)
				
			},
			function(data){
				alert('Done !');
			}
		);
	});
})(jQuery)
</script>
		<?php
	}
	function render_ta_page_content(){
		
		$users = get_users();
		$credits = $this->user_credits;
		is_array($credits)?'':($credits = array());
		?>
		<div class="user_credit user_credit_holder">
			<ul>
				<?php 
				foreach($users as $user){
					?>
					<li><label><?php echo $user->user_nicename.','.$user->user_email; ?><input type="hidden" class="user_id" name="user_id" value="<?php echo $user->ID; ?>" /> <input type="text" name="user_credit" class="user_credit" value="<?php echo ( isset($credits[$user->ID])?$credits[$user->ID]:'');?>"/></label></li>
					<?php
				}
				?>
			</ul>
		</div>
		<input type="submit" name="save_credit" class="save_credit" value="Save Credit(s)" />
		<script>
		(function($){
			$('.save_credit').click(function(){
				var user_credit_array = {};
				$('.user_credit li').each(function(index, element) {
					user_credit_array[$('.user_id',this).val()] = parseInt($('.user_credit',this).val());
				});
				$.post(
					ajaxurl,
					{
						'action':'save_user_credit_array',
						'user_credit' : JSON.stringify(user_credit_array)
					},
					function(data){
						alert('Data is saved');
					}
				)
			});
		})(jQuery)
		</script>
		<?php
	}
	function create_task_list_page(){
		
		$tasks = $this->scheduled_time_post;
		$current_id = get_current_user_id();
		?>
<div role="tabpanel">

  <!-- Nav tabs -->
  <ul class="nav nav-tabs" role="tablist">
    <li role="presentation" class="active"><a href="#assigned_task" aria-controls="home" role="tab" data-toggle="tab">Assigned Task</a></li>
    <li role="presentation"><a href="#credits" aria-controls="profile" role="tab" data-toggle="tab">Credits</a></li>
    <li role="presentation"><a href="#history" aria-controls="messages" role="tab" data-toggle="tab">History</a></li>
  </ul>

  <!-- Tab panes -->
  <div class="tab-content">
    <div role="tabpanel" class="tab-pane active" id="assigned_task">
        <ul>
            <?php
            if($this->in_time_submit[$current_id]){
                foreach($this->in_time_submit[$current_id] as $post_id => $state){
                    if(empty($state)){
                        ?>
                        <li class="each_task">
                            <h4><a class="modal_show" data-post_id="<?php echo $post_id;?>"><?php echo $this->scheduled_time_post[$post_id]['title'];?></a></h4>
                            <p>Deadline : <?php echo date('d-m-y H:i',$this->scheduled_time_post[$post_id]['deadline']);?></p>
                            <p>Current Time : <?php echo date('d-m-y H:i',time());?></p>
                            <?php if(time() > $this->scheduled_time_post[$post_id]['deadline']):?><p>Time Over</p><?php endif;?>
                        </li>
                        <?php	
                    }
                }
            }
            
            ?>
          
        </ul>
          <script>
          $('.modal_show').on('click',function(){
						post_id = $(this).attr('data-post_id');
						$.post(
							ajaxurl,
							{
								'action':'get_post_ajx',
								'post_id' : post_id,
							},
							function(data){
								data = JSON.parse(data);
								$('#post_showing_Modal .modal-title').html(data['title']);
								$('#post_showing_Modal .modal-body').html(data['content']);
								$('#post_showing_Modal').modal('show');
							}
						)
						return false;
					});
          </script>
    </div>
  
    <div role="tabpanel" class="tab-pane fade" id="credits">
    <?php if(isset($this->user_credits[get_current_user_id()])):?>
            <h3>Credits</h3>
            <p>Your total credits : <?php echo $this->user_credits[get_current_user_id()];?></p>
		<?php endif?>
       
    </div>
    <div role="tabpanel" class="tab-pane fade" id="history">
     <h3>History</h3>
        <ul>
        <?php
		if( is_array($this->in_time_submit[get_current_user_id()])){
			foreach($this->in_time_submit[get_current_user_id()] as $post_id => $state){
				?>
                <li>
                <?php
				if($state == 'pending'){
					?>
					Your task "<?php echo get_the_title($post_id);?>" is under review
					<?php
				}elseif($state == 'true'){
					?>
					Your completed the task : <?php echo get_the_title($post_id)?>
					<?php
				}elseif($state == 'false'){
					?>
					You failed to do the  task : <?php echo get_the_title($post_id)?>
					<?php
				}elseif($state == ''){
					?>
					The  task : <a class="modal_show" data-post_id="<?php echo $post_id; ?>"><?php echo get_the_title($post_id)?></a> has been assigned to you
					<?php
				}
				?>
                </li>
                <?php
			}
		}
		
		?>
        </ul>
    </div>
  </div>

</div>
        
    <?php
	}
	
	function task_submission_time_fail($user_id,$task_id){
		$this->in_time_submit[$user_id][$task_id] = 'false';
		$this->user_credits[$user_id] = $this->user_credits[$user_id] - $this->scheduled_time_post[$task_id]['credit'];
		update_option('ta_user_credits',$this->user_credits);
		update_option('in_time_submit',$this->in_time_submit);
		
	}
	function task_submission_time_success($user_id,$task_id){
		$this->in_time_submit[$user_id][$task_id] = 'true';
		$this->user_credits[$user_id] = $this->user_credits[$user_id] + $this->scheduled_time_post[$task_id]['credit'];
		update_option('ta_user_credits',$this->user_credits);
		update_option('in_time_submit',$this->in_time_submit);
	}
	//send mail
	function send_mail_to_user_for_new_task(){
	}
	
	function set_new_task_notification($user_id,$noti_string){
		$this->user_notification[$user_id][] = $noti_string;
		update_option('user_notification',$this->user_notification);
		//echo '<pre>';print_r($this->user_notification);echo '</pre>';
	}
	function show_notification(){
		$this->get_modal();
		if(!current_user_can('manage_options')){
			$user_index = get_current_user_id();
		}else{
			$user_index = 'admin';
		}
		if(is_array($this->user_notification) and isset($this->user_notification[get_current_user_id()]) ){
				foreach($this->user_notification[$user_index] as $index => $each_notification){
					?>
					<div class="updated">
						<p><?php _e( $each_notification, 'ta' ); ?></p>
                        <p class="dissmiss_noti">Dismiss</p>
                        <input type="hidden" class="noti_index" value="<?php echo $index?>" />
					</div>
                    <?php
				}
				$js_user_id = !current_user_can('manage_options')?get_current_user_id():'admin';
				?>
                <script>
				(function($){
					var user_id = '<?php echo $js_user_id;?>';
					$('.dissmiss_noti').click(function(){
						$(this).parent().remove();
						var noti_index = $(this).siblings(':hidden.noti_index').val();
						$.post(
							ajaxurl,
							{
								'action':'remove_noti',
								'user_id' : user_id,
								'noti_index': noti_index
							},
							function(data){
								//alert(data);
							}
						)		
					
					});
					$(document).on('click','.modal_show',function(){
						post_id = $(this).attr('data-post_id');
						$.post(
							ajaxurl,
							{
								'action':'get_post_ajx',
								'post_id' : post_id,
							},
							function(data){
								data = JSON.parse(data);
								$('#post_showing_Modal .modal-title').html(data['title']);
								$('#post_showing_Modal .modal-body').html(data['content']);
								$('#post_showing_Modal').modal('show');
							}
						)
						return false;
					});
				})(jQuery)
				</script>
				<?php
		}
	}
	function load_custom_wp_admin_style(){
		wp_enqueue_style('ta-bs-style',plugins_url( 'css/bootstrap.min.css', __FILE__ ));
		wp_enqueue_style('ta-cutom-style',plugins_url( 'css/style.css', __FILE__ ));
		wp_enqueue_script('ta-bs-js',plugins_url( 'js/bootstrap.min.js', __FILE__ ),array('jquery'));
	}
	function get_modal(){
		?>
        <div class="modal fade" id="post_showing_Modal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">Modal title</h4>
              </div>
              <div class="modal-body">
                ...
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
              </div>
            </div>
          </div>
        </div>
        <?php
	}
	function modify_admin_bar( $wp_admin_bar ){
		if(current_user_can('manage_options')){
			 // do something with $wp_admin_bar;
			 // add a parent item
			$args = array(
				'id'    => 'task_assigner_all_task',
				'title' => 'Tasks',
				'href' => get_admin_url('','edit.php?post_type=task')
			);
			$wp_admin_bar->add_node( $args );
		
			// add a child item to our parent item
			$args = array(
				'id'     => 'all_tasks',
				'title'  => 'All Tasks',
				'parent' => 'task_assigner_all_task',
				'href' => get_admin_url('','edit.php?post_type=task')
			);
			$wp_admin_bar->add_node( $args );
			$args = array(
				'id'     => 'add_new_tasks',
				'title'  => 'Add New Task',
				'parent' => 'task_assigner_all_task',
				'href' => get_admin_url('','post-new.php?post_type=task')
			);
			$wp_admin_bar->add_node( $args );
			$args = array(
				'id'     => 'task_user_credit',
				'title'  => 'User Credits',
				'parent' => 'task_assigner_all_task',
				'href' => get_admin_url('','edit.php?post_type=task&page=task_assigner/task_assigner.php')
			);
			$wp_admin_bar->add_node( $args );
			$args = array(
				'id'     => 'task_submitted',
				'title'  => 'Task Submitted',
				'parent' => 'task_assigner_all_task',
				'href' => get_admin_url('','edit.php?post_type=task&page=task_submitted')
			);
			$wp_admin_bar->add_node( $args );
		
		
		}else{
			 // do something with $wp_admin_bar;
			 // add a parent item
			$args = array(
				'id'    => 'task_assigner_submitted_task',
				'title' => 'Submitted Tasks',
				'href' => get_admin_url('','edit.php?post_type=submitted_task')
			);
			$wp_admin_bar->add_node( $args );
		
			// add a child item to our parent item
			$args = array(
				'id'     => 'all_sumitted_tasks',
				'title'  => 'All Submitted Tasks',
				'parent' => 'task_assigner_submitted_task',
				'href' => get_admin_url('','edit.php?post_type=submitted_task')
			);
			$wp_admin_bar->add_node( $args );
			$args = array(
				'id'     => 'submit_new_task',
				'title'  => 'Submit a Task',
				'parent' => 'task_assigner_submitted_task',
				'href' => get_admin_url('','post-new.php?post_type=submitted_task')
			);
			$wp_admin_bar->add_node( $args );
			$args = array(
				'id'     => 'assigned_task_credit_history',
				'title'  => 'Assigned Tasks/Credits/History',
				'parent' => 'task_assigner_submitted_task',
				'href' => get_admin_url('','edit.php?post_type=submitted_task&page=ta_assigned_task')
			);
			$wp_admin_bar->add_node( $args );
		}
	 
	}
	
}
new task_assigner;