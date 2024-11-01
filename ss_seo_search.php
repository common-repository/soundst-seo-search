<?php
/**
 * Plugin Name: SoundSt SEO Search
 * Plugin URI: http://soundst.com/
 * Description: This plugin allows the editors to search posts/pages with the specific SEO title. 
 * Version: 1.2.3
 * Author: Sound Strategies Inc.
 * Author URI: http://soundst.com
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 **/
if (empty($ss_seo)) 
	$ss_seo = new ss_seo_search();
	
	if(isset($_GET['ss_seo_keyword'])) {
		ss_seo_search::$CountSearches ++;
		ss_seo_search::$Page_num = isset($_GET['page_num']) ? $_GET['page_num'] : 1;
		ss_seo_search::$KeyWord = isset($_GET['ss_seo_keyword']) ? $_GET['ss_seo_keyword'] : '';
		switch($_GET['posttype']) {
			case 'page': $ss_seo->render_meta_box_page(); break;
			case 'post': $ss_seo->render_meta_box_post(); break;
			default: die(0); break;
		}
		die(0);
	}

class ss_seo_search { 
	static $CountSearches = 0;
	const PostsPerPage = 10;
	static $PluginAreaURL = '';
	static $Page_num = 1;
	static $KeyWord = '';
	static $plugin_options = array(
				'ss_custom_field' => '_aioseop_title'
	);
	
	function __construct() {
		self::$plugin_options = get_option('ss_seo_search_options');
		if(!self::$plugin_options) {
			self::$plugin_options = array(
				'ss_custom_field' => '_aioseop_title'
			);
			add_option('ss_seo_search_options',self::$plugin_options);
		}
		add_action('init', array($this,'init_plugin'));
		parse_str($_SERVER['QUERY_STRING'],$q);
		unset($q['m_paged']);
		self::$PluginAreaURL = site_url().'/wp-admin/post.php?'.http_build_query($q);
		add_action( 'add_meta_boxes', array( &$this, 'add_ss_seo_search_page' ) );
		add_action( 'add_meta_boxes', array( &$this, 'add_ss_seo_search_post' ) );
	}
	
	function PluginUrl() {
        if (function_exists('plugins_url')) return trailingslashit(plugins_url(basename(dirname(__FILE__))));
        $path = dirname(__FILE__);
        $path = str_replace("\\","/",$path);
        $path = trailingslashit(get_bloginfo('wpurl')) . trailingslashit(substr($path,strpos($path,"wp-content/")));
        return $path;
    }
	
	function init_plugin () {
		if (is_admin()) {
	    	$this->admin_init();
	    	wp_enqueue_script('jquery_common',$this->PluginUrl().'js/ss_common.js');
		}
	}	
	
	function admin_init() {
	    add_action('admin_menu', array($this,'admin_menu'));
	}
	
	function admin_menu() {
		if (function_exists('add_management_page')) {
			add_management_page('SoundSt SEO Search','SoundSt SEO Search',8,'ss_seo_search', array($this,'options_page'));
		}
	}
	
function options_page() {
		if (!current_user_can('edit_themes'))  {
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		self::$plugin_options = get_option('ss_seo_search_options');
		$notice = '';
		if(isset($_POST['submit'])) {
			if(isset($_POST['ss_custom_field'])) {
				$plugin_options = array(
					'ss_custom_field' => isset($_POST['ss_custom_field'])&&!empty($_POST['ss_custom_field']) ? $_POST['ss_custom_field'] : self::$plugin_options['ss_custom_field']
				);
				if($_POST['ss_custom_field'] == 'ss__manual' && isset($_POST['ss_custom_field_man']) && !empty($_POST['ss_custom_field_man'])) {
					$plugin_options['ss_custom_field'] = $_POST['ss_custom_field_man'];
				}
			} 
			self::$plugin_options = $plugin_options;
			update_option('ss_seo_search_options', self::$plugin_options);	
			$notice = 'Your settings have been saved!';	
		} 
		$this->RenderPluginSettings($notice);	
	}
	
	function RenderPluginSettings($updated_html = '') { ?>
		<div id="wraps" class="wraps clearfloat">  
			<h2>Manage SEO Search Options</h2>
			<p>Please choose custom field name which will define the custom post SEO title</p>
			<?php if(!empty($updated_html)) { ?><div id='message' class='updated below-h2'><?php echo $updated_html; ?></div><?php } ?>
			<form method="post">
				<div style="margin-bottom:15px;">
					<input class="ss_lab_1" type="radio" id="ss_custom_field1" name="ss_custom_field" value="_aioseop_title"
					<?php if(self::$plugin_options['ss_custom_field'] == '_aioseop_title') echo ' checked' ?>/>
					<label class="ss_lab_1" for="ss_custom_field1">All in One SEO Pack: </label><br />
					
					<input class="ss_lab_1" type="radio" id="ss_custom_field2" name="ss_custom_field" value="_ghpseo_secondary_title"
					<?php if(self::$plugin_options['ss_custom_field'] == '_ghpseo_secondary_title') echo ' checked' ?>/>
					<label class="ss_lab_1" for="ss_custom_field2">Greg’s High Performance SEO: </label><br />
					
					<input class="ss_lab_1" type="radio" id="ss_custom_field3" name="ss_custom_field" value="_yoast_wpseo_title"
					<?php if(self::$plugin_options['ss_custom_field'] == '_yoast_wpseo_title') echo ' checked' ?>/>
					<label class="ss_lab_1" for="ss_custom_field3">Wordpress SEO: </label><br />
					
					<input class="ss_lab_2" type="radio" id="ss_custom_field_m" name="ss_custom_field" value="ss__manual"<?php 
					if(self::$plugin_options['ss_custom_field'] != '_ghpseo_secondary_title' && self::$plugin_options['ss_custom_field'] != '_aioseop_title' && self::$plugin_options['ss_custom_field'] != '_yoast_wpseo_title') 
					echo ' checked' ?>/>
					<label class="ss_lab_2" for="ss_custom_field_m">Enter custom field key manually: </label>
					<div id="ss_display">
						<input style="min-width: 200px;" type="text" id="ss_custom_field_man" name="ss_custom_field_man" value="<?php echo self::$plugin_options['ss_custom_field']?>"/>	
					</div>	
				</div>
				<input type="submit" value="Update Settings" name="submit" class="button-primary"/>
			</form>
		</div>
	<?php }

	static function getNextPageURL($max_pages) {
		$next_num = $max_pages > 1 ? (self::$Page_num > 1 ? (self::$Page_num < $max_pages ? self::$Page_num + 1 : 1) : 2) :1;
		if($next_num > 1) {
			return true;
		}
		return false;
	}
	
	static function getPrevPageURL() {
		$prev_num = self::$Page_num > 0 ? (self::$Page_num > 1 ? (self::$Page_num - 1) : 0) : 0;
		if($prev_num > 0) {
			return true;
		}
		return false;
	}
	
	static function getPosts($post_type = 'post') {
		if(!empty(self::$KeyWord)) {
			$paged = self::$Page_num == 0 ? 1 : self::$Page_num;
			$offset = self::$Page_num <= 1 ? 0 : (self::$Page_num - 1)* self::PostsPerPage;
			$notice = ' results were found for ';
			$args = array(
				'post_type' => 'any',
				'post_status' => 'publish',
				'posts_per_page' => self::PostsPerPage,
				'order'=> 'DESC',
				'orderby'=>'date',
				'paged' => $paged,
				'offset' => $offset, 
			);
				$args['meta_query'] = array(
					array(
						'key' => self::$plugin_options['ss_custom_field'],
						'value' => self::$KeyWord,
						'compare' => 'LIKE'
					)
				);
			$q = new WP_Query($args);
			if(count($q->posts) > 0) { 
				echo '<p>'.$q->found_posts.$notice.'<strong>'.self::$KeyWord.'</strong> (page '.ss_seo_search::$Page_num.' from '.$q->max_num_pages.')</p>';
				?>
				<ul class="ss_seo_search_list" id="<?php echo !empty(self::$KeyWord) ? self::$KeyWord : 'ss_seo_search_list'; ?>">
				<?php foreach($q->posts as $post) { 
						if($post->post_type == 'page') {
							$permalink = self::getPagePermalink($post->ID);
						} else $permalink = get_permalink($post->ID);
					?>
					<li id="<?php echo $post->post_type.'-'.$post->ID?>">
						<a target="_blank" href="<?php echo $permalink;?>"><?php echo get_post_meta($post->ID, self::$plugin_options['ss_custom_field'], true); ?></a> (<?php echo  get_the_time('F j, Y \a\t h:i:s a',$post)?>) <code>[<?php echo $post->post_type; ?>]</code>
					</li>
					<?php
				}?>
				</ul>
				<?php 
				$next_link = self::getNextPageURL($q->max_num_pages);
				$prev_link = self::getPrevPageURL();
				if($next_link !== false || $prev_link !== false) {?>
				<div style="display: inline-block; width:100%; margin-top:10px;">
				<?php if($next_link !== false) {?>
						<div class="alignleft">
							<a class="ss_paging" href="javascript:ss_setNextPage(<?php echo '\''.(self::$Page_num+1).'\',\''.$post_type.'\',\''.self::$KeyWord.'\''?>)">&laquo;Previous</a>
						</div>
					<?php }
					if($prev_link !== false) {?>
						<div class="alignright">
							<a class="ss_paging" href="javascript:ss_setNextPage(<?php echo '\''.(self::$Page_num-1).'\',\''.$post_type.'\',\''.self::$KeyWord.'\''?>)">More&raquo;</a>
						</div>
					<?php }?>
				</div>	
				<?php }
				wp_reset_query();
			} else {
				echo '<p style="color:red">No'.$notice.'<strong>'.self::$KeyWord.'</strong></p>';
			}
		} 
	}
	
	static function printSearchForm($post_type) {?>
		<script language="javascript" type="text/javascript" id="ss_input_script">
			var SS_SEO_SEARCH = '<?php echo urlencode(self::$PluginAreaURL); ?>';
		</script>
			<input type="text" title="Enter SEO title" name="ss_seo_keyword" id="ss_seo_keyword" value="<?php echo self::$KeyWord; ?>" style="width: 200px;"/>
			<input type="hidden" name="ss_seo_posttype" id="ss_seo_posttype" value="<?php echo $post_type; ?>"/>
			<span id="ss_seo_submit_wrap"><input type="button" id="ss_seo_submit" value="Search" /><img src="<?php echo site_url(); ?>/wp-admin/images/loading.gif" style="display: none;" /></span>
		<br />
	<?php }
	
	function add_ss_seo_search_page() {
		add_meta_box( 
             'ss_seo_search_page'
            ,'SoundSt SEO Search'
            ,array( &$this, 'render_meta_box_page' )
            ,'page' 
            ,'advanced'
            ,'high'
        );
	}
	
	function add_ss_seo_search_post() {
		add_meta_box( 
             'ss_seo_search_post'
            ,'SoundSt SEO Search'
            ,array( &$this, 'render_meta_box_post' )
            ,'post' 
            ,'advanced'
            ,'high'
        );
	}
	
	function render_meta_box_page() {
		if(self::$CountSearches == 0)
			self::printSearchForm('page');
		echo '<div id="ss_search_wrap">';
		self::getPosts('page');
		echo '</div>';
	}
	
	function render_meta_box_post() {
		if(self::$CountSearches == 0)
			self::printSearchForm('post');
		echo '<div id="ss_search_wrap">';
		self::getPosts('post');
		echo '</div>';
	}
	
	static function getPagePermalink($id) {
		return get_bloginfo('url').'/?p='.$id;
	}
	
}

function call_ss_seo_search() {
    return new ss_seo_search();
}

if ( is_admin() ) {
	add_action( 'edit_post', 'call_ss_seo_search' );
}

?>
