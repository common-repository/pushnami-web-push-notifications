var file_frame;

        jQuery(document).ready(function ($) {

            $('.upload').click(function () {
                var that = $(this);
                var img = $(this).siblings('.img');
                var url = $(this).siblings('.image_url');

                // Create the media frame.
                file_frame = wp.media.frames.file_frame = wp.media({
                    title: 'Select Image',
                    button: {
                        text: 'Select Image'
                    },
                    multiple: false  // Set to true to allow multiple files to be selected
                });

                // When an image is selected, run a callback.
                file_frame.on('select', function () {
                    // We set multiple to false so only get one image from the uploader
                    attachment = file_frame.state().get('selection').first().toJSON();

                    img.html('<img style="width:200px;height:200px" src="' + attachment.url + '" />');

                    url.val(attachment.url);
                    // Do something with attachment.id and/or attachment.url here
                });

                // Finally, open the modal
                file_frame.open();
            });

            $('#pushnami_prompt').click(function (event) {
                const promptChecked = event.target.checked;
                const updateChecked = $('#pushnami_update').prop("checked");
                if (!promptChecked && !updateChecked) {
                    $('#prompt_update_error').css('display', '');
                    $('#submit_button').prop('disabled', true);
                } else {
                    $('#prompt_update_error').css('display', 'none');
                    $('#submit_button').prop('disabled', false);
                }
            });

            $('#track_category').click(function (event) {
                const promptChecked = event.target.checked;
                const updateChecked = $('#pushnami_update').prop("checked");
                if (!promptChecked && !updateChecked) {
                    $('#submit_button').prop('disabled', true);
                } else {
                    $('#submit_button').prop('disabled', false);
                }
            });

            $('#pushnami_update').click(function (event) {
                const updateChecked = event.target.checked;
                const promptChecked = $('#pushnami_prompt').prop("checked");
                if (!updateChecked && !promptChecked) {
                    $('#prompt_update_error').css('display', '');
                    $('#submit_button').prop('disabled', true);
                } else {
                    $('#prompt_update_error').css('display', 'none');
                    $('#submit_button').prop('disabled', false);
                }
            });

            $('#pushnami_advanced_optin').click(function (event) {
                if (event.target.checked) {
                    $('#advanced_optin_notice').css('display', '');
                } else {
                    $('#advanced_optin_notice').css('display', 'none');
                }
            });

            $('#pushnami_prompt_trigger').click(function (event) {
                if (event.target.checked) {
                    $('#pushnami_prompt_trigger_id').css('display', '');
                } else {
                    $('#pushnami_prompt_trigger_id').css('display', 'none');
                }
            });

            $('#pushnami_prompt_delay').click(function (event) {
                if (event.target.checked) {
                    $('#pushnami_prompt_delay_time').css('display', '');
                } else {
                    $('#pushnami_prompt_delay_time').css('display', 'none');
                }
            });

            // debug mode enable keyboard shortcut listener
            let debugKeyPressed = [];
            const debugKeyLength = 9;
            $(document).keyup(({ key }) => {
                if (key) {
                    debugKeyPressed.push(key.toLowerCase());

                    if (debugKeyPressed.length > debugKeyLength) debugKeyPressed.shift();

                    if (
                        debugKeyPressed.length === debugKeyLength &&
                        debugKeyPressed.join('') === 'pushnamiarrowdown'
                    ) {
                        $('.debug_option').css('display', '');
                        $('#pushnami_debug').prop('checked', true);
                    }
                }
            });
        });