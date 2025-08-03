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
    private $backend_api_base = 'http://217.13.101.122:8000';
    
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
        $url = $this->backend_api_base . '/transcript?video_id=' . urlencode($video_id);
        $response = wp_remote_get($url, array('timeout' => 30));
        if (is_wp_error($response)) {
            return false;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($data['transcript'])) {
            return $data['transcript'];
        }
        return false;
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
     * Fallback method using YouTube's public timedtext endpoint
     * 
     * @param string $video_id YouTube video ID
     * @return string|false Transcript text or false on failure
     */
    private function get_transcript_via_fallback($video_id) {
        // Fetch the list of available caption tracks
        $list_url = "https://www.youtube.com/api/timedtext?type=list&v={$video_id}";
        $list_response = wp_remote_get($list_url);
        if (is_wp_error($list_response)) {
            return false;
        }
        $list_body = wp_remote_retrieve_body($list_response);
        $tracks_xml = @simplexml_load_string($list_body);
        if ($tracks_xml === false || !isset($tracks_xml->track)) {
            return false;
        }

        // Try to find an English track (prefer asr/auto-generated)
        $track = null;
        foreach ($tracks_xml->track as $t) {
            $lang_code = (string)$t['lang_code'];
            $kind = (string)$t['kind'];
            if (in_array($lang_code, ['en', 'en-US', 'en-GB'])) {
                $track = $t;
                if ($kind === 'asr') break; // Prefer auto-generated if available
            }
        }
        // If no English, just use the first track
        if (!$track && isset($tracks_xml->track[0])) {
            $track = $tracks_xml->track[0];
        }
        if (!$track) {
            return false;
        }

        // Build the timedtext URL for the selected track
        $lang_code = (string)$track['lang_code'];
        $name = urlencode((string)$track['name']);
        $kind = isset($track['kind']) ? '&kind=' . urlencode((string)$track['kind']) : '';
        $caption_url = "https://www.youtube.com/api/timedtext?lang={$lang_code}&v={$video_id}{$kind}";
        if (!empty($name)) {
            $caption_url .= "&name={$name}";
        }

        $caption_response = wp_remote_get($caption_url);
        if (is_wp_error($caption_response)) {
            return false;
        }
        $caption_body = wp_remote_retrieve_body($caption_response);
        if (empty($caption_body)) {
            return false;
        }
        $xml = @simplexml_load_string($caption_body);
        if ($xml === false || !isset($xml->text)) {
            return false;
        }
        $transcript = '';
        foreach ($xml->text as $text) {
            $transcript .= (string)$text . ' ';
        }
        return trim($transcript);
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
     * Check if video has captions available (API or fallback)
     * 
     * @param string $video_id YouTube video ID
     * @return bool True if captions are available
     */
    public function has_captions($video_id) {
        // First, try Data API
        $captions_url = "https://www.googleapis.com/youtube/v3/captions?part=snippet&videoId={$video_id}&key={$this->youtube_api_key}";
        $response = wp_remote_get($captions_url);
        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (!empty($data['items'])) {
                return true;
            }
        }
        // Fallback: try timedtext endpoint
        $langs = ['en', 'en-US', 'en-GB'];
        foreach ($langs as $lang) {
            $url = "https://www.youtube.com/api/timedtext?lang={$lang}&v={$video_id}";
            $response = wp_remote_get($url);
            if (!is_wp_error($response) && !empty(wp_remote_retrieve_body($response))) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get transcript using a Python script (advanced method)
     * 
     * @param string $video_id YouTube video ID
     * @return string|false Transcript text or false on failure
     */
    private function get_transcript_via_python($video_id) {
        $python = 'python3'; // or the path to your python3 binary
        $script = __DIR__ . '/get_transcript.py';
        $cmd = escapeshellcmd("$python $script " . escapeshellarg($video_id));
        $output = shell_exec($cmd);
        if (!$output) {
            return false;
        }
        $result = json_decode($output, true);
        if (isset($result['transcript'])) {
            return $result['transcript'];
        }
        return false;
    }
}