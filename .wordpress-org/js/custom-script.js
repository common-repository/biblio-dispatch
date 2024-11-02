var registerUrl = '<?php echo esc_url(REGISTER_URL); ?>';
            // JavaScript to handle tab switching
            jQuery(document).ready(function($) {
                document.getElementById('referer').value = document.referrer;
                
                // Use localized data from PHP
                const registerUrl = wcApiVars.register_url;
                const apiKeysUrl = wcApiVars.api_keys_url;
                
                // updateButtonText();
                $('#generate_keys').on('click', function () {
                    // const apiKeysUrl = "<?php echo esc_url(admin_url('admin.php?page=wc-settings&tab=advanced&section=keys')); ?>";
                    window.location.href = apiKeysUrl;
                });
                $('#save').on('click', async function (e) {
                    e.preventDefault();
                    await saveData(false);

                });
                
                $('#connect').on('click', async function (e) {
                     e.preventDefault();
                    var flagValue = $('#connect').data('flag');
                    await saveData(true); 

                });
                
                async function saveData(submitAfterSave)
                {                   
                    var consumer_key = $('#consumer_key').val();
                    var consumer_secret = $('#consumer_secret').val();
                    var store_name = $('#store_name').val();
                    var site_url = $('#site_url').val();
                    var platform_id =$("#platform_id").val();
                    var _wc_api_nonce = $('#_wc_api_nonce').val();
                    
                     if (consumer_key && consumer_secret) {
                        try {
                            const response = await $.ajax({
                                url: wcApiVars.ajax_url,
                                type: 'POST',
                                data: {
                                    action: 'wc_api_key_check_save_data',
                                    consumer_key: consumer_key,
                                    consumer_secret: consumer_secret,
                                    store_name: store_name,
                                    site_url: site_url,
                                    _wc_api_nonce: _wc_api_nonce
                                }
                            });
                            if (response.success) {
                                toastr.success(response.data.message || 'Configurations saved successfully!');
                                
                                // If save is successful and `submitAfterSave` is true, submit the form
                                if (submitAfterSave) {
                                    $('#biblioDispatchPlugin').trigger('submit');
                                }
                            } else if (response.data.statusCode == 401) {
                                toastr.error('Unauthorized: Invalid consumer key or secret.');
                            } else {
                                toastr.error('Error: ' + (response.data.message || 'Something went wrong.'));
                            }
                        } catch (error) {
                            toastr.error('AJAX Error: ' + error);
                        }
                    } else {
                        toastr.warning('Please fill out all required fields.');
                    }
                    
                }
            });