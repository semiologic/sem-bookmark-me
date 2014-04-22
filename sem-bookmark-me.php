<?php
/*
Plugin Name: Bookmark Me
Plugin URI: http://www.semiologic.com/software/bookmark-me/
Description: Widgets that let your visitors share your webpages on social media sites such as Buzzup, Delicious and Digg.
Version: 5.4
Author: Denis de Bernardy & Mike Koepke
Author URI: http://www.getsemiologic.com
Text Domain: sem-bookmark-me
Domain Path: /lang
License: Dual licensed under the MIT and GPLv2 licenses
*/

/*
Terms of use
------------

This software is copyright Denis de Bernardy & Mike Koepke, and is distributed under the terms of the MIT and GPLv2 licenses.

Fam Fam Fam silk icons (email_go, printer, information) are copyright Mark James (http://www.famfamfam.com/lab/icons/silk/), and CC-By licensed:

http://creativecommons.org/licenses/by/2.5/

Other icons are copyright their respective holders.
**/

/**
 * bookmark_me
 *
 * @property int|string alt_option_name
 * @package Bookmark Me
 **/

class bookmark_me extends WP_Widget {

	/**
	 * Plugin instance.
	 *
	 * @see get_instance()
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * URL to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_url = '';

	/**
	 * Path to this plugin's directory.
	 *
	 * @type string
	 */
	public $plugin_path = '';

	/**
	 * Access this plugin’s working instance
	 *
	 * @wp-hook plugins_loaded
	 * @return  object of this class
	 */
	public static function get_instance()
	{
		NULL === self::$instance and self::$instance = new self;

		return self::$instance;
	}

	/**
	 * Loads translation file.
	 *
	 * Accessible to other classes to load different language files (admin and
	 * front-end for example).
	 *
	 * @wp-hook init
	 * @param   string $domain
	 * @return  void
	 */
	public function load_language( $domain )
	{
		load_plugin_textdomain(
			$domain,
			FALSE,
			$this->plugin_path . 'lang'
		);
	}

	/**
	 * Constructor.
	 *
	 *
	 */

	public function __construct() {
		$this->plugin_url    = plugins_url( '/', __FILE__ );
		$this->plugin_path   = plugin_dir_path( __FILE__ );
		$this->load_language( 'sem-bookmark-me' );

		add_action( 'plugins_loaded', array ( $this, 'init' ) );

   		$widget_ops = array(
   			'classname' => 'bookmark_me',
   			'description' => __('Bookmark links to social media sites such as Facebook, Google+ and Twitter', 'sem-bookmark-me'),
   			);

   		$this->WP_Widget('bookmark_me', __('Bookmark Me', 'sem-bookmark-me'), $widget_ops);
   	} # bookmark_me()

	/**
	 * init()
	 *
	 * @return void
	 **/

	function init() {
		if ( get_option('widget_bookmark_me') === false ) {
			foreach ( array(
				'bookmark_me' => 'upgrade',
				'bookmark_me_widgets' => 'upgrade',
				'sem_bookmark_me_params' => 'upgrade_2_x',
				) as $ops => $method ) {
				if ( get_option($ops) !== false ) {
					$this->alt_option_name = $ops;
					add_filter('option_' . $ops, array(get_class($this), $method));
					break;
				}
			}
		}

		add_action('widgets_init', array($this, 'widgets_init'));

	    if ( !is_admin() ) {
		    add_action('wp_enqueue_scripts', array($this, 'scripts'));
		    add_action('wp_enqueue_scripts', array($this, 'styles'), 0);
		    add_action('template_redirect', array($this, 'template_redirect'), 5);
	    }

		foreach ( array(
		    'switch_theme',
		    'update_option_active_plugins',
		    'update_option_sidebars_widgets',
		    'generate_rewrite_rules',

		    'flush_cache',
		    'after_db_upgrade',
		    ) as $hook ) {
			add_action($hook, array($this, 'flush_cache'));
		}

	    register_activation_hook(__FILE__, array($this, 'flush_cache'));
	    register_deactivation_hook(__FILE__, array($this, 'flush_cache'));
	} # init()
	
	
	/**
	 * template_redirect
	 *
	 * @return void
	 **/

	function template_redirect() {
		if ( isset($_GET['action']) && $_GET['action'] == 'print' ) {
			if ( has_filter('template_redirect', 'redirect_canonical') )
				redirect_canonical();

			if ( file_exists(STYLESHEETPATH . '/print.php') ) {
				include STYLESHEETPATH . '/print.php';
			} elseif ( TEMPLATEPATH != STYLESHEETPATH && file_exists(TEMPLATEPATH . '/print.php') ) {
				include TEMPLATEPATH . '/print.php';
			} else {
				include dirname(__FILE__) . '/print.php';
			}	
			die;
		}
	} # template_redirect()
	
	
	/**
	 * scripts()
	 *
	 * @return void
	 **/

	function scripts() {
		$scripts_js = ( WP_DEBUG ? 'scripts.min.js' : 'scripts.js' );
		wp_enqueue_script('bookmark_me', plugins_url( '/js/' . $scripts_js, __FILE__), array('jquery'), '20090906', true);
	} # scripts()
	
	
	/**
	 * styles()
	 *
	 * @return void
	 **/

	function styles() {
		$folder = plugin_dir_url(__FILE__);
		wp_enqueue_style('bookmark_me', $folder . 'css/styles.css', null, '20090903');
	} # styles()
	
	
	/**
	 * widgets_init()
	 *
	 * @return void
	 **/

	function widgets_init() {
		register_widget('bookmark_me');
	} # widgets_init()

	
	/**
	 * widget()
	 *
	 * @param array $args widget args
	 * @param array $instance widget options
	 * @return void
	 **/

	function widget($args, $instance) {
		if ( is_feed() || isset($_GET['action']) && $_GET['action'] == 'print' )
			return;

		extract($args, EXTR_SKIP);
		$instance = wp_parse_args($instance, bookmark_me::defaults());
		extract($instance, EXTR_SKIP);
		
		if ( is_admin() ) {
			echo $before_widget
				. ( $title
					? ( $before_title . $title . $after_title )
					: ''
					)
				. $after_widget;
			return;
		} elseif ( in_the_loop() ) {
			$page_title = get_the_title();
			$page_url = apply_filters('the_permalink', get_permalink());
            $page_excerpt = strip_tags(strip_shortcodes(get_the_excerpt()));
            $page_excerpt = (empty($page_excerpt)) ? substr(strip_tags(strip_shortcodes(get_the_content())), 0, 250)
                : $page_excerpt;
            $page_image = bookmark_me::get_first_image(get_the_ID(), get_the_content());
		} elseif ( is_singular() ) {
			global $wp_the_query;
			$post_id = $wp_the_query->get_queried_object_id();
            $post_obj = $wp_the_query->get_queried_object();
			$page_title = get_the_title($post_id);
			$page_url = apply_filters('the_permalink', get_permalink($post_id));
            $page_excerpt = strip_tags(strip_shortcodes($post_obj->post_excerpt));
            $page_excerpt = (empty($page_excerpt)) ? substr(strip_tags(strip_shortcodes($post_obj->post_content)), 0, 250)
                : $page_excerpt;
            $page_image = bookmark_me::get_first_image($post_id, $post_obj->post_content);
		} else {
			$page_title = get_option('blogname');
			$page_url = user_trailingslashit(get_option('home'));
            $page_excerpt = $page_title;
            $page_image = '';
		}
		
		$page_title = @html_entity_decode($page_title, ENT_COMPAT, get_option('blog_charset'));
		
		if ( !in_the_loop() ) {
			$print_action = false;
		} elseif ( strpos($page_url, '?') !== false ) {
			$print_action = '&action=print';
		} else {
			# An endpoint would have been better, but:
			# http://core.trac.wordpress.org/ticket/9477
			$print_action = '?action=print';
		}

		$use_caching = true;
		global $wp_version;
		if ( version_compare( $wp_version, '3.9', '>=' ) )
			if ( $this->is_preview() )
				$use_caching = false;

		if ( $use_caching ) {
			$o = wp_cache_get($widget_id, 'widget');
		}
		else
			$o = '';

		if ( empty( $o ) ) {
			# check if the widget has a class
			if ( strpos($before_widget, 'bookmark_me') === false ) {
				if ( preg_match("/^(<[^>]+>)/", $before_widget, $tag) ) {
					if ( preg_match("/\bclass\s*=\s*(\"|')(.*?)\\1/", $tag[0], $class) ) {
						$tag[1] = str_replace($class[2], $class[2] . ' bookmark_me', $tag[1]);
					} else {
						$tag[1] = str_replace('>', ' class="bookmark_me">', $tag[1]);
					}
					$before_widget = preg_replace("/^$tag[0]/", $tag[1], $before_widget);
				} else {
					$before_widget = '<div class="bookmark_me">' . $before_widget;
					$after_widget = $after_widget . '</div>' . "\n";
				}
			}
			
			$title = apply_filters('widget_title', $title);
			
			ob_start();
			
			echo $before_widget;
			
			if ( $title )
				echo $before_title . $title . $after_title;
			
			echo '<div class="bookmark_me_services' . ( !$print_action ? ' bookmark_me_narrow' : '' ) . '">' . "\n";
			
			foreach ( bookmark_me::get_main_services() as $service_id =>  $service ) {
				echo '<a href="' . esc_url($service['url'])  . '" class="' . $service_id . ' no_icon"'
					. ' title="' . esc_attr($service['name']) . '"'
					. ' rel="nofollow"  target="_blank">'
					. $service['name']
					. '</a>' . "\n";
			}

			echo '</div>' . "\n";

			if ( $print_action ) {
				echo '<div class="bookmark_me_actions">' . "\n";

				echo '<a href="mailto:?subject=%email_title%&amp;body=%email_url%"'
					. ' title="' . esc_attr(__('Email', 'sem-bookmark-me')) .  '" class="email_entry no_icon">'
					. __('Email', 'sem-bookmark-me')
					. '</a>' . "\n";

				echo '<a href="%print_url%"'
					. ' title="' . esc_attr(__('Print', 'sem-bookmark-me')) .  '" class="print_entry no_icon">'
					. __('Print', 'sem-bookmark-me')
					. '</a>' . "\n";
				
				echo '</div>' . "\n";
			}

			echo '<div class="bookmark_me_ruler"></div>' . "\n";
			
			echo '<div class="bookmark_me_extra" style="display: none;">' . "\n";

			foreach ( bookmark_me::get_extra_services() as $service_id =>  $service ) {
				echo '<a href="' . esc_url($service['url'])  . '" class="' . $service_id . ' no_icon"'
					. ' title="' . esc_attr($service['name']) . '"'
					. ( $service_id == 'help' && ( strpos(get_option('home'), 'semiologic.com') !== false )
						? ''
						: ' rel="nofollow" target="_blank"'
						)
					. '>'
					. $service['name']
					. '</a>' . "\n";
			}
			
			echo '<div class="bookmark_me_spacer"></div>' . "\n";
			
			echo '</div>' . "\n";
			
			echo $after_widget;

			$o = ob_get_clean();

			if ( $use_caching ) {
				wp_cache_add($widget_id, $o, 'widget');
			}
		}
		
		echo str_replace(
			array(
				'%enc_url%', '%enc_title%',
				'%email_url%', '%email_title%',
				'%print_url%', '%enc_excerpt%',
                '%enc_image%',
				),
			array(
				urlencode($page_url), urlencode($page_title),
				rawurlencode($page_url), rawurlencode($page_title),
				esc_url($page_url . $print_action), urlencode($page_excerpt),
                urlencode($page_image),
				),
			$o);
	} # widget()

    /**
     * get_first_image()
     *
     * @param $post_id
     * @param $post_content
     * @return array $services
     */

    function get_first_image($post_id, $post_content) {
        $args = array(
       		'numberposts' => 1,
       		'order'=> 'ASC',
       		'post_mime_type' => 'image',
       		'post_parent' => $post_id,
       		'post_status' => null,
       		'post_type' => 'attachment'
       	);

       	$attachments = get_children( $args );

       	// Check for image attachments in posts
       	if ($attachments){
       		foreach($attachments as $attachment){
       			return $attachment->guid;
       		}
       	}
        else {
       		// If no image attachements, then get the full post thumbnail
       		if(function_exists('has_post_thumbnail') && has_post_thumbnail($post_id)){
       			$imageId = get_post_thumbnail_id($post_id);
       			$imageUrl = wp_get_attachment_image_src($imageId, 'large');
       			return $imageUrl[0];
       		}
            else{
       			// Or else get the first image present in the post content
       			$output = preg_match_all( '/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $post_content, $matches );

       			if(!empty($matches[1])){
       				$firstImg = $matches [1] [0];
       				return $firstImg;
       			}

       		}
       	}
    }

	/**
	 * get_main_services()
	 *
	 * @return array $services
	 **/

	function get_main_services() {
		return array(
            'facebook' => array(
                'name' => __('Facebook', 'sem-bookmark-me'),
                 'url' => 'http://www.facebook.com/share.php?t=%enc_title%&u=%enc_url%'
                ),
            'googleplus' => array(
                'name' => __('Google+', 'sem-bookmark-me'),
                'url' => 'https://plus.google.com/share?url=%enc_url%',
                ),
			'twitter' => array(
		        'name' => __('Twitter', 'sem-bookmark-me'),
				'url' => 'http://twitter.com/timeline/home/?status=%enc_url%',
				),
            'pinterest' => array(
                'name' => __('Pinterest', 'sem-bookmark-me'),
                'url' => 'http://www.pinterest.com/pin/create/button/?url=%enc_url%&amp;media=%enc_image%&amp;description=%enc_excerpt%',
                ),
			);
	} # get_main_services()
	
	
	/**
	 * get_extra_services()
	 *
	 * @return array $services
	 **/

	function get_extra_services() {
		return array(
			'delicious' => array(
				'name' => __('Delicious', 'sem-bookmark-me'),
				'url' => 'http://del.icio.us/post?title=%enc_title%&url=%enc_url%',
				),
            'digg' => array(
                'name' => __('Digg', 'sem-bookmark-me'),
                'url' => 'http://digg.com/submit?phase=2&title=%enc_title%&url=%enc_url%',
                ),
			'fark' => array(
				'name' => __('Fark', 'sem-bookmark-me'),
				'url' => 'http://cgi.fark.com/cgi/farkit.pl?h=%enc_title%&u=%enc_url%',
				),
			'google' => array(
				'name' => __('Google', 'sem-bookmark-me'),
				'url' => 'http://www.google.com/bookmarks/mark?op=add&title=%enc_title%&bkmk=%enc_url%',
				),
            'instapaper' => array(
                'name' => __('Instapaper', 'sem-bookmark-me'),
                'url' => 'http://www.instapaper.com/hello2?url=%enc_url%&amp;title=%enc_title%',
                ),
			'linkedin' => array(
				'name' => __('LinkedIn', 'sem-bookmark-me'),
				'url' => 'http://www.linkedin.com/shareArticle?mini=true&summary=&source=&title=%enc_title%&url=%enc_url%',
				),
 /*          'msn' => array(
                'name' => __('MSN', 'sem-bookmark-me'),
                'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&mkt=en-us&top=1&title=%enc_title%&url=%enc_url%',
                ),  */
			'myspace' => array(
				'name' => __('MySpace', 'sem-bookmark-me'),
				'url' => 'http://www.myspace.com/Modules/PostTo/Pages/?l=3&t=t=%enc_title%&u=%enc_url%',
				),
			'newsvine' => array(
				'name' => __('Newsvine', 'sem-bookmark-me'),
				'url' => 'http://www.newsvine.com/_tools/seed&save?h=%enc_title%&u=%enc_url%',
				),
            'pocket' => array(
                 'name' => __('Pocket', 'sem-bookmark-me'),
                 'url' => 'https://getpocket.com/save?url=%enc_url%&title=%enc_title%',
                 ),
            'readability' => array(
                'name' => __('Readability', 'sem-bookmark-me'),
                'url' => 'http://www.readability.com/save?url=%enc_url%',
                ),
			'reddit' => array(
				'name' => __('Reddit', 'sem-bookmark-me'),
				'url' => 'http://reddit.com/submit?title=%enc_title%&url=%enc_url%',
				),
			'stumbleupon' => array(
				'name' => __('StumbleUpon', 'sem-bookmark-me'),
				'url' => 'http://www.stumbleupon.com/submit?title=%enc_title%&url=%enc_url%',
				),
            'tumblr' => array(
         		'name' => __('Tumblr', 'sem-bookmark-me'),
         		'url' => 'http://www.tumblr.com/share?v=3&u=%enc_url%&t=%enc_title%&s=%enc_excerpt%',
         		),
		    'yahoo' => array(
				'name' => __('Yahoo!', 'sem-bookmark-me'),
				'url' => 'http://bookmarks.yahoo.com/toolbar/savebm?opener=tb&t=%enc_title%&u=%enc_url%',
				),
/*			'help' => array(
				'name' => __('What\'s This?', 'sem-bookmark-me'),
				'url' => 'http://www.semiologic.com/resources/blogging/help-with-social-media-sites/',
				),
		    'buzzup' => array(
			 	'name' => __('Buzz Up', 'sem-bookmark-me'),
			 	'url' => 'http://buzz.yahoo.com/buzz?headline=%enc_title%&targetUrl=%enc_url%',
			 	),
		    'propeller' => array(
				'name' => __('Propeller', 'sem-bookmark-me'),
				'url' => 'http://www.propeller.com/submit/?T=%enc_title%&U=%enc_url%',
				),
			'diigo' => array(
				'name' => __('Diigo', 'sem-bookmark-me'),
				'url' => 'http://secure.diigo.com/post?title=%enc_title%&url=%enc_url%',
				),
		    'current' => array(
				'name' => __('Current', 'sem-bookmark-me'),
				'url' => 'http://current.com/clipper.htm?src=st&title=%enc_title%&url=%enc_url%',
				),
            'mixx' => array(
                'name' => __('Mixx', 'sem-bookmark-me'),
                'url' => 'http://www.mixx.com/submit?page_url=%enc_url%',
                ),
		    'tipd' => array(
				'name' => __('Tip\'d', 'sem-bookmark-me'),
				'url' => 'http://tipd.com/submit.php?url=%enc_url%',
				),
			'sphinn' => array(
				'name' => __('Sphinn', 'sem-bookmark-me'),
				'url' => 'http://sphinn.com/submit.php?title=%enc_title%&url=%enc_url%',
				),
			'slashdot' => array(
				'name' => __('Slashdot', 'sem-bookmark-me'),
				'url' => 'http://slashdot.org/bookmark.pl?title=%enc_title%&url=%enc_url%',
				),
*/
			);
	} # get_extra_services()
	
	
	/**
	 * update()
	 *
	 * @param array $new_instance
	 * @param array $old_instance
	 * @return array $instance
	 **/

	function update($new_instance, $old_instance) {
		$instance['title'] = strip_tags($new_instance['title']);
		
		bookmark_me::flush_cache();
		
		return $instance;
	} # update()
	
	
	/**
	 * form()
	 *
	 * @param array $instance
	 * @return void
	 **/

	function form($instance) {
		$instance = wp_parse_args($instance, bookmark_me::defaults());
		extract($instance, EXTR_SKIP);
		
		echo '<p>'
			. '<label>'
			. __('Title:', 'sem-bookmark-me')
			. '<br />'
			. '<input type="text" class="widefat"'
				. ' id="' . $this->get_field_id('title') . '"'
				. ' name="' . $this->get_field_name('title') . '"'
				. ' value="' . esc_attr($title) . '" />'
			. '</label>'
			. '</p>' . "\n";
	} # form()
	
	
	/**
	 * defaults()
	 *
	 * @return array $instance
	 **/

	function defaults() {
		return array(
			'title' => '',
			'widget_contexts' => array(
				'template_special.php' => false,
				)
			);
	} # defaults()


    /**
     * flush_cache()
     *
     * @param mixed $in
     * @return mixed
     */

	function flush_cache($in = null) {
		$o = get_option('widget_bookmark_me');
		unset($o['_multiwidget']);
		
		if ( !$o )
			return $in;
		
		foreach ( array_keys($o) as $id ) {
			wp_cache_delete("bookmark_me-$id", 'widget');
		}
		
		return $in;
	} # flush_cache()
	
	
	/**
	 * upgrade()
	 *
	 * @param array $ops
	 * @return array $ops
	 **/

	function upgrade($ops) {
		$widget_contexts = class_exists('widget_contexts')
			? get_option('widget_contexts')
			: false;

		foreach ( $ops as $k => $o ) {
			$ops[$k] = array(
				'title' => $o['title'],
				);
			if ( isset($widget_contexts['bookmark_me-' . $k]) ) {
				$ops[$k]['widget_contexts'] = $widget_contexts['bookmark_me-' . $k];
			}
		}
		
		return $ops;
	} # upgrade()
	
	
	/**
	 * upgrade_2_x()
	 *
	 * @param array $ops
	 * @return array
	 **/

	function upgrade_2_x($ops) {
		$ops = !empty($ops['title']) ? array('title' => $ops['title']) : array();
		
		if ( is_admin() ) {
			$sidebars_widgets = get_option('sidebars_widgets', array('array_version' => 3));
		} else {
			if ( !$GLOBALS['_wp_sidebars_widgets'] )
				$GLOBALS['_wp_sidebars_widgets'] = get_option('sidebars_widgets', array('array_version' => 3));
			$sidebars_widgets =& $GLOBALS['_wp_sidebars_widgets'];
		}

        $changed = false;
		foreach ( $sidebars_widgets as $sidebar => $widgets ) {
			if ( !is_array($widgets) )
				continue;
			$key = array_search('bookmark-me', $widgets);
			if ( $key !== false ) {
				$sidebars_widgets[$sidebar][$key] = 'bookmark_me';
				$changed = true;
				break;
			}
		}
		
		if ( $changed && is_admin() )
			update_option('sidebars_widgets', $sidebars_widgets);
		
		return $ops;
	} # upgrade_2_x()
} # bookmark_me


/**
 * the_bookmark_links()
 *
 * @param mixed $instance widget args (string title or array widget args)
 * @param array $args sidebar args
 * @return void
 **/

function the_bookmark_links($instance = null, $args = null) {
	if ( is_string($instance) )
		$instance = array('title' => $instance);
	
	$args = wp_parse_args($args, array(
		'before_widget' => '<div class="bookmark_me">' . "\n",
		'after_widget' => '</div>' . "\n",
		'before_title' => '<h2>',
		'after_title' => '</h2>' . "\n",
		));
	
	the_widget('bookmark_me', $instance, $args);
} # the_bookmark_links()

$bookmark_me = bookmark_me::get_instance();