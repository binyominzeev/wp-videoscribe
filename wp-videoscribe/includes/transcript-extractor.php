<?php
/**
 * YouTube Transcript Extractor
 * 
 * This class provides methods to extract transcripts from YouTube videos
 * using various approaches including YouTube Data API and fallback methods.
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_VideoScribe_Transcript_Extractor {
    
    private $youtube_api_key;
    
    public function __construct($api_key) {
        $this->youtube_api_key = $api_key;
    }
    
    /**
     * Extract transcript from a YouTube video
     * 
     * @param string $video_id YouTube video ID
     * @return string|false Transcript text or false on failure
     */
    public function extract_transcript($video_id) {
        // Try different methods in order of preference
        $transcript = $this->get_transcript_via_api($video_id);
        
        if (!$transcript) {
            $transcript = $this->get_transcript_via_fallback($video_id);
        }
        
        return $transcript;
    }
    
    /**
     * Get transcript using YouTube Data API
     * 
     * @param string $video_id YouTube video ID
     * @return string|false Transcript text or false on failure
     */
    private function get_transcript_via_api($video_id) {
        try {
            // Get captions list
            $captions_url = "https://www.googleapis.com/youtube/v3/captions?part=snippet&videoId={$video_id}&key={$this->youtube_api_key}";
            $captions_response = wp_remote_get($captions_url);
            
            if (is_wp_error($captions_response)) {
                return false;
            }
            
            $captions_data = json_decode(wp_remote_retrieve_body($captions_response), true);
            
            if (empty($captions_data['items'])) {
                return false;
            }
            
            // Find the best caption track (prefer auto-generated English)
            $caption_track = $this->find_best_caption_track($captions_data['items']);
            
            if (!$caption_track) {
                return false;
            }
            
            // Note: YouTube Data API v3 doesn't allow direct caption content download
            // without OAuth2. This is a limitation that would need to be addressed
            // in a production environment with proper authentication.
            
            return $this->get_caption_content($caption_track['id']);
            
        } catch (Exception $e) {
            error_log('WP VideoScribe: Transcript extraction error - ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Find the best caption track from available options
     * 
     * @param array $caption_tracks Available caption tracks
     * @return array|false Best caption track or false if none suitable
     */
    private function find_best_caption_track($caption_tracks) {
        $preferred_languages = array('en', 'en-US', 'en-GB');
        
        // First, try to find auto-generated English captions
        foreach ($caption_tracks as $track) {
            if (in_array($track['snippet']['language'], $preferred_languages) && 
                $track['snippet']['trackKind'] === 'asr') {
                return $track;
            }
        }
        
        // Then, try manual English captions
        foreach ($caption_tracks as $track) {
            if (in_array($track['snippet']['language'], $preferred_languages)) {
                return $track;
            }
        }
        
        // Finally, take any available caption
        return !empty($caption_tracks) ? $caption_tracks[0] : false;
    }
    
    /**
     * Get caption content (placeholder - requires OAuth2 in production)
     * 
     * @param string $caption_id Caption track ID
     * @return string|false Caption content or false on failure
     */
    private function get_caption_content($caption_id) {
        // Note: This is a placeholder. In a production environment, you would need:
        // 1. OAuth2 authentication
        // 2. Proper caption download endpoint
        // 3. SRT/VTT parsing
        
        return "This is a placeholder transcript. In a production environment, you would implement proper YouTube caption extraction with OAuth2 authentication to download the actual caption content.";
    }
    
    /**
     * Fallback method using alternative transcript extraction
     * 
     * @param string $video_id YouTube video ID
     * @return string|false Transcript text or false on failure
     */
    private function get_transcript_via_fallback($video_id) {
        // This could implement alternative methods like:
        // - Third-party transcript services
        // - Screen scraping (not recommended)
        // - User-provided transcripts
        
        // For now, return a helpful message
        return "Transcript extraction requires additional setup. Please refer to the plugin documentation for advanced transcript extraction methods.";
    }
    
    /**
     * Clean and format transcript text
     * 
     * @param string $transcript Raw transcript text
     * @return string Cleaned transcript
     */
    public function clean_transcript($transcript) {
        // Remove timestamps and formatting
        $transcript = preg_replace('/\d{2}:\d{2}:\d{2}\.\d{3} --> \d{2}:\d{2}:\d{2}\.\d{3}/', '', $transcript);
        
        // Remove HTML tags
        $transcript = strip_tags($transcript);
        
        // Clean up whitespace
        $transcript = preg_replace('/\s+/', ' ', $transcript);
        $transcript = trim($transcript);
        
        // Remove common caption artifacts
        $transcript = str_replace(array('[Music]', '[Applause]', '[Laughter]'), '', $transcript);
        
        return $transcript;
    }
    
    /**
     * Get video info for transcript context
     * 
     * @param string $video_id YouTube video ID
     * @return array|false Video information or false on failure
     */
    public function get_video_info($video_id) {
        $video_url = "https://www.googleapis.com/youtube/v3/videos?part=snippet,statistics,contentDetails&id={$video_id}&key={$this->youtube_api_key}";
        $response = wp_remote_get($video_url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data['items'])) {
            return false;
        }
        
        $video = $data['items'][0];
        
        return array(
            'title' => $video['snippet']['title'],
            'description' => $video['snippet']['description'],
            'duration' => $video['contentDetails']['duration'],
            'view_count' => $video['statistics']['viewCount'] ?? 0,
            'like_count' => $video['statistics']['likeCount'] ?? 0,
            'channel_title' => $video['snippet']['channelTitle'],
            'published_at' => $video['snippet']['publishedAt'],
            'thumbnail' => $video['snippet']['thumbnails']['maxres']['url'] ?? $video['snippet']['thumbnails']['high']['url']
        );
    }
    
    /**
     * Check if video has captions available
     * 
     * @param string $video_id YouTube video ID
     * @return bool True if captions are available
     */
    public function has_captions($video_id) {
        $captions_url = "https://www.googleapis.com/youtube/v3/captions?part=snippet&videoId={$video_id}&key={$this->youtube_api_key}";
        $response = wp_remote_get($captions_url);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        return !empty($data['items']);
    }
}