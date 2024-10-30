<?php
/*
Plugin Name: BlogSpine Widget
Plugin URI: http://blogspine.com/tools/plugins/blogspine-for-wordpress/home/
Description: Display your blog blogrolls listed from your BlogSpine account.
Author: BlogSpine Staff
Version: 1.1.0
Author URI: http://www.blogspine.com/contact/
*/

define('BSW_VERSION', '1.1.0');
define('WP_VERSION', $wp_version);

add_action('widgets_init', 'blogspine_init');
add_action('admin_menu', 'blogspine_menu');

function blogspine_init(){
    if ( !function_exists('register_sidebar_widget') || !function_exists('register_widget_control'))  return;
    register_widget_control(array('BlogSpine', 'widgets'), 'blogspine_control', 330, 355);
    register_sidebar_widget(array('BlogSpine', 'widgets'), 'blogspine_widget');
}
function blogspine_widget($args) {

    extract($args);

    $options            = get_option('blogspine_widget');
    $bs_widget_name     = $options['widget_name'];
    $bs_rest_key        = $options['rest_key'];
    $bs_total_num       = (!$options['total_num'])?10:$options['total_num'];
    $bs_show_link       = $options['show_link'];
    $bs_is_random       = $options['is_random'];
    $bs_front_desc      = $options['front_desc'];
    
    if(!$bs_total_num || $bs_total_num<1) $bs_total_num = 8;
    if(!$bs_widget_name) $bs_widget_name = 'BlogSpine Blogrolls';
    
    $params = array();

    $params['total_num']         = $bs_total_num;
    $params['show_link']         = $bs_show_link;
    $params['is_random']         = $bs_is_random;

    $params['blog_key']          = $bs_rest_key;
    $params['widget_version']    = BSW_VERSION;
    $params['source']            = 'widget_wordpress';
    $params['widget_source_ver'] = WP_VERSION;
    $params['host']              = $_SERVER['HTTP_HOST'];
    $params['addr']              = $_SERVER['REMOTE_ADDR'];

    foreach ($params as $k => $v): $param .= $k.'='.$v.'&'; endforeach;
    
    $raw_result = BLogSpineRequest($param, 'blogspine.com', '/rest/blogLinks/');
    $blogrolls = unserialize($raw_result[1]);

    echo $before_widget . $before_title . $bs_widget_name . $after_title;

    echo '<ul>';
    if (!empty($blogrolls )) {
        if(!empty($bs_front_desc)){echo '<p><div style="width: 145px;margin-bottom:10px;"><small>'.$bs_front_desc.'</small></div></p>';}
        foreach ($blogrolls as $entry) {
            echo '<li><a target="_blank" href="http://blogspine.com/?x='.$entry['slug'].'" title="'.$entry['title'].'">'.$entry['title'].'</a></li>';
        }
        if(!empty($bs_show_link)){echo '<p><div style="width: 145px;margin-bottom:10px;"><small><a href="http://www.blogspine.com/" title="Blog Spine - A Blogroll link tracker"><img src="http://www.blogspine.com/Pub/img/bs.gif" alt="Blog Spine - A Blogroll link tracker" title="Blog Spine - A Blogroll link tracker" border="0" width="80" height="15"></a></small></div></p>';}
    } else echo "<li>No Links</li>";
    echo '</ul>';
    echo $after_widget;
}

function blogspine_control(){
    echo '<a href="'. get_settings('siteurl') . '/wp-admin/plugins.php?page=blogspine-options'.'" target="_blank">Configuration Page</a>';
}
function blogspine_control_menu() {
    $options = get_option('blogspine_widget');
    if( !is_array($options) )
        $options = array('title'=>'Blogrolls', 'total_num'=> 10, 'show_link'=> 1, 'front_desc'=>'','is_random'=>0);
    if( $_POST['blogspine-submit']){
        $options['rest_key']      = trim(strip_tags(stripslashes($_POST['blogspine-rest_key'])));
        $options['widget_name']   = trim(strip_tags(stripslashes($_POST['blogspine-widget_name'])));
        $options['front_desc']    = trim(strip_tags(stripslashes($_POST['blogspine-front_desc'])));
        $options['is_random']     = (($_POST['blogspine-is_random']=='on')?1:0);
        $options['total_num']     = trim(strip_tags(stripslashes($_POST['blogspine-total_num'])));
        $options['show_link']     = (($_POST['blogspine-show_link']=='on')?1:0);
        update_option('blogspine_widget', $options);
    }
    $bs_rest_key      = htmlspecialchars($options['rest_key'], ENT_QUOTES);
    $bs_widget_name   = htmlspecialchars($options['widget_name'], ENT_QUOTES);
    $bs_front_desc    = htmlspecialchars($options['front_desc'], ENT_QUOTES);
    $bs_is_random     = $options['is_random'] ? 'checked="checked"' : '';
    $bs_total_num     = intval($options['total_num']);
    $bs_show_link     = $options['show_link'] ? 'checked="checked"' : '';
    ob_start();
    if (!empty($_POST['save'])) { ?>
    <div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
    <?php } ?>
    <form action="" method="post" id="blogspine-conf" name="blogspine">
        <div class="wrap">
            <h2>BlogSpine Configuration</h2>
    <?php
    echo '<p>
            <label for="blogspine-title">' . __('BlogSpine Blog Key:') . ' 
            <input id="blogspine-rest_key" name="blogspine-rest_key" type="text" value="'.$bs_rest_key.'" />
            </label>
         </p>
         <p>
            <label for="blogspine-title">' . __('Widget Title:') . ' 
            <input id="blogspine-widget_name" name="blogspine-widget_name" type="text" value="'.$bs_widget_name.'" />
            </label>
         </p>
         <p>
            <label for="blogspine-explanation">' . __('Short Description:') . ' 
            <input id="blogspine-front_desc" name="blogspine-front_desc" type="text" value="'.$bs_front_desc.'" />
            </label>
         </p>
         <p>
            <label for="blogspine-explanation">' . __('Show in random?:') . ' 
            <input class="checkbox" type="checkbox" '.$bs_is_random.' id="blogspine-is_random" name="blogspine-is_random" />
            </label>
         </p>
         <p>
            <label for="blogspine-show">' . __('Number of Links to Show:') . ' 
            <input style="width: 35px;" id="blogspine-total_num" name="blogspine-total_num" type="text" value="'.$bs_total_num.'" />
            </label>
         </p>
         <p>
            <label for="blogspine-show">' . __('Show footer?:') . ' 
            <input class="checkbox" type="checkbox" '.$bs_show_link.' id="blogspine-show_link" name="blogspine-show_link" />
            </label>
         </p>
         <p><small>Edit your blogrolls from your blogspine account.
         <br />Visit us: <a href="http://blogspine.com/" target="_blank">BlogSpine.com</a></small></p>
         <input type="hidden" id="blogspine-submit" name="blogspine-submit" value="1" />
         <br /><br />
         </p>
         <input type="hidden" id="blogspine-submit" name="blogspine-submit" value="1" />';
         ?>
        <p class="submit">
            <input type="submit" name="save" value="<?php echo __('Save Changes', 'blogspine');?>" />
        </p>
    </div>
</form>
<?php
ob_end_flush();
}

function BLogSpineRequest($request, $host, $path) {
    global $wp_version;
    $http_request  = "POST $path HTTP/1.0\r\n";
    $http_request .= "Host: $host\r\n";
    $http_request .= "Content-Type: application/x-www-form-urlencoded; charset=" . get_option('blog_charset') . "\r\n";
    $http_request .= "Content-Length: " . strlen($request) . "\r\n";
    $http_request .= "User-Agent: BlogSpine Wordpress Widget\r\n";
    $http_request .= "\r\n";
    $http_request .= $request;
    $http_request .= "\r\n\r\n";
    $response = '';
    if( false != ( $fs = @fsockopen($host, 80, $errno, $errstr, 10) ) ) {
        fwrite($fs, $http_request);
        while ( !feof($fs) )
            $response .= fgets($fs, 2048);
        fclose($fs);
        $response = explode("\r\n\r\n", $response, 2);
    }
    return $response;
}
function blogspine_menu(){
    add_submenu_page('plugins.php', __('BlogSpine', 'blogspine'), __('BlogSpine', 'blogspine'), 'manage_options', 'blogspine-options', 'blogspine_control_menu');
}

