<?php

/**
 * Plugin Name: app-media
 * Description: a specific plugin custom created for the redpluss project to add banner images in the mobile app of the web
 * Author Name: Priyam Sengupta | J bulls Infotech 2024
 * version: 1.0.0
 */

if(!defined('ABSPATH')){
    header("location: /");
    die();
}


function app_media_activation(){
    global $wpdb, $table_prefix;

    $wp_media = $table_prefix . 'media';
    $q = "CREATE TABLE `$wp_media` (`id` INT NOT NULL AUTO_INCREMENT , `post_id` INT NOT NULL , `post_title` VARCHAR(50) NOT NULL , `post_link` VARCHAR(500) NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;";
    $wpdb->query($q);
}
register_activation_hook(__FILE__, 'app_media_activation');

function app_media_deactivation(){
    global $wpdb, $table_prefix;

    $wp_media = $table_prefix . 'media';

    $q ="DROP TABLE `$wp_media`";
    $wpdb->query($q);


    function remove_app_media_meta_box(){
        remove_meta_box('banner_tag_meta_box', 'attachment', 'side');
    }
    add_action( 'admin_head' , 'remove_app_media_meta_box' );

    delete_post_meta_by_key('is_banner');


}
register_deactivation_hook(__FILE__, 'app_media_deactivation');


function add_banner_tag() {
    add_meta_box(
        'banner_tag_meta_box',
        'Is App Banner',
        'display_app_banner_checkbox',
        'attachment',
        'side',
        'high'
    );
}    
add_action('add_meta_boxes', 'add_banner_tag');


function display_app_banner_checkbox(){




    global $post;
    $post_id = $post->ID;
    

    $selected_answer = get_post_meta($post->ID, 'is_banner', true);
     ?>
     <form>
         <label for="is_banner"><?php _e('Select answer', 'is_banner_meta_box'); ?></label>
         <select name="is_banner" id="is_banner">
             <option value="" <?php selected($selected_answer, ); ?>>Choose A Option</option>
             <option value="yes" <?php selected($selected_answer, 'yes'); ?>>Yes</option>
             <option value="no" <?php selected($selected_answer, 'no'); ?>>No</option>
         </select>
     </form>
     <?php
}

function save_media_meta_data($post_id){
    update_post_meta($post_id, 'is_banner', $_POST['is_banner']);
}
add_action('edit_attachment','save_media_meta_data');


function update_media_table(){
    global $wpdb, $table_prefix, $post;
    $wp_media = $table_prefix . 'media';

    $q = "SELECT post_id FROM wp_postmeta WHERE meta_key = 'is_banner' AND meta_value = 'yes'";
    $media_ids = $wpdb->get_results($q);

    foreach ($media_ids as $media) {
        $singular_id = $media->post_id;
        $post_title = get_the_title($singular_id);
        $post_link = get_the_guid($singular_id);
        $sql = "SELECT post_id FROM $wp_media WHERE post_id = $singular_id";

        $result = $wpdb->get_results("$sql");
        

        if (!empty($result)){
            error_log("id already exsists");
        } elseif($result=null){
            error_log("null values");
        } else {
            $wpdb->insert($wp_media, array(
                'post_id' => $singular_id,
                'post_title' => $post_title,
                'post_link' => $post_link
            ));
        }
    }



    
}

add_action('init', 'update_media_table');

function delete_media_table_row(){
    global $wpdb, $table_prefix, $post;
    $wp_media = $table_prefix . 'media';

    $del = "SELECT post_id FROM wp_postmeta WHERE meta_key = 'is_banner' AND meta_value = ''";
    $emt_media_ids = $wpdb->get_results($del);

    foreach($emt_media_ids as $to_del){
        $singular_del_id = $to_del->post_id;
        

        $del_sql = "SELECT id FROM $wp_media WHERE post_id = $singular_del_id";
        $del_result = $wpdb->get_results($del_sql);
        


        delete_post_meta($singular_del_id, 'is_banner');
        if(!empty($del_result)){
            $del_id = $del_result[0]->id;
            $wpdb->delete($wp_media, array(
                'id' => $del_id,
            ));
        }
    }
}
add_action('init', 'delete_media_table_row');








add_action('rest_api_init', function () {
    register_rest_route('custom/v1', '/banner-url', [
        'methods' => 'GET',
        'callback' => 'handle_media_url',
        'permission_callback' => '__return_true',
    ]);
});

function handle_media_url($request){
    $parameters = $request->get_json_params();
    
    global $post, $wpdb, $table_prefix;
    $wp_media = $table_prefix . 'media';

        $q = "SELECT post_link FROM $wp_media";
        $res = $wpdb->get_results($q);

        $links = array();
        if (!empty($res)) {
            foreach ($res as $row) {
                $links[] = $row->post_link;
            }
        }

    return new WP_REST_Response([
        'message' => 'API is working',
        'banner_urls' => $links
    ], 200);

}