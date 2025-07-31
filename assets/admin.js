jQuery(document).ready(function($) {
    'use strict';
    
    // Form submission handler
    $('#wp-videoscribe-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitButton = $('#submit-video');
        var spinner = $('.submit .spinner');
        var youtubeUrl = $('#youtube_url').val().trim();
        
        // Validate URL
        if (!youtubeUrl) {
            showError('Please enter a YouTube URL.');
            return;
        }
        
        if (!isValidYouTubeUrl(youtubeUrl)) {
            showError('Please enter a valid YouTube URL.');
            return;
        }
        
        // Show processing state
        showProcessing();
        submitButton.prop('disabled', true);
        spinner.addClass('is-active');
        
        // Create progress steps
        createProgressSteps();
        updateProgressStep(1, 'active');
        
        // Make AJAX request
        $.ajax({
            url: wpVideoScribe.ajaxUrl,
            type: 'POST',
            data: {
                action: 'process_youtube_video',
                youtube_url: youtubeUrl,
                nonce: wpVideoScribe.nonce
            },
            timeout: 120000, // 2 minutes timeout
            success: function(response) {
                if (response.success) {
                    updateProgressStep(4, 'completed');
                    showSuccess(response.data);
                    loadRecentPosts();
                } else {
                    showError(response.data || wpVideoScribe.strings.error);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = wpVideoScribe.strings.error;
                
                if (status === 'timeout') {
                    errorMessage = 'Request timed out. Please try again.';
                } else if (xhr.responseJSON && xhr.responseJSON.data) {
                    errorMessage = xhr.responseJSON.data;
                }
                
                showError(errorMessage);
            },
            complete: function() {
                submitButton.prop('disabled', false);
                spinner.removeClass('is-active');
            }
        });
    });
    
    // Modal functionality
    $('.wp-videoscribe-modal-close, #close-modal').on('click', function() {
        $('#wp-videoscribe-modal').hide();
    });
    
    // Close modal when clicking outside
    $('#wp-videoscribe-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });
    
    // Load recent posts on page load
    loadRecentPosts();
    
    // Helper functions
    function isValidYouTubeUrl(url) {
        var pattern = /^(https?:\/\/)?(www\.)?(youtube\.com|youtu\.be)\/.+/;
        return pattern.test(url);
    }
    
    function showProcessing() {
        $('#results-section').show();
        $('#processing-status').show();
        $('#results-content').hide();
        
        // Scroll to results section
        $('html, body').animate({
            scrollTop: $('#results-section').offset().top - 50
        }, 500);
    }
    
    function showSuccess(data) {
        $('#processing-status').hide();
        $('#results-content').html(generateSuccessHTML(data)).show();
        
        // Show modal
        $('#modal-content').html(generateModalHTML(data));
        $('#edit-post-link').attr('href', data.edit_url);
        $('#wp-videoscribe-modal').show();
    }
    
    function showError(message) {
        $('#processing-status').hide();
        $('#results-content').html(
            '<div class="wp-videoscribe-error">' +
            '<strong>Error:</strong> ' + message +
            '</div>'
        ).show();
        
        // Clear progress steps
        $('.progress-steps').remove();
    }
    
    function generateSuccessHTML(data) {
        return '<div class="wp-videoscribe-success">' +
               '<p><strong>Success!</strong> ' + data.message + '</p>' +
               '<p><a href="' + data.edit_url + '" class="button-primary" target="_blank">Edit Post</a></p>' +
               '</div>';
    }
    
    function generateModalHTML(data) {
        return '<p>Your blog post draft has been created successfully!</p>' +
               '<p><strong>Post ID:</strong> ' + data.post_id + '</p>' +
               '<p>You can now edit the post to make any adjustments before publishing.</p>';
    }
    
    function createProgressSteps() {
        var stepsHTML = '<ul class="progress-steps">' +
                       '<li class="progress-step" data-step="1">Fetching Video Data</li>' +
                       '<li class="progress-step" data-step="2">Extracting Transcript</li>' +
                       '<li class="progress-step" data-step="3">Generating AI Content</li>' +
                       '<li class="progress-step" data-step="4">Creating Post Draft</li>' +
                       '</ul>';
        
        $('#processing-status').after(stepsHTML);
    }
    
    function updateProgressStep(step, status) {
        $('.progress-step').each(function() {
            var currentStep = $(this).data('step');
            $(this).removeClass('active completed');
            
            if (currentStep < step) {
                $(this).addClass('completed');
            } else if (currentStep === step) {
                $(this).addClass(status);
            }
        });
        
        // Simulate progress for visual feedback
        if (step < 4) {
            setTimeout(function() {
                updateProgressStep(step + 1, 'active');
            }, 2000 + Math.random() * 3000); // Random delay between 2-5 seconds
        }
    }
    
    function loadRecentPosts() {
        $.ajax({
            url: wpVideoScribe.ajaxUrl,
            type: 'POST',
            data: {
                action: 'get_recent_videoscribe_posts',
                nonce: wpVideoScribe.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    var postsHTML = '';
                    $.each(response.data, function(index, post) {
                        postsHTML += generateRecentPostHTML(post);
                    });
                    
                    $('#recent-posts-list').html(postsHTML);
                    $('#recent-posts').show();
                }
            }
        });
    }
    
    function generateRecentPostHTML(post) {
        return '<div class="recent-post-item">' +
               '<div class="recent-post-thumbnail">' +
               (post.thumbnail ? '<img src="' + post.thumbnail + '" alt="' + post.title + '">' : '') +
               '</div>' +
               '<div class="recent-post-details">' +
               '<div class="recent-post-title">' + post.title + '</div>' +
               '<div class="recent-post-meta">' +
               'Created: ' + post.date + ' | Status: ' + post.status +
               '</div>' +
               '</div>' +
               '<div class="recent-post-actions">' +
               '<a href="' + post.edit_url + '" class="button button-small">Edit</a>' +
               '<a href="' + post.view_url + '" class="button button-small" target="_blank">View</a>' +
               '</div>' +
               '</div>';
    }
    
    // Auto-refresh recent posts every 30 seconds
    setInterval(function() {
        if ($('#recent-posts').is(':visible')) {
            loadRecentPosts();
        }
    }, 30000);
    
    // Form validation
    $('#youtube_url').on('input', function() {
        var url = $(this).val().trim();
        var submitButton = $('#submit-video');
        
        if (url && isValidYouTubeUrl(url)) {
            $(this).removeClass('error');
            submitButton.prop('disabled', false);
        } else if (url) {
            $(this).addClass('error');
            submitButton.prop('disabled', true);
        } else {
            $(this).removeClass('error');
            submitButton.prop('disabled', false);
        }
    });
    
    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Escape key closes modal
        if (e.keyCode === 27) {
            $('#wp-videoscribe-modal').hide();
        }
        
        // Ctrl/Cmd + Enter submits form
        if ((e.ctrlKey || e.metaKey) && e.keyCode === 13) {
            if ($('#youtube_url').is(':focus')) {
                $('#wp-videoscribe-form').submit();
            }
        }
    });
    
    // Copy URL functionality
    $(document).on('click', '.copy-url', function(e) {
        e.preventDefault();
        var url = $(this).data('url');
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(url).then(function() {
                showNotification('URL copied to clipboard!');
            });
        } else {
            // Fallback for older browsers
            var textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            showNotification('URL copied to clipboard!');
        }
    });
    
    function showNotification(message) {
        var notification = $('<div class="notice notice-success is-dismissible">' +
                           '<p>' + message + '</p>' +
                           '</div>');
        
        $('.wrap h1').after(notification);
        
        setTimeout(function() {
            notification.fadeOut();
        }, 3000);
    }
    
    // URL preview functionality
    $('#youtube_url').on('blur', function() {
        var url = $(this).val().trim();
        if (url && isValidYouTubeUrl(url)) {
            previewVideo(url);
        }
    });
    
    function previewVideo(url) {
        var videoId = extractVideoId(url);
        if (videoId) {
            var previewHTML = '<div class="video-preview" style="margin-top: 10px;">' +
                             '<img src="https://img.youtube.com/vi/' + videoId + '/hqdefault.jpg" ' +
                             'alt="Video preview" style="max-width: 200px; border-radius: 4px;">' +
                             '</div>';
            
            $('.video-preview').remove();
            $('#youtube_url').parent().append(previewHTML);
        }
    }
    
    function extractVideoId(url) {
        var regExp = /^.*(youtu.be\/|v\/|u\/\w\/|embed\/|watch\?v=|&v=)([^#&?]*).*/;
        var match = url.match(regExp);
        return (match && match[2].length === 11) ? match[2] : null;
    }
    
    // Cleanup preview on form reset
    $(document).on('click', 'input[type="reset"]', function() {
        $('.video-preview').remove();
        $('#youtube_url').removeClass('error');
    });
});

// Add AJAX handler for getting recent posts
jQuery(document).ready(function($) {
    // This would be added to the main plugin file as an AJAX handler
    // wp_ajax_get_recent_videoscribe_posts
});