<?php
	/*
		Plugin Name: Karailiev's sitemap
		Plugin URI: http://blog.karailiev.net/karailievs-sitemap/
		Description: Generates sitemap for spiders.
		Version: 1.0
		Author: Valentin Karailiev
		Author URI: http://blog.karailiev.net/
	*/
	
	$ksm_settings = get_option('ksm_settings');
	$ksm_settings['version'] = '1.0';
	
	// If settings are missing check for old verion or set default values
	if (!isset($ksm_settings['general_active']))		$ksm_settings['general_active']		= get_option('ksm_active')				? get_option('ksm_active')				: true;
	if (!isset($ksm_settings['news_active']))			$ksm_settings['news_active']		= get_option('ksm_news_active')			? get_option('ksm_news_active')			: false;
	if (!isset($ksm_settings['update_on_comments']))	$ksm_settings['update_on_comments']	= get_option('ksm_comments')			? get_option('ksm_comments')			: false;
	if (!isset($ksm_settings['include_attachments']))	$ksm_settings['include_attachments']= get_option('ksm_attachments')			? get_option('ksm_attachments')			: true;
	if (!isset($ksm_settings['include_categories']))	$ksm_settings['include_categories']	= get_option('ksm_categories')			? get_option('ksm_categories')			: true;
	if (!isset($ksm_settings['include_tags']))			$ksm_settings['include_tags']		= get_option('ksm_tags')				? get_option('ksm_tags')				: true;
	if (!isset($ksm_settings['base_path']))				$ksm_settings['base_path']			= get_option('ksm_path')				? get_option('ksm_path')				: "./";
	if (!isset($ksm_settings['last_google_ping']))		$ksm_settings['last_google_ping']	= get_option('ksm_last_ping')			? get_option('ksm_last_ping')			: 0;
	if (!isset($ksm_settings['post_priority']))			$ksm_settings['post_priority']		= get_option('ksm_post_priority')		? get_option('ksm_post_priority')		: 0.5;
	if (!isset($ksm_settings['post_frequency']))		$ksm_settings['post_frequency']		= get_option('ksm_post_frequency')		? get_option('ksm_post_frequency')		: 'monthly';
	if (!isset($ksm_settings['page_priority']))			$ksm_settings['page_priority']		= get_option('ksm_page_priority')		? get_option('ksm_page_priority')		: 0.5;
	if (!isset($ksm_settings['page_frequency']))		$ksm_settings['page_frequency']		= get_option('ksm_page_frequency')		? get_option('ksm_page_frequency')		: 'monthly';
	if (!isset($ksm_settings['tag_priority']))			$ksm_settings['tag_priority']		= get_option('ksm_tag_priority')		? get_option('ksm_tag_priority')		: 0.1;
	if (!isset($ksm_settings['tag_frequency']))			$ksm_settings['tag_frequency']		= get_option('ksm_tag_frequency')		? get_option('ksm_tag_frequency')		: 'weekly';
	if (!isset($ksm_settings['category_priority']))		$ksm_settings['category_priority']	= get_option('ksm_category_priority')	? get_option('ksm_category_priority')	: 0.1;
	if (!isset($ksm_settings['category_frequency']))	$ksm_settings['category_frequency']	= get_option('ksm_category_frequency')	? get_option('ksm_category_frequency')	: 'weekly';
	
	if (!isset($ksm_settings['attachment_priority']))	$ksm_settings['attachment_priority']=	0.3;
	if (!isset($ksm_settings['attachment_frequency']))	$ksm_settings['attachment_frequency']=	'yearly';
	if (!isset($ksm_settings['priorities']))			$ksm_settings['priorities'] = array();

	
	// Update per post priority
	if (isset($_POST['ksm_priority'])) {
		if ($_POST['ksm_priority'] == 0) {
			unset ($ksm_settings['priorities'][$_POST['post_ID']]);
		} else {
			$ksm_settings['priorities'][$_POST['post_ID']] = $_POST['ksm_priority'];
		}
	}
	
	update_option('ksm_settings', $ksm_settings);

	
	// Add hooks
	add_action('admin_menu', 'ksm_add_pages');
	add_action('admin_menu', 'ksm_add_per_post_settings_box');

	if ($ksm_settings['general_active'] || $ksm_settings['news_active']) {
		add_action('edit_post', 'ksm_generate_sitemap');
		add_action('delete_post', 'ksm_generate_sitemap');
		add_action('private_to_published', 'ksm_generate_sitemap');
		add_action('publish_page', 'ksm_generate_sitemap');
		add_action('publish_phone', 'ksm_generate_sitemap');
		add_action('publish_post', 'ksm_generate_sitemap');
		add_action('save_post', 'ksm_generate_sitemap');
		add_action('xmlrpc_publish_post', 'ksm_generate_sitemap');

		if ($ksm_settings['update_on_comments']) {
			add_action('comment_post', 'ksm_generate_sitemap');
			add_action('edit_comment', 'ksm_generate_sitemap');
			add_action('delete_comment', 'ksm_generate_sitemap');
			add_action('pingback_post', 'ksm_generate_sitemap');
			add_action('trackback_post', 'ksm_generate_sitemap');
			add_action('wp_set_comment_status', 'ksm_generate_sitemap');
		}

		if ($ksm_settings['include_attachments']) {
			add_action('add_attachment', 'ksm_generate_sitemap');
			add_action('edit_attachment', 'ksm_generate_sitemap');
			add_action('delete_attachment', 'ksm_generate_sitemap');
		}
	}

	//Add config page
	function ksm_add_pages() {
		add_options_page("Karailiev's sitemap", "Sitemap", 8, basename(__FILE__), "ksm_admin_page");
	}
	
	//Add perpost settings
	function ksm_add_per_post_settings_box() {
		if (function_exists('add_meta_box')) {
			add_meta_box("ksm", "Karailiev's sitemap", "ksm_per_post_settings", "post");
			add_meta_box("ksm", "Karailiev's sitemap", "ksm_per_post_settings", "page");		
		}
	}


	function ksm_permissions() {
		global $ksm_settings;
		
		$ksm_path = ABSPATH . $ksm_settings['base_path'];
		$ksm_file_path = $ksm_path . "/sitemap.xml";
		$ksm_news_file_path = $ksm_path . "/sitemap-news.xml";
		
		if ($ksm_settings['general_active'] && is_file($ksm_file_path) && is_writable($ksm_file_path)) $ksm_permission = 0;
		elseif ($ksm_settings['general_active'] && !is_file($ksm_file_path) && is_writable($ksm_path)) {
			$fp = fopen($ksm_file_path, 'w');
			fwrite($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" />");
			fclose($fp);
			if (is_file($ksm_file_path) && is_writable($ksm_file_path)) $ksm_permission = 0;
			else $ksm_permission = 1;
		}
		elseif ($ksm_settings['general_active']) $ksm_permission = 1;
		else $ksm_permission = 0;
		
		
		if ($ksm_settings['news_active'] && is_file($ksm_news_file_path) && is_writable($ksm_news_file_path)) $ksm_permission += 0;
		elseif ($ksm_settings['news_active'] && !is_file($ksm_news_file_path) && is_writable($ksm_path)) {
			$fp = fopen($ksm_news_file_path, 'w');
			fwrite($fp, "<?xml version=\"1.0\" encoding=\"UTF-8\"?><urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\" />");
			fclose($fp);
			if (is_file($ksm_news_file_path) && is_writable($ksm_news_file_path)) $ksm_permission += 0;
			else $ksm_permission += 2;
		}
		elseif ($ksm_settings['news_active']) $ksm_permission += 2;
		else $ksm_permission += 0;

		return $ksm_permission;
	}


	function ksm_generate_sitemap() {
		global $ksm_settings, $table_prefix;
		$t = $table_prefix;

		$ksm_permission = ksm_permissions();
		if ($ksm_permission > 2 || (!$ksm_settings['general_active'] && !$ksm_settings['news_active'])) return;

		mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
		mysql_query("SET NAMES '".DB_CHARSET."'");
		mysql_select_db(DB_NAME);

		$home = get_option('home') . "/";
		
		$out = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$out .= "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">
	<!-- Generated by Karailiev's sitemap ".$ksm_settings['version']." plugin -->
	<!-- http://www.karailiev.net/karailievs-sitemap/ -->
	<!-- Created ".date("F d, Y, H:i")."-->
	<url>
		<loc>".$home."</loc>
		<lastmod>".gmdate ("Y-m-d\TH:i:s\Z")."</lastmod>
		<changefreq>weekly</changefreq>
		<priority>1</priority>
	</url>";

		
		$out_news = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>";
		$out_news .= "\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:news=\"http://www.google.com/schemas/sitemap-news/0.9\">
	<!-- Generated by Karailiev's sitemap ".$ksm_settings['version']." plugin -->
	<!-- http://www.karailiev.net/karailievs-sitemap/ -->
	<!-- Created ".date("F d, Y, H:i")."-->";
		
		
		$result = mysql_query("
			SELECT 
				`".$t."posts`.`ID`, `".$t."posts`.`post_date_gmt`, `".$t."posts`.`post_date`, `".$t."posts`.`post_modified_gmt`, `".$t."posts`.`post_name`, `".$t."posts`.`post_type`,
				MAX(`".$t."comments`.`comment_date_gmt`) AS `comment_date_gmt`, MAX(`".$t."comments`.`comment_date`) AS `comment_date`
			FROM `".$t."posts`
			LEFT JOIN `".$t."comments` ON `".$t."comments`.`comment_post_ID` = `".$t."posts`.`ID`
			WHERE
				(
					`".$t."posts`.`post_status`='publish'
					AND (
						`".$t."posts`.`post_type`='page'
						OR `".$t."posts`.`post_type`='post'
					)
					AND (
						`".$t."comments`.`comment_approved`='1'
						OR `".$t."comments`.`comment_approved` IS NULL
					)
				)
				" . ($ksm_settings['include_attachments']?"OR `".$t."posts`.`post_type`='attachment'":"") . "
			GROUP BY `".$t."posts`.`ID`
			ORDER BY `".$t."posts`.`post_modified_gmt` DESC
		");
		echo mysql_error();
		
		$now = time();
		$treeDays = 3*24*60*60;
		while ($data = mysql_fetch_assoc($result)) {
			if (isset($ksm_settings['priorities'][$data['ID']]) && $ksm_settings['priorities'][$data['ID']] == -1) {
				continue;
			} else if (isset($ksm_settings['priorities'][$data['ID']])
					&& $ksm_settings['priorities'][$data['ID']] > 0  
					&& $ksm_settings['priorities'][$data['ID']] <= 0.9) {
				$priority = $ksm_settings['priorities'][$data['ID']];
			} else {
				if ($data['post_type'] == "post") {
					$priority = $ksm_settings['post_priority'];
				} else if ($data['post_type'] == "page") {
					$priority = $ksm_settings['page_priority'];
				} else if (($data['post_type'] == "attachment")) {
					$priority = $ksm_settings['attachment_priority'];
				}
			}
			
			if ($ksm_settings['news_active'] && $ksm_permission != 2) {
				$postDate = strtotime($data['post_date']);
				if ($now - $postDate < $treeDays) {
					$out_news .= "
	<url>
		<loc>".get_permalink($data['ID'])."</loc>
		<news:news>
			<news:publication_date>".str_replace(" ", "T", $data['post_date_gmt'])."Z"."</news:publication_date>
		</news:news>
	</url>";
				}
			}
			
			if ($ksm_settings['general_active'] && $ksm_permission != 1) {
				$date = str_replace(" ", "T", $data['post_date_gmt'])."Z";
				if ($ksm_settings['update_on_comments'] && $data['comment_date_gmt']) {
					$postDate = strtotime($data['post_date_gmt']);
					$commentDate = strtotime($data['comment_date_gmt']);
					if ($commentDate > $postDate) {
						$date = str_replace(" ", "T", $data['comment_date_gmt'])."Z";
					}
				}
				$out .= "
	<url>
		<loc>".get_permalink($data['ID'])."</loc>
		<lastmod>".$date."</lastmod>
		<changefreq>".($data['post_type']=="post"?$ksm_settings['post_frequency']:$ksm_settings['page_frequency'])."</changefreq>
		<priority>" . $priority . "</priority>
	</url>";
				
			}
		}
		
		
		if ($ksm_settings['general_active'] && $ksm_permission != 1 && ($ksm_settings['include_categories'] || $ksm_settings['include_tags'])) {
			$what_kind = "";
			if ($ksm_settings['include_categories']) $what_kind = "`".$t."term_taxonomy`.`taxonomy`='category'";
			if ($ksm_settings['include_tags']) {
				if ($what_kind == "") $what_kind = "`".$t."term_taxonomy`.`taxonomy`='post_tag'";
				else $what_kind = "(" . $what_kind . " OR `".$t."term_taxonomy`.`taxonomy`='post_tag')";
			}

			$result = mysql_query("
				SELECT `".$t."term_taxonomy`.`term_id`, `".$t."term_taxonomy`.`taxonomy`
				FROM `".$t."term_taxonomy`
				WHERE
					`".$t."term_taxonomy`.`count` > 0
					AND ".$what_kind."
			");
			while ($data = mysql_fetch_assoc($result)) {
				$out .= "
	<url>
		<loc>".($data['taxonomy']=="post_tag"?get_tag_link($data['term_id']):get_category_link($data['term_id']))."</loc>
		<changefreq>".($data['taxonomy']=="post_tag"?$ksm_settings['tag_frequency']:$ksm_settings['category_frequency'])."</changefreq>
		<priority>".($data['taxonomy']=="post_tag"?$ksm_settings['tag_priority']:$ksm_settings['category_priority'])."</priority>
	</url>";
			}
		}


		$out_news .= "\n</urlset>";
		$out .= "\n</urlset>";
		
		
		if ($ksm_settings['general_active'] && $ksm_permission != 1) {
			$fp = fopen(ABSPATH . $ksm_path . "/sitemap.xml", 'w');
			fwrite($fp, $out);
			fclose($fp);
		}
		
		if ($ksm_settings['news_active'] && $ksm_permission != 2) {
			$fp = fopen(ABSPATH . $ksm_path . "/sitemap-news.xml", 'w');
			fwrite($fp, $out_news);
			fclose($fp);
		}
		

		$ksm_settings['last_google_ping'] = get_option('ksm_last_ping');
		if ((time() - $ksm_settings['last_google_ping']) > 60 * 60) {
			//get_headers("http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($home . $ksm_path . "sitemap.xml"));	//PHP5+
			$fp = @fopen("http://www.google.com/webmasters/tools/ping?sitemap=" . urlencode($home . $ksm_path . "sitemap.xml"), 80);
			@fclose($fp);
			$ksm_settings['last_google_ping'] = time();
			update_option('ksm_settings', $ksm_settings);
		}
	}



	//Config page
	function ksm_admin_page() {
		global $ksm_settings;
		$msg = "";

		// Check form submission and update options
		if ('ksm_submit' == $_POST['ksm_submit']) {
			$ksm_settings['general_active']		= $_POST['ksm_active']				? $_POST['ksm_active']				: false;
			$ksm_settings['news_active']		= $_POST['ksm_news_active']			? $_POST['ksm_news_active']			: false;
			$ksm_settings['update_on_comments']	= $_POST['ksm_comments']			? $_POST['ksm_comments']			: false;
			$ksm_settings['include_attachments']= $_POST['ksm_attachments']			? $_POST['ksm_attachments']			: false;
			$ksm_settings['include_categories']	= $_POST['ksm_categories']			? $_POST['ksm_categories']			: false;
			$ksm_settings['include_tags']		= $_POST['ksm_tags']				? $_POST['ksm_tags']				: false;
			
			$priorities = array(0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9);
			$frequencies = array('hourly', 'daily', 'weekly', 'monthly', 'yearly');
			
			$ksm_settings['post_priority']		= (isset($_POST['ksm_post_priority']) && in_array(trim(strtolower($_POST['ksm_post_priority'])), $priorities))?trim(strtolower($_POST['ksm_post_priority']))			: 0.5;
			$ksm_settings['page_priority']		= (isset($_POST['ksm_page_priority']) && in_array(trim(strtolower($_POST['ksm_page_priority'])), $priorities))?trim(strtolower($_POST['ksm_page_priority']))			: 0.5;
			$ksm_settings['tag_priority']		= (isset($_POST['ksm_tag_priority']) && in_array(trim(strtolower($_POST['ksm_tag_priority'])), $priorities))?trim(strtolower($_POST['ksm_tag_priority']))				: 0.1;
			$ksm_settings['category_priority']	= (isset($_POST['ksm_category_priority']) && in_array(trim(strtolower($_POST['ksm_category_priority'])), $priorities))?trim(strtolower($_POST['ksm_category_priority'])): 0.1;
			$ksm_settings['attachment_priority']= (isset($_POST['ksm_attachment_priority']) && in_array(trim(strtolower($_POST['ksm_attachment_priority'])), $priorities))?trim(strtolower($_POST['ksm_attachment_priority'])): 0.3;
			
			$ksm_settings['post_frequency']		= (isset($_POST['ksm_post_frequency']) && in_array(trim(strtolower($_POST['ksm_post_frequency'])), $frequencies))?trim(strtolower($_POST['ksm_post_frequency']))			: 'monthly';
			$ksm_settings['page_frequency']		= (isset($_POST['ksm_page_frequency']) && in_array(trim(strtolower($_POST['ksm_page_frequency'])), $frequencies))?trim(strtolower($_POST['ksm_page_frequency']))			: 'monthly';
			$ksm_settings['tag_frequency']		= (isset($_POST['ksm_tag_frequency']) && in_array(trim(strtolower($_POST['ksm_tag_frequency'])), $frequencies))?trim(strtolower($_POST['ksm_tag_frequency']))				: 'weekly';
			$ksm_settings['category_frequency']	= (isset($_POST['ksm_category_frequency']) && in_array(trim(strtolower($_POST['ksm_category_frequency'])), $frequencies))?trim(strtolower($_POST['ksm_category_frequency'])): 'weekly';
			$ksm_settings['attachment_frequency']=(isset($_POST['ksm_attachment_frequency']) && in_array(trim(strtolower($_POST['ksm_attachment_frequency'])), $frequencies))?trim(strtolower($_POST['ksm_attachment_frequency'])): 'yearly';
			
			$newPath = trim($_POST['ksm_path']);
			if ($newPath == "" || $newPath == "/") $ksm_settings['base_path'] = "./";
			elseif ($newPath[strlen($newPath)-1] != "/") $ksm_settings['base_path'] .= "/";
			
			if ($_POST['ksm_set_all_to_default_priority']) {
				$ksm_settings['priorities'] = array();
			}
			
			update_option('ksm_settings', $ksm_settings);
			ksm_generate_sitemap();
		}
		

		$ksm_permission = ksm_permissions();
		if ($ksm_permission == 1) $msg = "Error: there is a problem with <em>sitemap.xml</em>. It doesn't exist or is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
		elseif ($ksm_permission == 2) $msg = "Error: there is a problem with <em>sitemap-news.xml</em>. It doesn't exist or is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
		elseif ($ksm_permission == 3) $msg = "Error: there is a problem with <em>sitemap.xml</em>. It doesn't exist or is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.<br/>Error: there is a problem with <em>sitemap-news.xml</em>. It doesn't exist or is not writable. <a href=\"http://www.karailiev.net/karailievs-sitemap/\" target=\"_blank\" >For help see the plugin's homepage</a>.";
?>
	<div class="wrap">
<?php	if ($msg) {	?>
	<div id="message" class="error"><p><strong><?php echo $msg; ?></strong></p></div>
<?php	}	?>
		<h2>Karailiev's Sitemap Settings</h2>
		<form name="form1" method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>&amp;updated=true">
			<input type="hidden" name="ksm_submit" value="ksm_submit" />
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Basic settings</th>
					<td>
						<label for="ksm_active">
							<input name="ksm_active" type="checkbox" id="ksm_active" value="1" <?php echo $ksm_settings['general_active']?'checked="checked"':''; ?> />
							Create general sitemap
						</label><br />
						<label for="ksm_news_active">
							<input name="ksm_news_active" type="checkbox" id="ksm_news_active" value="1" <?php echo $ksm_settings['news_active']?'checked="checked"':''; ?> />
							Create news sitemap
						</label><br />
						<label for="ksm_comments">
							<input name="ksm_comments" type="checkbox" id="ksm_comments" value="1" <?php echo $ksm_settings['update_on_comments']?'checked="checked"':''; ?> />
							Update on comment
						</label><br />
					</td>
				</tr>
				<tr>
					<th scope="row">Post settings</th>
					<td>
						Default post priority: 
						<select name="ksm_post_priority">
							<option <?php echo $ksm_settings['post_priority']==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_settings['post_priority']==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_settings['post_priority']==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_settings['post_priority']==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_settings['post_priority']==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_settings['post_priority']==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_settings['post_priority']==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_settings['post_priority']==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_settings['post_priority']==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default post change frequency: 
						<select name="ksm_post_frequency">
							<option <?php echo $ksm_settings['post_frequency']=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_settings['post_frequency']=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_settings['post_frequency']=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_settings['post_frequency']=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_settings['post_frequency']=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />
					</td>
				</tr>
				<tr>
					<th scope="row">Page settings</th>
					<td>
						Default page priority: 
						<select name="ksm_page_priority">
							<option <?php echo $ksm_settings['page_priority']==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_settings['page_priority']==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_settings['page_priority']==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_settings['page_priority']==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_settings['page_priority']==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_settings['page_priority']==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_settings['page_priority']==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_settings['page_priority']==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_settings['page_priority']==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default page change frequency: 
						<select name="ksm_page_frequency">
							<option <?php echo $ksm_settings['page_frequency']=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_settings['page_frequency']=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_settings['page_frequency']=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_settings['page_frequency']=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_settings['page_frequency']=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />
					</td>
				</tr>
				<tr>
					<th scope="row">Attachment settings</th>
					<td>
						<label for="ksm_attachments">
							<input name="ksm_attachments" type="checkbox" id="ksm_attachments" value="1" <?php echo $ksm_settings['include_attachments']?'checked="checked"':''; ?> />
							Include attachments
						</label><br />
						
						Default category priority: 
						<select name="ksm_attachment_priority">
							<option <?php echo $ksm_settings['attachment_priority']==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_settings['attachment_priority']==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_settings['attachment_priority']==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_settings['attachment_priority']==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_settings['attachment_priority']==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_settings['attachment_priority']==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_settings['attachment_priority']==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_settings['attachment_priority']==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_settings['attachment_priority']==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default attachment change frequency: 
						<select name="ksm_attachment_frequency">
							<option <?php echo $ksm_settings['attachment_frequency']=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_settings['attachment_frequency']=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_settings['attachment_frequency']=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_settings['attachment_frequency']=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_settings['attachment_frequency']=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />
					</td>
				</tr>
				<tr>
					<th scope="row">Category settings</th>
					<td>
						<label for="ksm_categories">
							<input name="ksm_categories" type="checkbox" id="ksm_categories" value="1" <?php echo $ksm_settings['include_categories']?'checked="checked"':''; ?> />
							Include categories
						</label><br />
						
						Default category priority: 
						<select name="ksm_category_priority">
							<option <?php echo $ksm_settings['category_priority']==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_settings['category_priority']==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_settings['category_priority']==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_settings['category_priority']==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_settings['category_priority']==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_settings['category_priority']==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_settings['category_priority']==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_settings['category_priority']==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_settings['category_priority']==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default category change frequency: 
						<select name="ksm_category_frequency">
							<option <?php echo $ksm_settings['category_frequency']=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_settings['category_frequency']=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_settings['category_frequency']=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_settings['category_frequency']=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_settings['category_frequency']=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />
					</td>
				</tr>
				<tr>
					<th scope="row">Tag settings</th>
					<td>
						<label for="ksm_tags">
							<input name="ksm_tags" type="checkbox" id="ksm_tags" value="1" <?php echo $ksm_settings['include_tags']?'checked="checked"':''; ?> />
							Include tags
						</label><br />
						
						Default tag priority: 
						<select name="ksm_tag_priority">
							<option <?php echo $ksm_settings['tag_priority']==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
							<option <?php echo $ksm_settings['tag_priority']==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
							<option <?php echo $ksm_settings['tag_priority']==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
							<option <?php echo $ksm_settings['tag_priority']==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
							<option <?php echo $ksm_settings['tag_priority']==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
							<option <?php echo $ksm_settings['tag_priority']==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
							<option <?php echo $ksm_settings['tag_priority']==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
							<option <?php echo $ksm_settings['tag_priority']==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
							<option <?php echo $ksm_settings['tag_priority']==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
						</select><br />
						
						Default tag change frequency: 
						<select name="ksm_tag_frequency">
							<option <?php echo $ksm_settings['tag_frequency']=="hourly"?'selected="selected"':'';?> value="hourly">hourly</option>
							<option <?php echo $ksm_settings['tag_frequency']=="daily"?'selected="selected"':'';?> value="daily">daily</option>
							<option <?php echo $ksm_settings['tag_frequency']=="weekly"?'selected="selected"':'';?> value="weekly">weekly</option>
							<option <?php echo $ksm_settings['tag_frequency']=="monthly"?'selected="selected"':'';?> value="monthly">monthly</option>
							<option <?php echo $ksm_settings['tag_frequency']=="yearly"?'selected="selected"':'';?> value="yearly">yearly</option>
						</select><br />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row">Advanced settings</th>
					<td>
						Sitemap folder (relatively to blog's home): <input name="ksm_path" type="text" id="ksm_path" value="<?php echo $ksm_settings['base_path']?>" />
						<br />
						<label for="ksm_set_all_to_default_priority">
							<input name="ksm_set_all_to_default_priority" type="checkbox" id="ksm_set_all_to_default_priority" value="1" />
							Force all posts and pages to default priority
						</label><br />
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"></th>
					<td>
						<table><tr><td>
							<a target="_blank" href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=ZQVJDMHJRWW3W&lc=US&item_name=Valentin%20Karailiev&item_number=wp_plugin_karailievs%2dsitemap&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted">
								<img src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" alt="Donate" style="border:none!important;" />
							</a>
						</td><td>
							<strong>Support this plugin</strong>
						</td></tr></table>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" value="Save &amp; Rebuild" />
			</p>
		</form>
	</div>
<?php
	}
	
	function ksm_per_post_settings() {
		global $ksm_settings, $post;
	    $post_id = $post->ID;
	    $priority = $ksm_settings['priorities'][$post_id];
	    $default_priority = $post->post_type == 'post' ? $ksm_settings['post_priority'] : $ksm_settings['page_priority']
?>
		Set custom priority: 
		<select name="ksm_priority">
			<option <?php echo $priority==0?'selected="selected"':'';?> value="0">Default (<?php echo $default_priority ?>)</option>
			<option <?php echo $priority==0.9?'selected="selected"':'';?> value="0.9">0.9</option>
			<option <?php echo $priority==0.8?'selected="selected"':'';?> value="0.8">0.8</option>
			<option <?php echo $priority==0.7?'selected="selected"':'';?> value="0.7">0.7</option>
			<option <?php echo $priority==0.6?'selected="selected"':'';?> value="0.6">0.6</option>
			<option <?php echo $priority==0.5?'selected="selected"':'';?> value="0.5">0.5</option>
			<option <?php echo $priority==0.4?'selected="selected"':'';?> value="0.4">0.4</option>
			<option <?php echo $priority==0.3?'selected="selected"':'';?> value="0.3">0.3</option>
			<option <?php echo $priority==0.2?'selected="selected"':'';?> value="0.2">0.2</option>
			<option <?php echo $priority==0.1?'selected="selected"':'';?> value="0.1">0.1</option>
			<option <?php echo $priority==-1?'selected="selected"':'';?> value="-1">Exclude from sitemap</option>
		</select>
<?php
	} 
?>
