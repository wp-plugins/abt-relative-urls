<?php
/*
Plugin Name: Relative Urls (ABT)
Plugin URI: http://atlanticbt.com/blog/wordpress-relative-urls-plugin
Description: By default, WP inserts absolute urls (including protocol and domain) into post content.  Replace all self-referencing links with relative paths "/" instead.  Works when inserting images into posts, and on the actual save_post action.
Version: 0.3.1
Author: atlanticbt, zaus
Author URI: http://atlanticbt.com
License: GPLv2 or later
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
class ABT_Relative_Urls {
	
	/**
	 * Store prefix once, since the domain/protocol won't change
	 */
	private $absolute_prefix;
	
	/**
	 * Put anything here you want run immediately, like attaching hooks
	 */
	public function __construct() {
		
		add_filter('image_send_to_editor',array(&$this, 'image_to_relative'),5,8);
			
			/* failed attempts at relative links */
			/*
			add_filter('tiny_mce_before_init', array(&$this, 'tiny_mce_before_init' ), 10, 1);
			
			add_filter('post_link', array(&$this, 'relative_link_rewrite'), 10, 3);
			if( is_admin() ) {
				#add_action('wp_ajax_handle_frontend_ajax', 'handle_frontend_ajax_callback');
				add_action('wp_ajax_wp_link_ajax', array(&$this, 'admin_only_ajax_callback'), 5);
				add_action('wp_ajax_wp-link-ajax', array(&$this, 'admin_only_ajax_callback'), 5);
				// Load "admin-only" scripts here
			}
			*/
			
		
		// scan editor content to replace self-referencing links
		global $pagenow;
		if( is_admin() && 'post.php' == $pagenow ) {
			add_action('save_post', array(&$this, 'save_post_replace_absurls'), 18, 2);
		}
		
		// allow hardcoding, just in case...
		add_shortcode('abt_absolute_url', array(&$this, 'shortcode_abspath'));
		
		// do it once
		///NOTE: home_url is the safer value, as this is what's used by WP when creating links
		$this->absolute_prefix = home_url();	//'http' . (isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == "on" ? 's' : '') . '://' . $_SERVER['HTTP_HOST'];

	}//--	fn	__construct
	
	
	/**
	 * Get the protocol (http or https) and the HTTP_HOST variables, with trailing slash (or not)
	 * @param string $trailing_slash {default /} whether or not to return a trailing slash
	 */
	private function get_domain_and_protocol($trailing_slash = '/'){
		return $this->absolute_prefix . $trailing_slash;
	}//--	fn	get_domain_and_protocol
	

	/**
	 * Get the search array for the absolute path prefix
	 * @param string $absolute_path_prefix the url to remove
	 *
	 * @return array filtered (hook) array of items to search for
	 */
	private function get_search_for($absolute_path_prefix){
		return apply_filters('abt_relative_urls_get_search_for',
			array(
				'src="' . $absolute_path_prefix
				, 'href="' . $absolute_path_prefix
				, esc_attr( $absolute_path_prefix )
			)
		);
	}//--	fn	get_search_for
	
	/**
	 * Get the replace array for the relative path prefix
	 * @param string $relative_path_prefix the url to replace with
	 *
	 * @return array filtered (hook) array of items to replace with
	 */
	private function get_replace_with($relative_path_prefix){
		return apply_filters('abt_relative_urls_get_replace_with',
			array(
				'src="' . $relative_path_prefix
				, 'href="' . $relative_path_prefix
				, esc_attr( $relative_path_prefix )
			)
		);
	}//--	fn	get_replace_with

	/**
	 * Remove the absolute url from post content (i.e. first in src or href, then in attributes)
	 * @param string $content the content to scrub
	 * 
	 * @return string the scrubbed content
	 */
	private function _strip_content_absurls($content){
		// get current domain
		$absolute_path_prefix = $this->get_domain_and_protocol();
		// replace absolute path with 'relative path'
		$replace_with_prefix = '/';
	
		// find and replace the image source only (ignore other attributes)
		// also look for esc_attr'd versions (see \wp-admin\includes\media.php)
		$content = str_replace(
			// search
			$this->get_search_for($absolute_path_prefix)
			,
			// replace
			$this->get_replace_with($replace_with_prefix)
			,
			// in
			$content
		);
		
		return $content;
	}//--	fn	_strip_absurls
	
		
	/**
	 * @package Relative Image URLs
	 * @author BlueLayerMedia
	 * @version 1.0.0
	Plugin Name: Relative Image URLs
	Plugin URI: http://www.bluelayermedia.com/
	Description: Replaces absolute URLs with Relative URLs for image paths in posts
	Author: BlueLayerMedia
	Version: 1.0.0
	Author URI: http://www.bluelayermedia.com/
	*/
	function image_to_relative($html, $id, $caption, $title, $align, $url, $size, $alt) {
		return $this->_strip_content_absurls($html);
	}//--	fn	image_to_relative
	
	
	/**
	 * Helper - perform authorization check for post save
	 * @param int $post_ID the post id being saved
	 * @param obj $post the post object being saved
	 * 
	 * @return true on success, post_id otherwise...
	 */
	private function _save_post_authcheck($post_ID, &$post) {
		// seriously, need to figure out how to hook to default nonce...
		///TODO: is this safe?
		/* */
		$posted_type = $_POST['post_type'];
		$posted_id = $_POST['post_ID'];
		$nonce_name = 'update-' . /*$post->post_type*/ $posted_type . '_' . /*$post_ID*/ $posted_id;
		if ( empty($_POST) || ! check_admin_referer($nonce_name) ){
			return $post_ID;
		}
		/* */
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_ID;
		
		
	
		// Check permissions
		///TODO: does this fail for custom post types?
		if ( 'page' == $_POST['post_type'] ):
			if ( !current_user_can( 'edit_page', $post_ID ) )
				return $post_ID;
		else
			if ( !current_user_can( 'edit_post', $post_ID ) )
				return $post_ID;
		endif;
		
		return true;
	}//--	fn	_save_post_authcheck
	
	
	/**
	 * HELPER - Step through possible array to strip URLs
	 * actually strips urls from multilevel array
	 * @param mixed $meta the recursively passed value
	 */
	private function _nested_strip(&$meta){
		if( is_string($meta) )
			$meta = $this->_strip_content_absurls($meta);
		elseif( is_array($meta) )
			array_walk_recursive($meta, array(&$this, '_nested_strip'));
	}//--	fn	_nested_strip
	
	/**
	 * Step through possible array to strip URLs
	 * NOTE: this will internally unserialize values, then recursively strip URLs
	 * @param mixed $meta the recursively passed value
	 */
	private function nested_strip(&$meta){
		if( is_array($meta) ) {
			foreach( $meta as &$value ) {
				$this->nested_strip($value);
				
			} 
			
			#array_walk( $meta, array(&$this, 'nested_unserialize'));
		}
		// last chance
		$meta = maybe_unserialize($meta);
		
		// now strip urls; only happens on strings (since we're recursing)
		$this->_nested_strip($meta);
	}//--	fn	nested_strip
	
	/**
	 * HOOK Replace absolute urls with current domain with relative url path
	 * In post META
	 * @param int $post_ID the post id being saved
	 * @param obj $post the post object being saved
	 * @param bool $authcheck {default: true} perform authcheck if true - used for internal calls
	 */
	function save_meta_replace_absurls($post_ID, $post, $authcheck = true) {
			
			if( $authcheck && true !== $this->_save_post_authcheck($post_ID, $post) ) return $post_ID;
			
			// OK, WE'RE AUTHENTICATED !!!
			
			/*
			 * The idea is that, because we don't really know what's being submitted
			 * we'll just check everything it already has against what was provided
			 * NOTE: can't necessarily check against POST, because some metadata may
			 * have a different meta key than post...sheesh
			 */
			
			$meta = get_post_meta($post_ID, false);
			
			#_log('original', $meta);
			
			// remove specified fields from consideration
			$meta = apply_filters('abt_relative_urls_exclude_meta', $meta, $post_ID);

			// scan meta for urls
			#array_walk_recursive($meta, 'maybe_unserialize');
			$this->nested_strip($meta);
			
			// now...loop to save meta
			foreach( $meta as $key => $value ){
				// only care about arrays
				// NOTE: because we retrieved the whole array before, each individual value
				// 	needs to be "turned into a single" value (as though we did get_post_meta(id, key, TRUE))
				if( is_array( $value[0] )) update_post_meta($post_ID, $key, $value[0]);
			}
			
	}//--	fn	save_meta_replace_absurls
	
	/**
	 * HOOK Replace absolute urls with current domain with relative url path
	 * In post content
	 */
	function save_post_replace_absurls($post_ID, $post) {
			
			if( true !== $this->_save_post_authcheck($post_ID, $post) ) return $post_ID;
			
			// OK, WE'RE AUTHENTICATED !!!
			
			///TODO: meta fields here?  or as separate hook...
			$this->save_meta_replace_absurls($post_ID, $post, false); // do the meta replacements; skip internal authcheck since we did it above
			
			// get content string
			#$content = $post->post_content;//stripslashes_deep( $_POST['content'] );
			
			// replace all instances of current host from content
			$content = $this->_strip_content_absurls($post->post_content);
			
			// replace all instances of current host from excerpt
			$excerpt = $this->_strip_content_absurls($post->post_excerpt);
			
			// update content
			global $wpdb;
			$data = array(
				'post_content' => $content
				, 'post_excerpt' => $excerpt
			);
			
			if ( false === $wpdb->update( $wpdb->posts, $data, array( 'ID' => $post_ID ) ) ) {
				if ( $wp_error )
					return new WP_Error('db_update_error', __('Could not update post in the database after correcting absolute Urls'), $wpdb->last_error);
				else
					return 0;
			}
			
			#_log(__FUNCTION__, 'updated absolute urls');
			
	}//--	fn	save_post_replace_absurls
	
	
	/**
	 * Shortcode handler - return the absolute path untouched by this plugin
	 * @param string $trailing the trailing characters {default: none}
	 */
	function shortcode_abspath($atts = array()) {
		extract(shortcode_atts(array('trailing'=>''), (array)$atts));
		
		return $this->get_domain_and_protocol( $trailing );
	}//--	fn	shortcode_abspath
	
	
	
	
	#region ------------------- FAILED ATTEMPTS -------------------------
	
	/**
	 * Hook - alter default WP rich-text editor configuration to allow relative URLs
	 * @see \wp-admin\includes\post.php
	 * @seealso http://core.trac.wordpress.org/ticket/6737#comment:7
	 * 
	 * @param array $initArray config settings for tinymce
	 * @return array the initArray config settings
	 */
	public function tiny_mce_before_init($initArray){
		$initArray['relative_urls'] = true;
		$initArray['document_base_url'] = get_bloginfo('siteurl');
		
		return $initArray;
	} 
	
	
	/**
	 * Replace base path in ajax'd link requests ?
	 * @deprecated
	 */
	function relative_link_rewrite($permalink, $post, $leavename){
		_log(__FUNCTION__, $permalink, isset($_GET['action']) ? $_GET['action'] : 'no action' );
		// check if admin ajax and request is for wp-link-ajax
		if( !is_admin() || ! isset($_GET['action']) ||  'wp-link-ajax' != $_GET['action'] ):
			return $permalink;
		endif;
		
		_log(__FUNCTION__, array('permalink'=>$permalink, 'post'=>$post, 'leavename'=>$leavename ));
		
		// replace current domain in permalink
		$permalink = str_replace( $this->get_domain_and_protocol(), '/', $permalink);
		_log($permalink);
		
		return $permalink;
	}//--	fn	relative_link_rewrite
	
	#endregion ------------------- FAILED ATTEMPTS -------------------------
	
}///---	class	ABT_Relative_Urls


// run wrapper
new ABT_Relative_Urls();
