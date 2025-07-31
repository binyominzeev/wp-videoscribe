<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$youtube_api_key = get_option('wp_videoscribe_youtube_api_key', '');
$openai_api_key = get_option('wp_videoscribe_openai_api_key', '');
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wp-videoscribe-settings-container">
        <form method="post" action="">
            <?php wp_nonce_field('wp_videoscribe_settings'); ?>
            
            <div class="card">
                <h2><?php _e('API Configuration', 'wp-videoscribe'); ?></h2>
                <p><?php _e('Configure the required API keys to enable VideoScribe functionality.', 'wp-videoscribe'); ?></p>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="youtube_api_key"><?php _e('YouTube Data API Key', 'wp-videoscribe'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="youtube_api_key" 
                                   name="youtube_api_key" 
                                   value="<?php echo esc_attr($youtube_api_key); ?>" 
                                   class="regular-text" 
                                   autocomplete="off" />
                            <button type="button" class="button toggle-password" data-target="youtube_api_key">
                                <?php _e('Show', 'wp-videoscribe'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Required for fetching video metadata and transcripts from YouTube.', 'wp-videoscribe'); ?>
                                <br>
                                <a href="https://console.developers.google.com/apis/credentials" target="_blank">
                                    <?php _e('Get your YouTube Data API key', 'wp-videoscribe'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">
                            <label for="openai_api_key"><?php _e('OpenAI API Key', 'wp-videoscribe'); ?></label>
                        </th>
                        <td>
                            <input type="password" 
                                   id="openai_api_key" 
                                   name="openai_api_key" 
                                   value="<?php echo esc_attr($openai_api_key); ?>" 
                                   class="regular-text" 
                                   autocomplete="off" />
                            <button type="button" class="button toggle-password" data-target="openai_api_key">
                                <?php _e('Show', 'wp-videoscribe'); ?>
                            </button>
                            <p class="description">
                                <?php _e('Required for generating AI-powered summaries and title suggestions.', 'wp-videoscribe'); ?>
                                <br>
                                <a href="https://platform.openai.com/api-keys" target="_blank">
                                    <?php _e('Get your OpenAI API key', 'wp-videoscribe'); ?>
                                </a>
                            </p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button(__('Save Settings', 'wp-videoscribe')); ?>
            </div>
        </form>
        
        <div class="card">
            <h2><?php _e('Setup Instructions', 'wp-videoscribe'); ?></h2>
            
            <h3><?php _e('YouTube Data API Setup', 'wp-videoscribe'); ?></h3>
            <ol>
                <li><?php _e('Go to the Google Cloud Console', 'wp-videoscribe'); ?></li>
                <li><?php _e('Create a new project or select an existing one', 'wp-videoscribe'); ?></li>
                <li><?php _e('Enable the YouTube Data API v3', 'wp-videoscribe'); ?></li>
                <li><?php _e('Create credentials (API key)', 'wp-videoscribe'); ?></li>
                <li><?php _e('Copy the API key and paste it above', 'wp-videoscribe'); ?></li>
            </ol>
            
            <h3><?php _e('OpenAI API Setup', 'wp-videoscribe'); ?></h3>
            <ol>
                <li><?php _e('Sign up or log in to OpenAI Platform', 'wp-videoscribe'); ?></li>
                <li><?php _e('Navigate to API Keys section', 'wp-videoscribe'); ?></li>
                <li><?php _e('Create a new secret key', 'wp-videoscribe'); ?></li>
                <li><?php _e('Copy the API key and paste it above', 'wp-videoscribe'); ?></li>
                <li><?php _e('Ensure you have sufficient credits/billing set up', 'wp-videoscribe'); ?></li>
            </ol>
            
            <div class="notice notice-info">
                <p>
                    <strong><?php _e('Note:', 'wp-videoscribe'); ?></strong>
                    <?php _e('Both API keys are required for the plugin to function properly. The keys are stored securely in your WordPress database.', 'wp-videoscribe'); ?>
                </p>
            </div>
        </div>
        
        <div class="card">
            <h2><?php _e('Test Configuration', 'wp-videoscribe'); ?></h2>
            <p><?php _e('Test your API configuration to ensure everything is working correctly.', 'wp-videoscribe'); ?></p>
            
            <div id="api-test-results" style="display: none;">
                <!-- Test results will be displayed here -->
            </div>
            
            <p>
                <button type="button" id="test-apis" class="button">
                    <?php _e('Test API Configuration', 'wp-videoscribe'); ?>
                </button>
                <span class="spinner"></span>
            </p>
        </div>
        
        <div class="card">
            <h2><?php _e('Usage Statistics', 'wp-videoscribe'); ?></h2>
            <?php
            $posts_created = get_posts(array(
                'post_type' => 'post',
                'post_status' => array('draft', 'publish'),
                'meta_key' => '_wp_videoscribe_source_url',
                'numberposts' => -1,
                'fields' => 'ids'
            ));
            $total_posts = count($posts_created);
            ?>
            
            <table class="wp-list-table widefat fixed striped">
                <tbody>
                    <tr>
                        <td><strong><?php _e('Total Posts Created', 'wp-videoscribe'); ?></strong></td>
                        <td><?php echo $total_posts; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php _e('Plugin Version', 'wp-videoscribe'); ?></strong></td>
                        <td><?php echo WP_VIDEOSCRIBE_VERSION; ?></td>
                    </tr>
                </tbody>
            </table>
            
            <?php if ($total_posts > 0): ?>
            <p>
                <a href="<?php echo admin_url('edit.php?meta_key=_wp_videoscribe_source_url'); ?>" class="button">
                    <?php _e('View VideoScribe Posts', 'wp-videoscribe'); ?>
                </a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        var target = $(this).data('target');
        var input = $('#' + target);
        var button = $(this);
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            button.text('<?php esc_js(_e('Hide', 'wp-videoscribe')); ?>');
        } else {
            input.attr('type', 'password');
            button.text('<?php esc_js(_e('Show', 'wp-videoscribe')); ?>');
        }
    });
    
    // Test API configuration
    $('#test-apis').on('click', function() {
        var button = $(this);
        var spinner = button.next('.spinner');
        var resultsDiv = $('#api-test-results');
        
        button.prop('disabled', true);
        spinner.addClass('is-active');
        
        $.post(ajaxurl, {
            action: 'test_api_configuration',
            nonce: '<?php echo wp_create_nonce('wp_videoscribe_test_apis'); ?>'
        }, function(response) {
            button.prop('disabled', false);
            spinner.removeClass('is-active');
            
            if (response.success) {
                resultsDiv.html('<div class="notice notice-success"><p>' + response.data.message + '</p></div>');
            } else {
                resultsDiv.html('<div class="notice notice-error"><p>' + response.data + '</p></div>');
            }
            
            resultsDiv.show();
        });
    });
});
</script>