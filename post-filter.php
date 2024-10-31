<?php
/*
Plugin Name: Post Filter
Plugin Script: post-filter.php
Plugin URI: http://marto.lazarov.org/plugins/post-filter
Description: Delete unwanted posts by keyword immediately after published
Version: 1.1.0
Author: mlazarov
Author URI: http://marto.lazarov.org
*/

if (!class_exists('post_filter')) {
	class post_filter {
		var $options = array();
		function post_filter() {
			$this->__construct();
		}

		function __construct() {
			$stored_options = get_option('post_filter_options');
			$this->options = (array)(is_serialized($stored_options)) ? unserialize($stored_options) : $stored_options;
			if(!isset($this->options['keywords']) || !is_array($this->options['keywords'])) $this->options['keywords'] = array('viagra','penis');
			if(!isset($this->options['count_deleted'])) $this->options['count_deleted'] = 0;
			if(!isset($this->options['count_filtered'])) $this->options['count_filtered'] = 0;

			add_action("admin_menu", array (&$this,"admin_menu_link"));
			add_action("publish_post",array(&$this,"checkPost"));
		}

		function admin_menu_link() {
			add_management_page('Post filter', 'Post filter', 8, basename(__FILE__), array (&$this,'admin_options_page'));
			add_filter('plugin_action_links_' . plugin_basename(__FILE__), array (&$this,'filter_plugin_actions'), 10, 2);
		}

		function filter_plugin_actions($links, $file) {
			$settings_link = '<a href="tools.php?page=' . basename(__FILE__) . '">' . __('Settings') . '</a>';
			array_unshift($links, $settings_link); // before other links

			return $links;
		}

		function saveSettings(){
			update_option('post_filter_options', serialize($this->options));
		}

		function admin_options_page() {
			if($_POST){
				$words = explode(',',mb_strtolower($_POST['keywords']));
				$keywords = array();
				foreach($words as $word){
					$keywords[] = trim($word);
				}
				$this->options['keywords'] = $keywords;
				$this->options['force'] = $_POST['force']?1:0;
				$this->saveSettings();
			}
			?>
			<div class="wrap">
				<div id="dashboard" style="width:450px;padding:10px;">
					<h3>Posts filter by Keywords</h3>
					<form method="post">
						<div  style="">
							<p><strong style="color:red;">Warning</strong>:
							This will delete all posts matching keywords added bellow!<br/>
							Remember to make regular backups of your database in case something goes wrong!</p>
							Enter keywords separeted by comma:<br/>
							<textarea name="keywords" cols="56" rows="5" style="width:430px;height:60px;"><?=htmlspecialchars(implode(', ',$this->options['keywords']));?></textarea><br/>

							<input type="checkbox" name="force" value="1" <?=($this->options['force']?'checked="checked"':'');?>/> Don't move deleted posts to trash (purge posts - there is no recovery option)<br/><br/>

							<div style="text-align:right;"><input type="submit" name="wp_delete_posts" class="button-primary" value="Save" /></div>
						</div>
					</form>
					Filtered posts: <?php echo $this->options['count_filtered']; ?><br/>
					Deleted posts: <?php echo $this->options['count_deleted']; ?><br/.
				</div>
			</div>
			<?php

		}
		function checkPost($post_id){
			global $wpdb;
			$bad_words = $this->options['keywords'];
			$check_in = array('post_content','post_title','post_excerpt','post_name');

			$post = get_post($post_id);

			$this->options['count_filtered']++;

			foreach($check_in as $field_name){
				$content = mb_strtolower($post->{$field_name});
				foreach($bad_words as $word){
					if(stripos($content,$word)){
						$result = wp_delete_post($post_id,$this->options['force']);
						$this->options['count_deleted']++;
						break 2;
					}
				}
			}

			$this->saveSettings();

		}

	}
}

if (class_exists('post_filter')) {
	$post_filter_var = new post_filter();
}
?>
