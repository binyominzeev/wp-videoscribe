# WP VideoScribe

A WordPress plugin that automatically generates blog post summaries and title suggestions from YouTube videos using AI, then creates WordPress post drafts with featured images.

## Features

- **YouTube Integration**: Extract video metadata, thumbnails, and transcripts
- **AI-Powered Content Generation**: Generate comprehensive summaries and 10-20 title suggestions using OpenAI GPT
- **Automatic Post Creation**: Create WordPress post drafts with generated content
- **Featured Image Support**: Automatically download and set video thumbnails as featured images
- **User-Friendly Admin Interface**: Simple form-based interface with progress tracking
- **Recent Posts Management**: View and manage recently created VideoScribe posts
- **API Configuration**: Easy setup and testing of required API keys

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- YouTube Data API v3 key
- OpenAI API key

## Installation

1. Download the plugin files
2. Upload the `wp-videoscribe` folder to your `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Configure your API keys in VideoScribe > Settings

## Configuration

### YouTube Data API Setup

1. Go to the [Google Cloud Console](https://console.developers.google.com/)
2. Create a new project or select an existing one
3. Enable the YouTube Data API v3
4. Create credentials (API key)
5. Copy the API key and paste it in the plugin settings

### OpenAI API Setup

1. Sign up or log in to [OpenAI Platform](https://platform.openai.com/)
2. Navigate to the API Keys section
3. Create a new secret key
4. Copy the API key and paste it in the plugin settings
5. Ensure you have sufficient credits/billing set up

## Usage

1. Navigate to VideoScribe in your WordPress admin
2. Enter a YouTube video URL in the form
3. Click "Generate Blog Post"
4. Wait for the AI to process the video and generate content
5. Review and edit the created post draft
6. Publish when ready

## How It Works

1. **Video Data Extraction**: The plugin fetches video metadata, thumbnail, and transcript from YouTube
2. **AI Processing**: OpenAI GPT analyzes the transcript and generates a summary and title suggestions
3. **Post Creation**: A new WordPress post draft is created with the generated content
4. **Featured Image**: The video thumbnail is downloaded and set as the featured image

## File Structure

```
wp-videoscribe/
├── wp-videoscribe.php          # Main plugin file
├── templates/
│   ├── admin-page.php          # Admin interface template
│   └── settings-page.php       # Settings page template
├── assets/
│   ├── admin.css              # Admin styles
│   └── admin.js               # Admin JavaScript
└── README.md                  # This file
```

## Hooks and Filters

The plugin provides several hooks for customization:

### Actions
- `wp_videoscribe_before_post_creation` - Fired before creating a post
- `wp_videoscribe_after_post_creation` - Fired after creating a post
- `wp_videoscribe_ai_content_generated` - Fired after AI content generation

### Filters
- `wp_videoscribe_ai_prompt` - Modify the AI prompt
- `wp_videoscribe_post_content` - Modify the generated post content
- `wp_videoscribe_post_title` - Modify the post title

## Troubleshooting

### Common Issues

**"YouTube API key not configured"**
- Ensure you've entered a valid YouTube Data API key in the settings
- Verify the API key has the correct permissions

**"OpenAI API test failed"**
- Check that your OpenAI API key is valid
- Ensure you have sufficient credits in your OpenAI account

**"No captions available for this video"**
- The video doesn't have captions/subtitles available
- Try with a different video that has captions

**"Request timed out"**
- The AI processing is taking longer than expected
- Try again with a shorter video or check your internet connection

### Debug Mode

To enable debug logging, add this to your `wp-config.php`:

```php
define('WP_VIDEOSCRIBE_DEBUG', true);
```

## Limitations

- Only works with YouTube videos that have captions/transcripts available
- Requires active API keys for both YouTube and OpenAI
- Processing time depends on video length and AI response time
- OpenAI API usage costs apply based on your usage

## Security

- All API keys are stored securely in the WordPress database
- AJAX requests are protected with nonces
- User capability checks ensure only authorized users can access features
- Input sanitization and validation on all user inputs

## Support

For support, feature requests, or bug reports, please create an issue in the plugin repository.

## License

This plugin is licensed under the GPL v2 or later.

## Changelog

### Version 1.0.0
- Initial release
- YouTube video processing
- AI content generation
- Post draft creation
- Admin interface
- API configuration and testing