<?php
/**
 * Plugin Name: WP VideoScribe
 * Plugin URI: https://github.com/your-username/wp-videoscribe
 * Description: Generate blog post summaries and title suggestions from YouTube videos using AI, then create WordPress post drafts automatically.
 * Version: 1.0.0
 * Author: Binjomin Szanto-Varnagy
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-videoscribe
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WP_VIDEOSCRIBE_VERSION', '1.0.0');
define('WP_VIDEOSCRIBE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_VIDEOSCRIBE_PLUGIN_URL', plugin_dir_url(__FILE__));

// Include required files
require_once WP_VIDEOSCRIBE_PLUGIN_DIR . 'includes/transcript-extractor.php';

class WPVideoScribe {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_process_youtube_video', array($this, 'process_youtube_video'));
        add_action('wp_ajax_get_recent_videoscribe_posts', array($this, 'get_recent_videoscribe_posts'));
        add_action('wp_ajax_test_api_configuration', array($this, 'test_api_configuration'));
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function init() {
        load_plugin_textdomain('wp-videoscribe', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function activate() {
        // Only create OpenAI API key option
        add_option('wp_videoscribe_openai_api_key', '');
    }
    
    public function deactivate() {
        // Clean up if needed
    }
    
    public function add_admin_menu() {
        add_menu_page(
            __('VideoScribe', 'wp-videoscribe'),
            __('VideoScribe', 'wp-videoscribe'),
            'manage_options',
            'wp-videoscribe',
            array($this, 'admin_page'),
            'dashicons-video-alt3',
            30
        );
        
        add_submenu_page(
            'wp-videoscribe',
            __('Settings', 'wp-videoscribe'),
            __('Settings', 'wp-videoscribe'),
            'manage_options',
            'wp-videoscribe-settings',
            array($this, 'settings_page')
        );
    }
    
    public function enqueue_admin_scripts($hook) {
        if (strpos($hook, 'wp-videoscribe') !== false) {
            wp_enqueue_style('wp-videoscribe-admin', WP_VIDEOSCRIBE_PLUGIN_URL . 'assets/admin.css', array(), WP_VIDEOSCRIBE_VERSION);
            wp_enqueue_script('wp-videoscribe-admin', WP_VIDEOSCRIBE_PLUGIN_URL . 'assets/admin.js', array('jquery'), WP_VIDEOSCRIBE_VERSION, true);
            wp_localize_script('wp-videoscribe-admin', 'wpVideoScribe', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wp_videoscribe_nonce'),
                'strings' => array(
                    'processing' => __('Processing video...', 'wp-videoscribe'),
                    'error' => __('An error occurred. Please try again.', 'wp-videoscribe'),
                    'success' => __('Post draft created successfully!', 'wp-videoscribe')
                )
            ));
        }
    }
    
    public function admin_page() {
        include WP_VIDEOSCRIBE_PLUGIN_DIR . 'templates/admin-page.php';
    }
    
    public function settings_page() {
        if (isset($_POST['submit'])) {
            check_admin_referer('wp_videoscribe_settings');
            update_option('wp_videoscribe_openai_api_key', sanitize_text_field($_POST['openai_api_key']));
            echo '<div class="notice notice-success"><p>' . __('Settings saved!', 'wp-videoscribe') . '</p></div>';
        }

        include WP_VIDEOSCRIBE_PLUGIN_DIR . 'templates/settings-page.php';
    }
    
    public function process_youtube_video() {
        check_ajax_referer('wp_videoscribe_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-videoscribe'));
        }
        
        $youtube_url = sanitize_url($_POST['youtube_url']);
        $video_id = $this->extract_video_id($youtube_url);
        
        if (!$video_id) {
            wp_send_json_error(__('Invalid YouTube URL', 'wp-videoscribe'));
        }
        
        try {
            // Get video details and transcript
            $video_data = $this->get_video_data($video_id);
            
            if (!$video_data) {
                wp_send_json_error(__('Could not fetch video data', 'wp-videoscribe'));
            }
            
            // Generate AI content
            $ai_content = $this->generate_ai_content($video_data['transcript'], $video_data['title']);
            
            // Create WordPress post draft
            $post_id = $this->create_post_draft($video_data, $ai_content);
            
            if ($post_id) {
                wp_send_json_success(array(
                    'message' => __('Post draft created successfully!', 'wp-videoscribe'),
                    'post_id' => $post_id,
                    'edit_url' => get_edit_post_link($post_id)
                ));
            } else {
                wp_send_json_error(__('Failed to create post draft', 'wp-videoscribe'));
            }
            
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }
    
    private function extract_video_id($url) {
        preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $url, $matches);
        return isset($matches[1]) ? $matches[1] : false;
    }
    
    private function get_video_data($video_id) {
        $api_key = get_option('wp_videoscribe_youtube_api_key'); // Add this to your settings
        $video_url = "https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics&id={$video_id}&key={$api_key}";
        $video_response = wp_remote_get($video_url);

        if (is_wp_error($video_response)) {
            throw new Exception(__('Failed to fetch video details', 'wp-videoscribe'));
        }

        $video_data = json_decode(wp_remote_retrieve_body($video_response), true);

        if (empty($video_data['items'])) {
            throw new Exception(__('Video not found', 'wp-videoscribe'));
        }

        $video_info = $video_data['items'][0];

        $transcript = $this->get_video_transcript($video_id);

        return array(
            'id' => $video_id,
            'title' => $video_info['snippet']['title'],
            'description' => $video_info['snippet']['description'],
            'thumbnail' => $video_info['snippet']['thumbnails']['maxres']['url'] ?? $video_info['snippet']['thumbnails']['high']['url'],
            'channel' => $video_info['snippet']['channelTitle'],
            'published_at' => $video_info['snippet']['publishedAt'],
            'transcript' => $transcript
        );
    }

    private function get_video_transcript($video_id) {
        // No need for YouTube API key
        $extractor = new WP_VideoScribe_Transcript_Extractor(null);

        // Optionally remove has_captions check if your backend always returns transcript or error
        $transcript = $extractor->extract_transcript($video_id);

        if (!$transcript) {
            throw new Exception(__('Failed to extract video transcript', 'wp-videoscribe'));
        }

        return $extractor->clean_transcript($transcript);
    }
    
    private function generate_ai_content($transcript, $video_title) {
        $openai_api_key = get_option('wp_videoscribe_openai_api_key');
        
        if (empty($openai_api_key)) {
            throw new Exception(__('OpenAI API key not configured', 'wp-videoscribe'));
        }
        
        // Truncate transcript if it's too long (rough estimate: 1 token ≈ 4 characters)
        $max_transcript_length = 12000; // ~3000 tokens for transcript
        if (strlen($transcript) > $max_transcript_length) {
            $transcript = substr($transcript, 0, $max_transcript_length) . '...';
        }
        
        $prompt = <<<EOT
        Az alábbi szöveg egy tórai tanításomról készült gépi átirat. Készíts belőle egy részletes, jól formázott dokumentumot (minimum 1000 szó), amely átfogóan és világosan, összefüggő prózában írja le a tanítás tartalmát. Legyen benne bevezetés, fő gondolatmenet és zárás. Minden érdemi forrásidézetet (pl. Tóra, Talmud, Rambam, Midrás) pontosan nevezz meg, és helyezd kontextusba. Ne adj hozzá semmilyen új információt, csak a szövegben elhangzott gondolatokat rendszerezd és fogalmazd meg világosan.

        Transzkript: {$transcript}
        EOT;

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $openai_api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => 'gpt-4-1106-preview', // Legfrissebb stabil GPT-4 Turbo modell
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => 'Te egy profi szerkesztő vagy, aki hosszú, jól strukturált, kifejező és stílusos dokumentumokat készít tanítások alapján. Figyelj a tagolásra, forrásidézetekre, és soha ne rövidítsd le a tartalmat, ha nem muszáj.'
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.4,
                'max_tokens' => 4096 // Megemelve, hogy beleférjen egy hosszú dokumentum is
            )),
            'timeout' => 120
        ));

        
        if (is_wp_error($response)) {
            throw new Exception(__('Failed to generate AI content', 'wp-videoscribe'));
        }
        
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($response_body['error'])) {
            throw new Exception('OpenAI API Error: ' . $response_body['error']['message']);
        }
        
        $ai_response = $response_body['choices'][0]['message']['content'];
        $ai_content = json_decode($ai_response, true);
        
        if (!$ai_content) {
            // Fallback parsing if JSON format fails
            $ai_content = array(
                'summary' => $ai_response,
                'titles' => array('Generated Title 1', 'Generated Title 2', 'Generated Title 3')
            );
        }
        
        return $ai_content;
    }
    
    private function create_post_draft($video_data, $ai_content) {
        // Download and set featured image
        $attachment_id = $this->download_featured_image($video_data['thumbnail'], $video_data['title']);
        
        // Create post content
        $post_content = $ai_content['summary'] . "\n\n";
        $post_content .= "<h3>Original Video</h3>\n";
        $post_content .= "<p>Source: <a href=\"https://www.youtube.com/watch?v={$video_data['id']}\" target=\"_blank\">{$video_data['title']}</a></p>\n";
        $post_content .= "<p>Channel: {$video_data['channel']}</p>\n\n";
        
        if (!empty($ai_content['titles'])) {
            $post_content .= "<h3>Alternative Title Suggestions</h3>\n<ul>\n";
            foreach ($ai_content['titles'] as $title) {
                $post_content .= "<li>" . esc_html($title) . "</li>\n";
            }
            $post_content .= "</ul>\n";
        }
        
        // Create the post
        $post_data = array(
            'post_title' => $video_data['title'],
            'post_content' => $post_content,
            'post_status' => 'draft',
            'post_type' => 'post',
            'meta_input' => array(
                '_wp_videoscribe_source_url' => "https://www.youtube.com/watch?v={$video_data['id']}",
                '_wp_videoscribe_video_id' => $video_data['id'],
                '_wp_videoscribe_channel' => $video_data['channel']
            )
        );
        
        $post_id = wp_insert_post($post_data);
        
        if ($post_id && $attachment_id) {
            set_post_thumbnail($post_id, $attachment_id);
        }
        
        return $post_id;
    }
    
    private function download_featured_image($image_url, $title) {
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        
        $tmp = download_url($image_url);
        
        if (is_wp_error($tmp)) {
            return false;
        }
        
        $file_array = array(
            'name' => sanitize_file_name($title . '.jpg'),
            'tmp_name' => $tmp
        );
        
        $attachment_id = media_handle_sideload($file_array, 0);
        
        if (is_wp_error($attachment_id)) {
            @unlink($tmp);
            return false;
        }
        
        return $attachment_id;
    }
    
    public function get_recent_videoscribe_posts() {
        check_ajax_referer('wp_videoscribe_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-videoscribe'));
        }
        
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => array('draft', 'publish'),
            'meta_key' => '_wp_videoscribe_source_url',
            'numberposts' => 5,
            'orderby' => 'date',
            'order' => 'DESC'
        ));
        
        $recent_posts = array();
        
        foreach ($posts as $post) {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $thumbnail_url = $thumbnail_id ? wp_get_attachment_image_url($thumbnail_id, 'thumbnail') : '';
            
            $recent_posts[] = array(
                'id' => $post->ID,
                'title' => $post->post_title,
                'date' => get_the_date('M j, Y', $post),
                'status' => $post->post_status,
                'thumbnail' => $thumbnail_url,
                'edit_url' => get_edit_post_link($post->ID),
                'view_url' => get_permalink($post->ID)
            );
        }
        
        wp_send_json_success($recent_posts);
    }
    
    public function test_api_configuration() {
        check_ajax_referer('wp_videoscribe_test_apis', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Unauthorized', 'wp-videoscribe'));
        }

        $openai_api_key = get_option('wp_videoscribe_openai_api_key');
        $errors = array();

        // Test OpenAI API
        if (empty($openai_api_key)) {
            $errors[] = __('OpenAI API key is not configured', 'wp-videoscribe');
        } else {
            $response = wp_remote_get('https://api.openai.com/v1/models', array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $openai_api_key,
                    'Content-Type' => 'application/json',
                ),
                'timeout' => 30
            ));

            if (is_wp_error($response)) {
                $errors[] = __('OpenAI API test failed: ', 'wp-videoscribe') . $response->get_error_message();
            } else {
                $response_code = wp_remote_retrieve_response_code($response);
                if ($response_code !== 200) {
                    $body = json_decode(wp_remote_retrieve_body($response), true);
                    $error_message = isset($body['error']['message']) ? $body['error']['message'] : 'Unknown error';
                    $errors[] = __('OpenAI API test failed: ', 'wp-videoscribe') . $error_message;
                }
            }
        }

        if (!empty($errors)) {
            wp_send_json_error(implode('<br>', $errors));
        } else {
            wp_send_json_success(array(
                'message' => __('All API configurations are working correctly!', 'wp-videoscribe')
            ));
        }
    }
}

// Initialize the plugin
new WPVideoScribe();