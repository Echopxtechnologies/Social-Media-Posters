<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-share-alt"></i> Create Social Media Post
                        </h4>
                        <hr class="hr-panel-heading" />

                        <?php if (empty($connections)) { ?>
                            <div class="alert alert-warning text-center">
                                <p><i class="fa fa-exclamation-triangle fa-2x"></i></p>
                                <h4>No Active Connections Found!</h4>
                                <p>Please add at least one social media connection before creating posts.</p>
                                <a href="<?php echo admin_url('sm_posters/add_connection'); ?>" class="btn btn-primary btn-lg mtop15">
                                    <i class="fa fa-plus"></i> Add Connection
                                </a>
                            </div>
                        <?php } else { ?>

                        <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

                        <?php echo form_open(current_url(), ['id' => 'post_form']); ?>

                        <!-- Hidden container for platform inputs -->
                        <div id="platform_inputs_container"></div>

                        <div class="row">
                            <!-- LEFT COLUMN: Content Creation -->
                            <div class="col-md-7">
                                
                                <!-- Step 1: Select Client -->
                                <div class="form-group">
                                    <label class="control-label">
                                        <span class="label-step">1</span> Select Client
                                    </label>
                                    <select name="client_id" id="client_id" class="selectpicker form-control" 
                                            data-live-search="true" data-width="100%" required>
                                        <option value="">-- Select Client --</option>
                                        <?php 
                                        $clients = [];
                                        foreach ($connections as $conn) {
                                            if (!isset($clients[$conn->client_id])) {
                                                $clients[$conn->client_id] = $conn->company;
                                            }
                                        }
                                        foreach ($clients as $client_id => $company) {
                                        ?>
                                            <option value="<?php echo $client_id; ?>"><?php echo htmlspecialchars($company); ?></option>
                                        <?php } ?>
                                    </select>
                                </div>

                                <div id="content_area" style="display:none;">
                                    
                                    <!-- Step 2: Upload Media -->
                                    <div class="form-group">
                                        <label class="control-label">
                                            <span class="label-step">2</span> Upload Media (Optional)
                                        </label>
                                        
                                        <input type="file" 
                                               id="media_file" 
                                               accept="image/jpeg,image/jpg,image/png,image/gif,video/mp4"
                                               style="display:none;">
                                        
                                        <input type="hidden" name="media_base64" id="media_base64">
                                        <input type="hidden" name="media_type" id="media_type">
                                        <input type="hidden" name="media_mime" id="media_mime">
                                        <input type="hidden" name="media_filename" id="media_filename">
                                        
                                        <div id="upload_placeholder" class="upload-placeholder">
                                            <i class="fa fa-camera fa-3x text-muted"></i>
                                            <p class="text-muted mtop10 mbot0">Click to upload image or video</p>
                                            <small class="text-muted">Max: 100MB</small>
                                        </div>

                                        <div id="media_preview" style="display:none; position:relative;">
                                            <img id="preview_image" src="" style="max-width: 100%; border-radius: 8px; display:none;">
                                            <div id="preview_video" style="display:none;"></div>
                                            <button type="button" class="btn btn-danger btn-xs remove-media-btn" id="remove_media">
                                                <i class="fa fa-times"></i> Remove
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Step 3: Add Link
                                    <div class="form-group">
                                        <label for="link" class="control-label">
                                            <span class="label-step">3</span> Add Link (Optional)
                                        </label>
                                        <input type="url" 
                                               name="link" 
                                               id="link" 
                                               class="form-control" 
                                               placeholder="https://example.com">
                                    </div> -->

                                    <!-- Step 3: Write Message -->
                                    <div class="form-group">
                                        <label for="message" class="control-label">
                                            <span class="label-step">3</span> Write Your Message <span class="text-danger">*</span>
                                        </label>
                                        <textarea name="message" 
                                                  id="message" 
                                                  rows="8" 
                                                  class="form-control" 
                                                  placeholder="What would you like to share?"
                                                  required></textarea>
                                        <div class="text-right text-muted">
                                            <small><span id="char_count">0</span> characters</small>
                                        </div>
                                    </div>

                                    <!-- Step 4: Schedule -->
                                    <div class="form-group">
                                        <label class="control-label">
                                            <span class="label-step">4</span> When to Post?
                                        </label>
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="radio radio-primary">
                                                    <input type="radio" name="schedule_type" id="post_now" value="now" checked>
                                                    <label for="post_now">
                                                        <i class="fa fa-paper-plane"></i> Post Now
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="col-sm-6">
                                                <div class="radio radio-primary">
                                                    <input type="radio" name="schedule_type" id="schedule_later" value="schedule">
                                                    <label for="schedule_later">
                                                        <i class="fa fa-clock-o"></i> Schedule Later
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Schedule DateTime -->
                                    <div class="form-group" id="schedule_fields" style="display:none;">
                                        <div class="row">
                                            <div class="col-sm-7">
                                                <label>Date</label>
                                                <input type="text" 
                                                       name="scheduled_date" 
                                                       id="scheduled_date" 
                                                       class="form-control datepicker" 
                                                       placeholder="Select date"
                                                       autocomplete="off">
                                            </div>
                                            <div class="col-sm-5">
                                                <label>Time</label>
                                                <input type="time" 
                                                       name="scheduled_time" 
                                                       id="scheduled_time" 
                                                       class="form-control">
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </div>

                            <!-- RIGHT COLUMN: Platform Selection -->
                            <div class="col-md-5">
                                
                                <div class="accounts-panel">
                                    <div class="accounts-header">
                                        <h5 class="no-margin">
                                            <i class="fa fa-share-alt"></i> Select Platforms to Post
                                        </h5>
                                    </div>
                                    
                                    <div id="no_client_selected" class="text-center" style="padding: 60px 20px;">
                                        <i class="fa fa-hand-o-left fa-3x text-muted"></i>
                                        <p class="text-muted mtop15">Select a client first</p>
                                    </div>

                                    <div id="accounts_list" style="display:none;">
                                        <div class="accounts-body">
                                            <!-- Platforms will be loaded here via JavaScript -->
                                        </div>
                                        
                                        <div class="accounts-footer">
                                            <button type="button" class="btn btn-sm btn-default" id="select_all_platforms">
                                                <i class="fa fa-check-square-o"></i> Select All
                                            </button>
                                            <button type="button" class="btn btn-sm btn-default" id="clear_all_platforms">
                                                <i class="fa fa-square-o"></i> Clear All
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Platform Guidelines -->
                                <div class="mtop20">
                                    <div class="alert alert-info" style="margin-bottom: 0;">
                                        <strong><i class="fa fa-info-circle"></i> Tips:</strong>
                                        <ul class="list-unstyled mtop10" style="margin-bottom: 0; font-size: 11px;">
                                            <li><strong>Facebook:</strong> No limit</li>
                                            <li><strong>Instagram:</strong> 2,200 chars max</li>
                                            <li><strong>X:</strong> 280 chars max</li>
                                            <li><strong>LinkedIn:</strong> 3,000 chars max</li>
                                        </ul>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="row mtop20">
                            <div class="col-md-12">
                                <hr>
                                <button type="submit" class="btn btn-lg btn-primary" id="submit_btn" disabled>
                                    <i class="fa fa-paper-plane"></i> <span id="btn_text">Publish Post</span>
                                </button>
                                <a href="<?php echo admin_url('sm_posters/posts'); ?>" class="btn btn-lg btn-default">
                                    <i class="fa fa-times"></i> Cancel
                                </a>
                            </div>
                        </div>

                        <?php echo form_close(); ?>

                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.label-step {
    display: inline-block;
    width: 24px;
    height: 24px;
    line-height: 24px;
    text-align: center;
    background: #4267B2;
    color: white;
    border-radius: 50%;
    font-weight: bold;
    font-size: 12px;
    margin-right: 8px;
}

.upload-placeholder {
    border: 2px dashed #ddd;
    padding: 40px 20px;
    text-align: center;
    cursor: pointer;
    border-radius: 8px;
    background: #fafafa;
    transition: all 0.3s;
}

.upload-placeholder:hover {
    border-color: #4267B2;
    background: #f0f2f5;
}

.remove-media-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    z-index: 10;
}

.accounts-panel {
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    background: white;
    overflow: hidden;
}

.accounts-header {
    padding: 15px;
    background: #f8f9fa;
    border-bottom: 1px solid #e0e0e0;
}

.accounts-body {
    padding: 10px;
    max-height: 400px;
    overflow-y: auto;
}

.account-item {
    padding: 12px;
    margin-bottom: 8px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
}

.account-item:hover {
    border-color: #4267B2;
    background: #f8f9fa;
}

.account-item.selected {
    border-color: #4267B2;
    background: #e7f3ff;
}

.account-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    margin-right: 12px;
}

.account-icon svg {
    width: 24px;
    height: 24px;
}

.account-info {
    flex: 1;
}

.account-name {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 2px;
}

.account-platform {
    font-size: 11px;
    color: #666;
}

.account-checkbox {
    width: 20px;
    height: 20px;
}

.accounts-footer {
    padding: 10px 15px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
    text-align: center;
}

.accounts-footer .btn {
    margin: 0 5px;
}
</style>

<?php init_tail(); ?>

<script>
(function() {
    'use strict';
    
    var connections = <?php echo json_encode($connections); ?>;
    var platformIcons = {
        'facebook': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>',
        'instagram': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg>',
        'x': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>',
        'linkedin': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>',
        'tumblr': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M14.563 24c-5.093 0-7.031-3.756-7.031-6.411V9.747H5.116V6.648c3.63-1.313 4.512-4.596 4.71-6.469C9.84.051 9.941 0 9.999 0h3.517v6.114h4.801v3.633h-4.82v7.47c.016 1.001.375 2.371 2.207 2.371h.09c.631-.02 1.486-.205 1.936-.419l1.156 3.425c-.436.636-2.4 1.374-4.156 1.404h-.178l.011.002z"/></svg>',
        'pinterest': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.401.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.354-.629-2.758-1.379l-.749 2.848c-.269 1.045-1.004 2.352-1.498 3.146 1.123.345 2.306.535 3.55.535 6.607 0 11.985-5.365 11.985-11.987C23.97 5.39 18.592.026 11.985.026L12.017 0z"/></svg>'
    };

    var platformConfig = {
        'facebook': {name: 'Facebook', color: '#4267B2'},
        'instagram': {name: 'Instagram', color: '#E4405F'},
        'x': {name: 'X', color: '#000000'},
        'linkedin': {name: 'LinkedIn', color: '#0077B5'},
        'tumblr': {name: 'Tumblr', color: '#35465C'},
        'pinterest': {name: 'Pinterest', color: '#BD081C'}
    };

    // Initialize
    $('#scheduled_date').datepicker({
        format: 'yyyy-mm-dd',
        startDate: new Date(),
        autoclose: true
    });

    // Client selection
    $('#client_id').on('change', function() {
        var clientId = $(this).val();
        if (!clientId) {
            $('#content_area, #accounts_list').slideUp();
            $('#no_client_selected').show();
            $('#submit_btn').prop('disabled', true);
            return;
        }
        $('#content_area').slideDown();
        loadClientAccounts(clientId);
    });

    // Load client accounts
    function loadClientAccounts(clientId) {
        var clientConnections = connections.filter(function(conn) {
            return conn.client_id == clientId;
        });

        if (clientConnections.length === 0) {
            $('#accounts_list').slideUp();
            $('#no_client_selected').html(
                '<i class="fa fa-exclamation-triangle fa-3x text-warning"></i>' +
                '<p class="text-muted mtop15">No connections for this client</p>'
            ).show();
            return;
        }

        $('#no_client_selected').hide();
        $('#accounts_list').slideDown();

        var html = '';
        clientConnections.forEach(function(conn) {
            var config = platformConfig[conn.platform] || {name: conn.platform, color: '#999'};
            var icon = platformIcons[conn.platform] || '<i class="fa fa-share"></i>';
            var accountName = conn.account_name || conn.account_id;
            
            html += '<div class="account-item" data-connection-id="' + conn.id + '" data-platform="' + conn.platform + '">';
            html += '  <div class="account-icon" style="background-color: ' + config.color + '10; color: ' + config.color + ';">' + icon + '</div>';
            html += '  <div class="account-info">';
            html += '    <div class="account-name">' + accountName + '</div>';
            html += '    <div class="account-platform">' + config.name + '</div>';
            html += '  </div>';
            html += '  <input type="checkbox" class="account-checkbox" data-platform="' + conn.platform + '" data-connection-id="' + conn.id + '">';
            html += '</div>';
        });

        $('.accounts-body').html(html);
        bindAccountSelection();
    }

    // Bind account selection
    function bindAccountSelection() {
        $('.account-item').off('click').on('click', function(e) {
            if ($(e.target).is('input')) return;
            $(this).find('.account-checkbox').prop('checked', function(i, val) {
                return !val;
            }).trigger('change');
        });

        $('.account-checkbox').off('change').on('change', function() {
            var $item = $(this).closest('.account-item');
            $item.toggleClass('selected', this.checked);
            updateHiddenInputs();
            updateSubmitButton();
        });
    }

    // CRITICAL: Update hidden inputs
    function updateHiddenInputs() {
        var $container = $('#platform_inputs_container');
        $container.empty();
        
        $('.account-checkbox:checked').each(function() {
            var platform = $(this).data('platform');
            var connectionId = $(this).data('connection-id');
            
            // Add platform
            $container.append($('<input>', {
                type: 'hidden',
                name: 'platforms[]',
                value: platform
            }));
            
            // Add connection
            $container.append($('<input>', {
                type: 'hidden',
                name: 'connections[' + platform + ']',
                value: connectionId
            }));
        });
    }

    // Select/Clear all
    $('#select_all_platforms').click(function() {
        $('.account-checkbox').prop('checked', true).trigger('change');
    });

    $('#clear_all_platforms').click(function() {
        $('.account-checkbox').prop('checked', false).trigger('change');
    });

    // Update submit button
    function updateSubmitButton() {
        var hasMessage = $('#message').val().trim().length > 0;
        var hasSelection = $('.account-checkbox:checked').length > 0;
        $('#submit_btn').prop('disabled', !(hasMessage && hasSelection));
    }

    $('#message').on('input', function() {
        $('#char_count').text($(this).val().length);
        updateSubmitButton();
    });

    // Schedule type
    $('input[name="schedule_type"]').on('change', function() {
        if ($(this).val() === 'schedule') {
            $('#schedule_fields').slideDown();
            $('#btn_text').text('Schedule Post');
        } else {
            $('#schedule_fields').slideUp();
            $('#btn_text').text('Publish Post');
        }
    });

    // Media upload
    $('#upload_placeholder').click(function() {
        $('#media_file').click();
    });

    $('#media_file').on('change', function(e) {
        var file = e.target.files[0];
        if (!file) return;

        if (file.size > 100 * 1024 * 1024) {
            alert('File too large! Max 100MB');
            return;
        }

        var reader = new FileReader();
        reader.onload = function(e) {
            $('#media_base64').val(e.target.result.split(',')[1]);
            $('#media_type').val(file.type.startsWith('image/') ? 'image' : 'video');
            $('#media_mime').val(file.type);
            $('#media_filename').val(file.name);

            $('#upload_placeholder').hide();
            $('#media_preview').show();

            if (file.type.startsWith('image/')) {
                $('#preview_image').attr('src', e.target.result).show();
                $('#preview_video').hide();
            } else {
                $('#preview_video').html('<div class="alert alert-success"><i class="fa fa-video-camera"></i> ' + file.name + '</div>').show();
                $('#preview_image').hide();
            }
        };
        reader.readAsDataURL(file);
    });

    $('#remove_media').click(function() {
        $('#media_file, #media_base64, #media_type, #media_mime, #media_filename').val('');
        $('#media_preview').hide();
        $('#upload_placeholder').show();
    });

    // Form submission
    $('#post_form').on('submit', function(e) {
        var message = $('#message').val().trim();
        var checked = $('.account-checkbox:checked').length;

        if (!message) {
            e.preventDefault();
            alert('Please enter a message');
            return false;
        }

        if (checked === 0) {
            e.preventDefault();
            alert('Please select at least one platform');
            return false;
        }

        var scheduleType = $('input[name="schedule_type"]:checked').val();
        if (scheduleType === 'schedule' && (!$('#scheduled_date').val() || !$('#scheduled_time').val())) {
            e.preventDefault();
            alert('Please select date and time for scheduling');
            return false;
        }

        // Final update of hidden inputs
        updateHiddenInputs();
        
        $('#submit_btn').prop('disabled', true).html('<i class="fa fa-spinner fa-spin"></i> Processing...');
        return true;
    });

})();
</script>
</body>
</html>