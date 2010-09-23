<?php
/*
	Plugin Name: flickrImg
	Plugin URI: http://http://www.murraypicton.com/flickrimg
	Description: Put a random flickr image for a specified user as the main image for each post
	Version: 1.0
	Author: Murray Picton
	Author URI: http://www.murraypicton.com
	License: GPL2
	Copyright 2010  MURRAY PICTON  (email : info@murraypicton.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
add_filter('the_content', 'flickrImg');
add_action('admin_menu', 'flickrImg_adminMenu');


/* Plugin Functions */
function flickrImg($content) {
	if(is_page())
		return $content;
	else
		return flickrImg_getImage(md5($content)).$content;
}
function flickrImg_getImage($hash) {
	$cache = get_option("flickrImg");
	$img = $cache[$hash];
	if(empty($img)) {
		if(!$imgs = flickrImg_getAllImages()) return "";
		$img = $imgs[array_rand($imgs)];
		$cache[$hash] = $img;
		update_option("flickrImg", $cache);
	}
	return "<div class='flickrImg' style='text-align: center;'><img src='$img' alt='Post Image' /></div>\r\n";
}
function flickrImg_getAllImages() {
	$images 	= array();
	if(!$user_id = flickrImg_getUserId()) return false;
	$request 	= flickrImg_getServiceURL()."?method=flickr.people.getPublicPhotos&api_key=".flickrImg_getAPIKey()."&user_id=".$user_id."&per_page=500";
	//while(true) {
		$page = 1;
		$xmlObj = simplexml_load_file($request."&page=$page");
		if(isset($xmlObj->photos->photo[0]) ) {
			foreach($xmlObj->photos->photo as $photo) {
				$images[] = "http://farm".$photo->attributes()->farm.".static.flickr.com/".$photo->attributes()->server."/".$photo->attributes()->id."_".$photo->attributes()->secret.".jpg";
			}
			$page++;
		}/* else {
			break;
		}
	}*/
	return $images;
}
function flickrImg_getUserId() {
	$settings = get_option('flickrImgSettings');
	$username 	= $settings['username'];
	if(empty($username)) return false;
	$request 	= flickrImg_getServiceURL()."?method=flickr.people.findByUsername&api_key=".flickrImg_getAPIKey()."&username=$username";
	
	$xmlObj = simplexml_load_file($request);
	return $xmlObj->user->attributes()->nsid;
}
function flickrImg_getAPIKey() {
	return "a0924fe5ce9fca584a25f7195defa19e";
}
function flickrImg_getServiceURL() {
	return "http://api.flickr.com/services/rest/";
}

/* Settings functions */
function flickrImg_adminMenu() {
	add_options_page('FlickrImg', 'FlickrImg', 8, basename(__FILE__), 'flickrImg_adminPage');
}
function flickrImg_adminPage() {
	if ( isset($_POST['flickrImg_submit'] ) ) {
		flickrImg_adminPageSubmit();
	}
	$settings = get_option('flickrImgSettings');
	
	echo '<div class="wrap">';
	echo '<h2>' . __('flickrImg Settings') . '</h2>';
	echo '<form method="post" action="">';
	echo '<fieldset>';
	echo '<p><label for="flickrImg_username">'. __("Flickr Username") .'</label>: <input type="text" id="flickrImg_username" name="flickrImg_username" value="'. $settings['username'] .'" /></p>';
	echo '<p><label for="flickrImg_reset">'. __("Reset all images?") .'</label>: <input type="checkbox" id="flickrImg_reset" name="flickrImg_reset" value="1" /></p>';
	echo '<p class="submit">';
	echo '<input type="submit" name="flickrImg_submit" value="Save settings" />';
	echo '</p>';
	echo '</fieldset>';
	echo '</form>';
	echo '</div>';
}
function flickrImg_adminPageSubmit() {
	$settings = array();
	$settings['username'] = $_POST['flickrImg_username'];
	
	$reset = $_POST['flickrImg_reset'];
	
	update_option('flickrImgSettings', $settings);
	
	if($reset == 1) {
		update_option('flickrImg', "");
	}
	
	echo '<div class="updated fade"><p><strong>'. __('Settings saved.') .'</strong></p></div>';
}
?>