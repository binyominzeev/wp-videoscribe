<?php
// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="wp-videoscribe-container">
        <div class="wp-videoscribe-form-section">
            <div class="card">
                <h2><?php _e('Generate Blog Post from YouTube Video', 'wp-videoscribe'); ?></h2>
                <p><?php _e('Enter a YouTube URL below to automatically generate a blog post summary and title suggestions using AI.', 'wp-videoscribe'); ?></p>
                
                <form id="wp-videoscribe-form" method="post">
                    <?php wp_nonce_field('wp_videoscribe_nonce', 'wp_videoscribe_nonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="youtube_url"><?php _e('YouTube URL', 'wp-videoscribe'); ?></label>
                            </th>
                            <td>
                                <input type="url" 
                                       id="youtube_url" 
                                       name="youtube_url" 
                                       class="regular-text" 
                                       placeholder="https://www.youtube.com/watch?v=..." 
                                       required />
                                <p class="description">
                                    <?php _e('Enter a valid YouTube video URL (e.g., https://www.youtube.com/watch?v=dQw4w9WgXcQ)', 'wp-videoscribe'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                    
                    <p class="submit">
                        <input type="submit" 
                               id="submit-video" 
                               class="button-primary" 
                               value="<?php esc_attr_e('Generate Blog Post', 'wp-videoscribe'); ?>" />
                        <span class="spinner"></span>
                    </p>
                </form>
            </div>
        </div>
        
        <div class="wp-videoscribe-results-section" id="results-section" style="display: none;">
            <div class="card">
                <h2><?php _e('Processing Results', 'wp-videoscribe'); ?></h2>
                <div id="processing-status">
                    <p class="processing-message">
                        <span class="dashicons dashicons-update-alt spinning"></span>
                        <?php _e('Processing video...', 'wp-videoscribe'); ?>
                    </p>
                </div>
                <div id="results-content" style="display: none;">
                    <!-- Results will be populated here -->
                </div>
            </div>
        </div>
        
        <div class="wp-videoscribe-info-section">
            <div class="card">
                <h2><?php _e('How it Works', 'wp-videoscribe'); ?></h2>
                <ol>
                    <li><?php _e('Enter a YouTube video URL in the form above', 'wp-videoscribe'); ?></li>
                    <li><?php _e('The plugin extracts the video transcript and metadata', 'wp-videoscribe'); ?></li>
                    <li><?php _e('AI generates a comprehensive summary and title suggestions', 'wp-videoscribe'); ?></li>
                    <li><?php _e('A new post draft is created with the generated content and video thumbnail', 'wp-videoscribe'); ?></li>
                </ol>
                
                <h3><?php _e('Requirements', 'wp-videoscribe'); ?></h3>
                <ul>
                    <li><?php _e('YouTube Data API v3 key (for video data and transcripts)', 'wp-videoscribe'); ?></li>
                    <li><?php _e('OpenAI API key (for AI-generated content)', 'wp-videoscribe'); ?></li>
                </ul>
                
                <p>
                    <a href="<?php echo admin_url('admin.php?page=wp-videoscribe-settings'); ?>" class="button">
                        <?php _e('Configure API Keys', 'wp-videoscribe'); ?>
                    </a>
                </p>
            </div>
        </div>
        
        <div class="wp-videoscribe-recent-posts" id="recent-posts" style="display: none;">
            <div class="card">
                <h2><?php _e('Recent VideoScribe Posts', 'wp-videoscribe'); ?></h2>
                <div id="recent-posts-list">
                    <!-- Recent posts will be loaded here -->
                </div>
            </div>
        </div>
    </div>
</div>

<div id="wp-videoscribe-modal" class="wp-videoscribe-modal" style="display: none;">
    <div class="wp-videoscribe-modal-content">
        <span class="wp-videoscribe-modal-close">&times;</span>
        <h2><?php _e('Post Created Successfully!', 'wp-videoscribe'); ?></h2>
        <div id="modal-content">
            <!-- Modal content will be populated here -->
        </div>
        <div class="modal-actions">
            <a href="#" id="edit-post-link" class="button-primary" target="_blank">
                <?php _e('Edit Post', 'wp-videoscribe'); ?>
            </a>
            <button type="button" class="button" id="close-modal">
                <?php _e('Close', 'wp-videoscribe'); ?>
            </button>
        </div>
    </div>
</div>