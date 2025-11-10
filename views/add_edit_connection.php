<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin">
                            <i class="fa fa-plug"></i> <?php echo $title; ?>
                        </h4>
                        <hr class="hr-panel-heading" />

                        <?php if (isset($connection)): ?>
                            <div class="alert alert-info">
                                <i class="fa fa-info-circle"></i> <strong>Editing Connection:</strong> 
                                <?php echo $connection->account_name ?: ucfirst($connection->platform) . ' Connection'; ?>
                            </div>
                        <?php endif; ?>

                        <?php echo validation_errors('<div class="alert alert-danger">', '</div>'); ?>

                        <?php echo form_open(current_url(), ['id' => 'connection_form']); ?>
                        
                        <?php if ($connection): ?>
                            <input type="hidden" name="connection_id" value="<?php echo $connection->id; ?>">
                        <?php endif; ?>

                        <!-- STEP 1: Client Selection -->
                        <div class="form-group">
                            <label for="client_id" class="control-label">
                                <span class="label label-primary">STEP 1</span> <strong>Select Client</strong>
                            </label>
                            <select name="client_id" id="client_id" class="selectpicker form-control" 
                                    data-live-search="true" data-width="100%" required>
                                <option value="">-- Select Client --</option>
                                <?php foreach ($clients as $client) { ?>
                                    <option value="<?php echo $client['userid']; ?>" 
                                            <?php echo ($connection && $connection->client_id == $client['userid']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($client['company']); ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <!-- STEP 2: Platform Selection -->
                        <div class="form-group">
                            <label for="platform" class="control-label">
                                <span class="label label-primary">STEP 2</span> <strong>Select Platform</strong>
                            </label>
                            <select name="platform" id="platform" class="form-control select-platform" required 
                                    <?php echo $connection ? 'disabled' : ''; ?>>
                                <option value="">-- Select Platform --</option>
                                <option value="facebook" <?php echo ($connection && $connection->platform == 'facebook') ? 'selected' : ''; ?>>
                                    🔵 Facebook
                                </option>
                                <option value="instagram" <?php echo ($connection && $connection->platform == 'instagram') ? 'selected' : ''; ?>>
                                    📷 Instagram
                                </option>
                                <option value="x" <?php echo ($connection && $connection->platform == 'x') ? 'selected' : ''; ?>>
                                    ✖️ X (Twitter)
                                </option>
                                <option value="linkedin" <?php echo ($connection && $connection->platform == 'linkedin') ? 'selected' : ''; ?>>
                                    💼 LinkedIn
                                </option>
                                <option value="tumblr" <?php echo ($connection && $connection->platform == 'tumblr') ? 'selected' : ''; ?>>
                                    📝 Tumblr
                                </option>
                                <option value="pinterest" <?php echo ($connection && $connection->platform == 'pinterest') ? 'selected' : ''; ?>>
                                    📌 Pinterest
                                </option>
                            </select>
                            <?php if ($connection) { ?>
                                <input type="hidden" name="platform" value="<?php echo $connection->platform; ?>">
                            <?php } ?>
                        </div>

                        <!-- STEP 3: Dynamic Platform-Specific Fields -->
                        <div id="platform_fields" style="display:none;">
                            <hr>
                            <label class="control-label">
                                <span class="label label-primary">STEP 3</span> <strong>Enter Credentials</strong>
                            </label>
                            <div id="fields_container"></div>
                        </div>

                        <!-- Status (Always Visible) -->
                        <div id="status_field" style="display:none;">
                            <hr>
                            <div class="form-group">
                                <label class="control-label">Connection Status</label>
                                <div class="radio radio-primary">
                                    <input type="radio" name="status" id="status_active" value="1" 
                                           <?php echo (!$connection || $connection->status == 1) ? 'checked' : ''; ?>>
                                    <label for="status_active">
                                        <i class="fa fa-check-circle text-success"></i> <strong>Active</strong> - Enable this connection
                                    </label>
                                </div>
                                <div class="radio radio-primary">
                                    <input type="radio" name="status" id="status_inactive" value="0" 
                                           <?php echo ($connection && $connection->status == 0) ? 'checked' : ''; ?>>
                                    <label for="status_inactive">
                                        <i class="fa fa-times-circle text-danger"></i> <strong>Inactive</strong> - Disable temporarily
                                    </label>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fa fa-check"></i> <?php echo $connection ? 'Update Connection' : 'Save Connection'; ?>
                            </button>
                            <a href="<?php echo admin_url('sm_posters/connections'); ?>" class="btn btn-default btn-lg">
                                <i class="fa fa-times"></i> Cancel
                            </a>
                        </div>

                        <?php echo form_close(); ?>

                    </div>
                </div>

                <!-- Platform Instructions Panel -->
                <div id="instructions_panel" class="panel_s" style="display:none;">
                    <div class="panel-body">
                        <div id="instructions_content"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<style>
.help-text {
    font-size: 12px;
    color: #737373;
    margin-top: 5px;
}

.instruction-box {
    background: #f8f9fa;
    border-left: 4px solid #4267B2;
    padding: 15px;
    margin-top: 15px;
}

.instruction-box h5 {
    margin-top: 0;
    color: #333;
}

.instruction-box ol, .instruction-box ul {
    margin-bottom: 0;
}

.credential-group {
    background: #fff;
    border: 1px solid #e0e0e0;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 15px;
}
</style>

<?php init_tail(); ?>

<script>
// Platform field configurations
var platformFields = {
    facebook: {
        name: 'Facebook',
        color: '#4267B2',
        fields: [
            {
                name: 'account_name',
                label: 'Account Name (Optional)',
                type: 'text',
                placeholder: 'e.g., My Business Page',
                required: false
            },
            {
                name: 'account_id',
                label: 'Facebook Page ID',
                type: 'text',
                placeholder: '123456789012345',
                required: true,
                help: 'Numeric Page ID from Graph API Explorer'
            },
            {
                name: 'access_token',
                label: 'Page Access Token',
                type: 'textarea',
                placeholder: 'EAAxxxxxxxxxxxxx...',
                required: true,
                rows: 3,
                help: 'Long-lived page access token (60 days validity)'
            }
        ],
        instructions: `
            <h5><i class="fa fa-facebook"></i> Facebook Setup Instructions</h5>
            <ol>
                <li>Go to <a href="https://developers.facebook.com/tools/explorer/" target="_blank">Graph API Explorer</a></li>
                <li>Select your app or create one</li>
                <li>Click "Generate Access Token" with permissions: <code>pages_show_list</code>, <code>pages_manage_posts</code></li>
                <li>Run query: <code>GET /me/accounts</code></li>
                <li>Copy the page <strong>id</strong> and <strong>access_token</strong></li>
            </ol>
            <p class="text-warning"><i class="fa fa-exclamation-triangle"></i> <strong>Note:</strong> Exchange for long-lived token (60 days) for production use.</p>
        `
    },

    instagram: {
        name: 'Instagram',
        color: '#E4405F',
        fields: [
            {
                name: 'account_name',
                label: 'Account Name (Optional)',
                type: 'text',
                placeholder: 'e.g., @mybusiness',
                required: false
            },
            {
                name: 'account_id',
                label: 'Instagram Business Account ID',
                type: 'text',
                placeholder: '17841400...',
                required: true,
                help: 'Instagram Business Account ID from Graph API'
            },
            {
                name: 'access_token',
                label: 'Facebook Page Access Token',
                type: 'textarea',
                placeholder: 'Same token as connected Facebook Page',
                required: true,
                rows: 3,
                help: 'Use the access token from the Facebook Page connected to this Instagram account'
            }
        ],
        instructions: `
            <h5><i class="fa fa-instagram"></i> Instagram Setup Instructions</h5>
            <p><strong>Requirements:</strong> Instagram Business Account connected to Facebook Page</p>
            <ol>
                <li>Convert Instagram to Business Account (Settings → Account → Switch to Professional)</li>
                <li>Connect to your Facebook Page</li>
                <li>In Graph API Explorer, run: <code>GET /YOUR_PAGE_ID?fields=instagram_business_account</code></li>
                <li>Copy the <strong>instagram_business_account.id</strong> value</li>
                <li>Use the same access token as your Facebook Page</li>
            </ol>
            <p class="text-info"><i class="fa fa-info-circle"></i> <strong>Content:</strong> Images required, max 2,200 chars caption</p>
        `
    },

    x: {
        name: 'X (Twitter)',
        color: '#000000',
        fields: [
            {
                name: 'account_name',
                label: 'Account Name (Optional)',
                type: 'text',
                placeholder: 'e.g., @mybusiness',
                required: false
            },
            {
                name: 'api_key',
                label: 'API Key (Consumer Key)',
                type: 'text',
                placeholder: 'xxxxxxxxxxxxx',
                required: true,
                help: 'From X Developer Portal → Keys and tokens'
            },
            {
                name: 'api_secret',
                label: 'API Secret (Consumer Secret)',
                type: 'text',
                placeholder: 'xxxxxxxxxxxxx',
                required: true,
                help: 'From X Developer Portal → Keys and tokens'
            },
            {
                name: 'access_token',
                label: 'Access Token',
                type: 'text',
                placeholder: 'xxxxx-xxxxxxxxxxxxx',
                required: true,
                help: 'From X Developer Portal → Keys and tokens'
            },
            {
                name: 'access_token_secret',
                label: 'Access Token Secret',
                type: 'text',
                placeholder: 'xxxxxxxxxxxxx',
                required: true,
                help: 'From X Developer Portal → Keys and tokens'
            },
            {
                name: 'account_id',
                label: 'X Username (Optional)',
                type: 'text',
                placeholder: '@username',
                required: false,
                help: 'Your @username for reference'
            }
        ],
        instructions: `
            <h5><i class="fa fa-twitter"></i> X (Twitter) Setup Instructions</h5>
            <p class="text-warning"><strong>⚠️ Elevated Access Required</strong> for posting tweets</p>
            <ol>
                <li>Go to <a href="https://developer.twitter.com" target="_blank">X Developer Portal</a></li>
                <li>Create app and apply for <strong>Elevated access</strong></li>
                <li>Set app permissions to <strong>Read and Write</strong></li>
                <li>Go to "Keys and tokens" tab</li>
                <li>Generate/regenerate all 4 credentials (API Key, API Secret, Access Token, Access Token Secret)</li>
            </ol>
            <p class="text-info"><i class="fa fa-info-circle"></i> <strong>Limits:</strong> 280 chars, 4 images max per tweet</p>
        `
    },

    linkedin: {
        name: 'LinkedIn',
        color: '#0077B5',
        fields: [
            {
                name: 'account_name',
                label: 'Account Name (Optional)',
                type: 'text',
                placeholder: 'e.g., John Doe - LinkedIn',
                required: false
            },
            {
                name: 'account_id',
                label: 'Person URN',
                type: 'text',
                placeholder: 'urn:li:person:xxxxx',
                required: true,
                help: 'Get from: GET /v2/me, format as urn:li:person:ID'
            },
            {
                name: 'access_token',
                label: 'OAuth 2.0 Access Token',
                type: 'textarea',
                placeholder: 'AQV...',
                required: true,
                rows: 3,
                help: 'Must have w_member_social scope'
            },
            {
                name: 'refresh_token',
                label: 'Refresh Token (Optional)',
                type: 'textarea',
                placeholder: 'Refresh token for auto-renewal',
                required: false,
                rows: 2
            }
        ],
        instructions: `
            <h5><i class="fa fa-linkedin"></i> LinkedIn Setup Instructions</h5>
            <p class="text-warning"><strong>⚠️ Requires LinkedIn App Review</strong> for w_member_social scope</p>
            <ol>
                <li>Go to <a href="https://www.linkedin.com/developers/" target="_blank">LinkedIn Developers</a></li>
                <li>Create app and request "Share on LinkedIn" product</li>
                <li>Request <code>w_member_social</code> scope (requires review)</li>
                <li>Implement OAuth 2.0 flow to get access token</li>
                <li>Call <code>GET /v2/me</code> to get person ID</li>
                <li>Format as: <code>urn:li:person:YOUR_ID</code></li>
            </ol>
            <p class="text-info"><i class="fa fa-info-circle"></i> <strong>Content:</strong> Professional tone, max 3,000 chars</p>
        `
    },

    tumblr: {
    name: 'Tumblr',
    color: '#35465C',
    fields: [
        {
            name: 'account_id',
            label: 'Blog Hostname',
            type: 'text',
            placeholder: 'example.tumblr.com',
            required: true,
            help: 'Enter your Tumblr blog hostname (without https://).'
        },
        {
            name: 'account_name',
            label: 'Profile Display Name',
            type: 'text',
            placeholder: 'Optional Name (ex: Travel Stories)',
            required: false,
            help: 'This is just for identifying the account inside the system.'
        },
        {
            name: 'consumer_key',
            label: 'Consumer Key (API Key)',
            type: 'text',
            placeholder: 'Enter your Tumblr API Key',
            required: true,
            help: 'You will get this from Tumblr Developer Dashboard.'
        },
        {
            name: 'consumer_secret',
            label: 'Consumer Secret (API Secret)',
            type: 'text',
            placeholder: 'Enter your Tumblr API Secret',
            required: true,
        },
        {
            name: 'oauth_token',
            label: 'Access Token',
            type: 'text',
            placeholder: 'OAuth Access Token',
            required: true,
        },
        {
            name: 'oauth_token_secret',
            label: 'Access Token Secret',
            type: 'text',
            placeholder: 'OAuth Access Token Secret',
            required: true,
        }
    ],
    instructions: `
        <h5><i class="fa fa-tumblr"></i> How to Connect Tumblr</h5>
        <ol>
            <li>Visit <a href="https://www.tumblr.com/oauth/apps" target="_blank">https://www.tumblr.com/oauth/apps</a></li>
            <li>Create a new application or open an existing one</li>
            <li>Copy the <strong>Consumer Key</strong> and <strong>Consumer Secret</strong></li>
            <li>Use OAuth 1.0 authorization to generate:
                <ul>
                    <li>Access Token</li>
                    <li>Access Token Secret</li>
                </ul>
            </li>
            <li>Enter all credentials here and save</li>
        </ol>
        <p class="text-info"><i class="fa fa-info-circle"></i> Supported post formats include Text, Photo, Video, Quote, Link, and Chat.</p>
    `
},


    pinterest: {
        name: 'Pinterest',
        color: '#BD081C',
        fields: [
            {
                name: 'account_name',
                label: 'Account Name (Optional)',
                type: 'text',
                placeholder: 'e.g., My Pinterest Board',
                required: false
            },
            {
                name: 'account_id',
                label: 'Board ID',
                type: 'text',
                placeholder: 'Board ID from Pinterest API',
                required: true,
                help: 'Get from: GET /v5/boards'
            },
            {
                name: 'access_token',
                label: 'OAuth 2.0 Access Token',
                type: 'textarea',
                placeholder: 'pina_...',
                required: true,
                rows: 3,
                help: 'Must have boards:read and pins:write scopes'
            },
            {
                name: 'refresh_token',
                label: 'Refresh Token (Optional)',
                type: 'textarea',
                placeholder: 'Refresh token for auto-renewal',
                required: false,
                rows: 2
            }
        ],
        instructions: `
            <h5><i class="fa fa-pinterest"></i> Pinterest Setup Instructions</h5>
            <p class="text-danger"><strong>⚠️ Image Required</strong> for every pin</p>
            <ol>
                <li>Go to <a href="https://developers.pinterest.com/" target="_blank">Pinterest Developers</a></li>
                <li>Create app and request API access</li>
                <li>Implement OAuth 2.0 flow with scopes: <code>boards:read</code>, <code>pins:write</code></li>
                <li>Get user authorization and exchange for access token</li>
                <li>Call <code>GET /v5/boards</code> to get board ID</li>
            </ol>
            <p class="text-info"><i class="fa fa-info-circle"></i> <strong>Image:</strong> Min 600x900px, 2:3 aspect ratio ideal, max 32MB</p>
        `
    }
};

// Existing connection data (if editing)
var existingConnection = <?php echo json_encode($connection ? $connection : null); ?>;

// Debug: Log existing connection
if (existingConnection) {
    console.log('Existing Connection Data:', existingConnection);
}

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    var map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
}

document.addEventListener('DOMContentLoaded', function() {
    var platformSelect = document.getElementById('platform');
    var fieldsContainer = document.getElementById('fields_container');
    var platformFieldsDiv = document.getElementById('platform_fields');
    var statusField = document.getElementById('status_field');
    var instructionsPanel = document.getElementById('instructions_panel');
    var instructionsContent = document.getElementById('instructions_content');

    // Handle platform selection change
    platformSelect.addEventListener('change', function() {
        var platform = this.value;
        
        if (!platform) {
            platformFieldsDiv.style.display = 'none';
            statusField.style.display = 'none';
            instructionsPanel.style.display = 'none';
            return;
        }

        // Get platform configuration
        var config = platformFields[platform];
        if (!config) {
            console.error('Platform configuration not found for:', platform);
            return;
        }

        console.log('Loading fields for platform:', platform);

        // Build fields HTML
        var html = '<div class="credential-group">';
        
        config.fields.forEach(function(field) {
            html += '<div class="form-group">';
            html += '<label for="' + field.name + '" class="control-label">';
            if (field.required) {
                html += '<span class="text-danger">* </span>';
            }
            html += field.label + '</label>';

            // Get existing value - PROPERLY handle all field names
            var value = '';
            if (existingConnection && typeof existingConnection[field.name] !== 'undefined' && existingConnection[field.name] !== null) {
                value = existingConnection[field.name];
                console.log('Field:', field.name, '= ', value);
            } else {
                console.log('Field:', field.name, '= EMPTY');
            }

            if (field.type === 'textarea') {
                html += '<textarea name="' + field.name + '" id="' + field.name + '" class="form-control" ';
                html += 'rows="' + (field.rows || 3) + '" ';
                html += 'placeholder="' + escapeHtml(field.placeholder) + '" ';
                if (field.required) html += 'required ';
                html += '>' + escapeHtml(value) + '</textarea>';
            } else {
                html += '<input type="' + field.type + '" name="' + field.name + '" id="' + field.name + '" ';
                html += 'class="form-control" placeholder="' + escapeHtml(field.placeholder) + '" ';
                html += 'value="' + escapeHtml(value) + '" ';
                if (field.required) html += 'required ';
                html += '>';
            }

            if (field.help) {
                html += '<p class="help-text">' + field.help + '</p>';
            }
            html += '</div>';
        });

        html += '</div>';
        fieldsContainer.innerHTML = html;

        // Show instructions
        instructionsContent.innerHTML = '<div class="instruction-box">' + config.instructions + '</div>';
        
        // Show fields and status
        platformFieldsDiv.style.display = 'block';
        statusField.style.display = 'block';
        instructionsPanel.style.display = 'block';

        // Scroll to fields
        setTimeout(function() {
            platformFieldsDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 100);
    });

    // Trigger on page load if platform already selected
    if (platformSelect.value) {
        console.log('Platform already selected on load:', platformSelect.value);
        platformSelect.dispatchEvent(new Event('change'));
    }

    // Form validation
    document.getElementById('connection_form').addEventListener('submit', function(e) {
        var clientId = document.getElementById('client_id').value;
        if (!clientId) {
            alert('Please select a client');
            e.preventDefault();
            return false;
        }

        var platform = document.getElementById('platform').value;
        if (!platform) {
            alert('Please select a platform');
            e.preventDefault();
            return false;
        }

        console.log('Form submitting...');
    });
});
</script>
</body>
</html>