<?php
/*
Plugin Name: Reconews
Plugin URI:  https://www.cartmaker.net
Description: 投稿されたコンテンツ(記事)の内容を解析して、関連する外部のニュースやコンテンツを取得して投稿ページに表示することで内容の充実を図ることができます。
Version:     0.3
Author:      CARTMAKER
Author URI:  https://www.cartmaker.net/soft/reconews/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: reconews-wp
Domain Path: /languages
*/
	
	if(!defined('ABSPATH')){
    	exit;
	}
	
	class RecommendNews {
		
		public $db_version = "0.1";
		
		public $table_name = null;
		
		public $action = "reconews_action";
		
		public $igo = null;
		
		public function __construct() {
			
			global $wpdb;
			$this->table_name = $wpdb->prefix.'reconews';
			$this->action = "reconews_action";

			if(function_exists('register_activation_hook')){
				
				register_activation_hook(__FILE__, array($this,'update_data_base'));
				
			}
			
			require (plugin_dir_path( __FILE__ ).'lib/igo-php/lib/Igo.php');
			$this->igo = new Igo(plugin_dir_path( __FILE__ )."lib/ipadic", "UTF-8");

		
			add_action('admin_menu', array($this, 'add_pages'));
			add_action('wp_insert_post', array($this, 'wp_insert_post'), 10, 3);
			add_filter('the_content',array($this, 'add_news'));
			
			#add_action('wp_ajax_reconews_action', array($this, 'wp_ajax_free_reconews'));
			#add_action('wp_ajax_nopriv_reconews_action', array($this, 'wp_ajax_free_reconews'));

		
		}

		public function update_data_base(){
			
			global $wpdb;
			global $jal_db_version;
			
			$charset_collate = $wpdb->get_charset_collate();
			$sql = "CREATE TABLE ".$this->table_name." (
					`id`			mediumint(9) NOT NULL AUTO_INCREMENT, 
					`post_id`		mediumint(9) NOT NULL, 
					`keyword`		text DEFAULT NULL, 
					`news_data`		longtext DEFAULT NULL,
					`poweredBy`		mediumint(2) DEFAULT 0, 
					`created_date`	int(11) DEFAULT NULL, 
					`update_date`	int(11) DEFAULT NULL, 
					UNIQUE KEY id (`id`)
					) $charset_collate;";
			
			require_once(get_home_path() . 'wp-admin/includes/upgrade.php');
			dbDelta($sql);
			
			add_option("reconews_db_version", $this->db_version);
			
		}
		
		public function add_pages() {
			add_menu_page('reconews_admin','Reconews', 'level_8', __FILE__, array($this,'adomin'), '', 26);
		}
		
		public function adomin() {
			
			if(!empty($_POST) && current_user_can("level_8") === true){
				
				if(check_admin_referer('reconews_action', 'reconews_nonce_field')){
					
					$reconews_title = wp_strip_all_tags(sanitize_text_field($_POST['reconews_title']));
					$reconews_update_frequency = wp_strip_all_tags(sanitize_text_field($_POST['reconews_update_frequency']));
					$reconews_view_count = wp_strip_all_tags(sanitize_text_field($_POST['reconews_view_count']));
					$reconews_engine = wp_strip_all_tags(sanitize_text_field($_POST['reconews_engine']));
					
					if(!empty($reconews_title) && !empty($reconews_update_frequency) && !empty($reconews_view_count) && !empty($reconews_engine)){
						
						if(get_option("reconews_title") === false){
							
							add_option("reconews_title", $reconews_title);
							
						}else{
							
							update_option("reconews_title", $reconews_title);
							
						}
						
						if(get_option("reconews_update_frequency") === false){
							
							add_option("reconews_update_frequency", $reconews_update_frequency);
							
						}else{
							
							update_option("reconews_update_frequency", $reconews_update_frequency);
							
						}
						
						if(get_option("reconews_view_count") === false){
							
							add_option("reconews_view_count", $reconews_view_count);
							
						}else{
							
							update_option("reconews_view_count", $reconews_view_count);
							
						}
						
						if(get_option("reconews_engine") === false){
							
							add_option("reconews_engine", $reconews_engine);
							
						}else{
							
							update_option("reconews_engine", $reconews_engine);
							
						}
						
					}
					
				}
				
			}
			
			
			
			$title = get_option("reconews_title", '関連ニュース');
			$update_frequency = get_option("reconews_update_frequency", '24');
			$view_count = get_option("reconews_view_count", '5');
			$reconews_engine = get_option("reconews_engine", 'bing');
			
			$update_frequency_list = "";
			$update_frequency_array = array('2' => '2時間', '4' => '4時間', '6' => '6時間', '12' => '12時間', '24' => '1日間隔', '48' => '2日間隔', '96' => '4日間隔', '168' => '7日間隔', '336' => '14日間隔', '672' => '28日間隔');
			foreach($update_frequency_array as $key => $value){
				
				if($update_frequency == $key){
					
					$update_frequency_list .= '<option value="'.$key.'" selected="selected">'.$value.'</option>';
					
				}else{
					
					$update_frequency_list .= '<option value="'.$key.'">'.$value.'</option>';
					
				}
				
			}
			
			$view_count_list = "";
			$view_count_array = array('4', '5', '6', '7', '8', '9', '10');
			for($i = 0; $i < count($view_count_array); $i++){
				
				if($view_count == $view_count_array[$i]){
					
					$view_count_list .= '<option value="'.$view_count_array[$i].'" selected="selected">'.$view_count_array[$i].'件</option>';
					
				}else{
					
					$view_count_list .= '<option value="'.$view_count_array[$i].'">'.$view_count_array[$i].'件</option>';
					
				}
				
			}
			
			if($reconews_engine == 'bing'){
				
				$bing_checked = 'checked="checked"';
				
			}else{
				
				$google_checked = 'checked="checked"';
				
			}
			
			print '<div class=""><h1 class="wp-heading-inline">Reconews</h1></div>';
			
		
			print '<div class="wrap">';
			print '<div>';
			print '<form id="reconews_form" action="" method="post">';
			print '<table class="form-table">';
			print '<tr valign="top">';
			print '<th scope="row"><label for="inputtext">タイトル</label></th>';
			print '<td><input name="reconews_title" type="text" id="reconews_title" value="'.$title.'" class="regular-text" /></td>';
			print '</tr>';
			print '<tr valign="top">';
			print '<th scope="row"><label for="inputtext">ニュース エンジン</label></th>';
			print '<td><input type="radio" name="reconews_engine" value="bing" '.$bing_checked.'>bing (bingの場合は記事の説明文も表示されます。)<br>';
			print '<input type="radio" name="reconews_engine" value="google" '.$google_checked.'>google</td>';
			print '</tr>';
			
			print '<tr valign="top">';
			print '<th scope="row"><label for="inputtext">関連ニュースの表示数</label></th>';
			print '<td><select name="reconews_view_count">'.$view_count_list.'</select></td>';
			print '</tr>';
			print '<tr valign="top">';
			print '<th scope="row"><label for="inputtext">関連ニュースの更新頻度</label></th>';
			print '<td><select name="reconews_update_frequency">'.$update_frequency_list.'</select></td>';
			print '</tr>';
			print '</table>';
			print '<input name="mode" type="hidden" id="free_post_mail_mode" class="regular-text" />';
			wp_nonce_field('reconews_action', 'reconews_nonce_field');
			print '<div class="tablenav bottom"><div class="alignleft actions bulkactions"><input id="free_post_mail_save_button" type="submit" name="button" class="button action" value="保存" /></div></div>';
			print '</form>';
			print '<!-- /.wrap --></div>';

			
			
		}
		
		public function wp_insert_post($post_id, $post, $update){
			
			$status = get_post_status($post_id);
			if($status != "publish"){
				
				return null;
				
			}
			
			$post_id = sanitize_key($post_id);
			#$title = $post->post_title;
			#$content = $post->post_content;
			$post_title = esc_html(wp_strip_all_tags(sanitize_text_field($post->post_title), false));
			$post_content = esc_html(wp_strip_all_tags(sanitize_text_field($post->post_content), false));
			
			$result = $this->igo->parse($post_title);
			$keyword_list = $this->search_keyword($result, $keyword_list, 2);
			
			$result = $this->igo->parse($post_content);
			$keyword_list = $this->search_keyword($result, $keyword_list, 1);
			
			#unset($igo);
			
			arsort($keyword_list);
			$keyword = array();
			$i = 0;
			foreach($keyword_list as $key => $value){
				
				if(mb_strlen($key) != 1){
					
					array_push($keyword, $key);
					if($i == 3){
						
						break;
						
					}
					
					$i++;
					
				}
				
			}
			
			#$keyword_value = implode(" ", $keyword);
			$keyword_value = esc_html(wp_strip_all_tags(sanitize_text_field(implode(" ", $keyword)), false));

			$this_timezone = get_option('timezone_string');
			date_default_timezone_set($this_timezone);
			$date = date("U");
			
			global $wpdb;
			$sql = $wpdb->prepare("SELECT `post_id`,`keyword` FROM $this->table_name WHERE `post_id` = %s;", array($post_id));
			$count = $wpdb->get_var($sql);
			
			if(!empty($keyword) && count($keyword) != 0){
				
				if($count == 0){
					
					$sql = $wpdb->prepare("INSERT INTO $this->table_name (`post_id`, `keyword`, `created_date`, `update_date`) VALUES(%s, %s, %s, %s);", array($post_id, $keyword_value, $date, $date));
					
				}else{
					
					$sql = $wpdb->prepare("UPDATE $this->table_name SET `keyword` = %s, `update_date` = %s WHERE `post_id` = %s;", array($keyword_value, $date, $post_id));
					
				}
				
				$wpdb->query($sql);
				
			}

			
		}
		
		public function save_news_data($post_id, $news_data){

			$this_timezone = get_option('timezone_string');
			date_default_timezone_set($this_timezone);
			$date = date("U");
			
			global $wpdb;
			$sql = $wpdb->prepare("UPDATE $this->table_name SET `news_data` = %s, `update_date` = %s WHERE `post_id` = %s;", array(wp_strip_all_tags(serialize($news_data)), $date, $post_id));
			$wpdb->query($sql);
			
			return $news_data;
			
		}

		public function wp_ajax_free_reconews(){
			
			if(isset($_POST['nonce']) && check_ajax_referer($this->action, 'nonce')){
				
				print json_encode(array('status' => 'success'));
				
			}
			
			die();
			
		}
		
		
		public function add_news($contentData){
			
			$post_id = get_the_ID();
			$get_permalink = get_permalink($post_id);
			$post_type = get_post_type($post_id);
			if($post_type != "post"){
				
				return $contentData;
				
			}
			
			if(is_single($post_id) === false){
				
				return $contentData;
				
			}
			
			$reconews_title = get_option("reconews_title", '関連ニュース');
			$update_frequency = get_option("reconews_update_frequency", '24');
			$view_count = get_option("reconews_view_count", '5');
			$reconews_engine = get_option("reconews_engine", 'bing');

			$this_timezone = get_option('timezone_string');
			date_default_timezone_set($this_timezone);
			$date = date("U");
			
			global $wpdb;
			$sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE `post_id` = %s;", array($post_id));
			$data = $wpdb->get_row($sql);
			if(is_null($data)){
				
				$content = (object) array('post_title' => get_the_title($post_id), 'post_content' => $contentData);
				$this->wp_insert_post($post_id, $content, null);
				$sql = $wpdb->prepare("SELECT * FROM $this->table_name WHERE `post_id` = %s;", array($post_id));
				$data = $wpdb->get_row($sql);
				$news_data = $this->get_news($data->keyword, $get_permalink, $reconews_engine);
				if(is_array($news_data) && count($news_data)){
					
					$this->save_news_data($post_id, $news_data);
					
				}else{
					
					return $contentData;
					
				}
				
			}else{
				
				if(is_null($data->news_data)){
					
					$news_data = $this->get_news($data->keyword, $get_permalink, $reconews_engine);
					if(is_array($news_data) && count($news_data)){
						
						$this->save_news_data($post_id, $news_data);
						
					}else{
						
						return $contentData;
						
					}
					
				}else{
					
					$news_data = unserialize($data->news_data);
					$update_date = $data->update_date + ($update_frequency * 60 * 60);
					#print date('Y/m/d H:i', $update_date)."<br>";
					if($update_date < $date){
						
						$update_news_data = $this->get_news($data->keyword, $get_permalink, $reconews_engine, $news_data);
						if(is_array($update_news_data) && count($update_news_data) != 0){
							
							$news_data = $this->save_news_data($post_id, $update_news_data);
							
						}
						
					}
					
				}
				
			}
			

			
			$keyword_list = array();
			$news_box = '<div class="widget">'.$reconews_title.'<ul>';
			for($i = 0; $i < count($news_data); $i++){
				
				if($view_count <= $i){
					break;
				}
				
				$title = $news_data[$i]['title'];
				$link = $news_data[$i]['link'];
				$news_box .= '<li><a href="'.$link.'" target="_blank">'.$title.'</a>';
				if(isset($news_data[$i]['description'])){
					
					$news_box .= '<div style="font-size: 0.8em;">'.$news_data[$i]['description'].'</div>';
					
				}
				$news_box .= '</li>';
				
			}
			$news_box .= '</ul></div>';
			
			$contentData .= $news_box;
			
			return $contentData;
			
		}
		
		public function get_news($keyword, $get_permalink, $reconews_engine, $news_data = false){

			$language = get_bloginfo('language', 'display');
			
			
			$params = array('q' => $keyword, 'lang' => $language, 'url' => $get_permalink, 'engine' => $reconews_engine);
			if(is_array($news_data)){
				
				$params['news_data'] = json_encode($news_data);
				
			}
			
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, "https://test.cartmaker.net/get_news_wp/0.3/index.php");
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
			curl_setopt($curl, CURLOPT_POST, true);
			
			$response = curl_exec($curl);
			$result = json_decode($response);
			$httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close ($curl);
			
			$data = array();
			if(is_array($result)){
				
				foreach($result as $key => $value){
				
					$title = sanitize_text_field($value->title);
					$link = sanitize_text_field($value->link);
					$array = array("title" => $title, "link" => $link);
					if(isset($value->description)){
						
						$array["description"] = sanitize_text_field($value->description);
						
					}
					array_push($data, $array);
					
				}
				
			}else{
				
				var_dump($response);
				
			}
			
			return $data;
			
		}
		
		public function search_keyword($result, $keyword_list, $set_point = 1){
			
			foreach($result as $key => $value){
				
				$feature_array = explode(",", $value->feature, 6);
				array_pop($feature_array);
				$feature = implode(",", $feature_array);
				#if($feature[0] == '助詞' || $feature[0] == '助動詞' || $feature[0] == '記号' || $feature[0] == '接続詞'){
				if(preg_match('/(助詞)|(助動詞)|(記号)|(接続詞)|(自立)|(数)/', $feature)){
					
					unset($result[$key]);
					
				}else{
					
					$point = $set_point;
					if(preg_match('/(固有名詞)|(人名)/', $feature)){
						
						$point *= 2;
						
					}
					if(isset($keyword_list[$value->surface])){
						
						$keyword_list[$value->surface] += $point;
						
					}else{
						
						$keyword_list[$value->surface] = $point;
						
					}
					
				}
				
			}
			
			return $keyword_list;
			
		}
		
	}	
	
	$recommendNews = new RecommendNews();
	
	
?>