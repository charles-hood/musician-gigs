jQuery(document).ready(function($) {
    // Toggle "Ends same day" and "All Day" checkboxes
    $('#ends_same_day, #all_day').on('change', function() {
        if ($(this).is(':checked')) {
            if ($(this).attr('id') === 'ends_same_day') {
                $('#all_day').prop('checked', false);
            } else {
                $('#ends_same_day').prop('checked', false);
            }
        }
    });

    // Toggle "Redirect to external URL" checkbox
    $('#redirect_url').on('change', function() {
        if ($(this).is(':checked')) {
            var urlField = '<tr><th><label for="external_url">External URL</label></th><td><input type="url" name="external_url" id="external_url" class="regular-text" required></td></tr>';
            $(this).closest('tr').after(urlField);
        } else {
            $(this).closest('tr').next('tr').remove();
        }
    });

    // Display video preview
    $('#description').on('blur', function() {
        var description = $(this).val();
        var videoURL = description.match(/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com|youtu\.be|vimeo\.com|facebook\.com\/(?:video\.php|.*?\/videos\/))\/(?:watch\?v=|embed\/|v\/|videos\/)?(?:[\w-]+)(?:\S+)?/gi);

        if (videoURL) {
            var videoPreview = '<div class="video-preview"><iframe src="' + videoURL[0] + '" frameborder="0" allowfullscreen></iframe></div>';
            $(this).siblings('.video-preview').remove();
            $(this).after(videoPreview);
        } else {
            $(this).siblings('.video-preview').remove();
        }
    });
});
