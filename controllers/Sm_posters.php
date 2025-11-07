<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Social Media Posters Controller
 * Manages connections and posts across multiple platforms
 */
class Sm_posters extends AdminController
{
    public function __construct()
    {
        parent::__construct();
        
        // Load models
        $this->load->model('sm_posters_model');
        $this->load->model('facebook_model');
        $this->load->model('instagram_model');
        $this->load->model('x_model');
        $this->load->model('linkedin_model');
        $this->load->model('tumblr_model');
        $this->load->model('pinterest_model');
        $this->load->model('clients_model');
        
        // Load libraries
        $this->load->library('form_validation');
        $this->load->helper('text');
        
        // Check permissions
        if (!has_permission('sm_posters', '', 'view')) {
            access_denied('sm_posters');
        }
    }
    
    /**
     * Dashboard
     */
    public function index()
    {
        $data['title'] = 'Social Media Manager';
        $data['connections'] = $this->sm_posters_model->get_all_connections();
        $data['stats'] = $this->sm_posters_model->get_dashboard_stats();
        
        $this->load->view('sm_posters/dashboard', $data);
    }
    
    /**
     * Manage Connections
     */
    public function connections()
    {
        $data['title'] = 'Social Media Connections';
        $data['connections'] = $this->sm_posters_model->get_all_connections();
        
        $this->load->view('sm_posters/connections', $data);
    }
    
    /**
     * Add Connection
     */
    public function add_connection()
    {
        if (!has_permission('sm_posters', '', 'create')) {
            access_denied('sm_posters');
        }

        if ($this->input->post()) {
            // Validate common fields
            $this->form_validation->set_rules('client_id', 'Client', 'required');
            $this->form_validation->set_rules('platform', 'Platform', 'required');
            $this->form_validation->set_rules('account_id', 'Account ID', 'required');

            // Get platform early for conditional validation
            $platform = $this->input->post('platform');

            // Platform-specific validation rules
            switch ($platform) {
                case 'x':
                    // X needs multiple credentials
                    $this->form_validation->set_rules('api_key', 'API Key', 'required');
                    $this->form_validation->set_rules('api_secret', 'API Secret', 'required');
                    $this->form_validation->set_rules('access_token', 'Access Token', 'required');
                    $this->form_validation->set_rules('access_token_secret', 'Access Token Secret', 'required');
                    break;
                
                case 'tumblr':
                    // Tumblr uses OAuth 1.0a
                    $this->form_validation->set_rules('access_token', 'Access Token', 'required');
                    $this->form_validation->set_rules('access_token_secret', 'Access Token Secret', 'required');
                    break;
                
                default:
                    // Most platforms use OAuth 2.0
                    $this->form_validation->set_rules('access_token', 'Access Token', 'required');
                    break;
            }

            if ($this->form_validation->run() == TRUE) {
                // Build data array based on platform
                $data = [
                    'client_id' => $this->input->post('client_id'),
                    'account_name' => $this->input->post('account_name'),
                    'account_id' => $this->input->post('account_id'),
                    'status' => $this->input->post('status') ? 1 : 0,
                ];

                // Add platform-specific fields
                switch ($platform) {
                    case 'x':
                        $data['api_key'] = $this->input->post('api_key');
                        $data['api_secret'] = $this->input->post('api_secret');
                        $data['access_token'] = $this->input->post('access_token');
                        $data['access_token_secret'] = $this->input->post('access_token_secret');
                        break;
                    
                    case 'tumblr':
                        $data['access_token'] = $this->input->post('access_token');
                        $data['access_token_secret'] = $this->input->post('access_token_secret');
                        break;
                    
                    default:
                        $data['access_token'] = $this->input->post('access_token');
                        $data['refresh_token'] = $this->input->post('refresh_token');
                        $data['token_expires_at'] = $this->input->post('token_expires_at');
                        break;
                }

                // Add connection (platform is passed separately)
                $insert_id = $this->sm_posters_model->add_connection($platform, $data);

                if ($insert_id) {
                    set_alert('success', 'Connection added successfully');
                    redirect(admin_url('sm_posters/connections'));
                } else {
                    set_alert('danger', 'Failed to add connection. Please check logs.');
                }
            }
        }

        $data['title'] = 'Add Social Media Connection';
        $data['clients'] = $this->clients_model->get();
        $data['connection'] = null;
        $data['platform'] = ''; // Empty for new connection
        
        $this->load->view('sm_posters/add_edit_connection', $data);
    }
    
    /**
     * Edit Connection
     * URL format: sm_posters/edit_connection/{platform}/{id}
     */
    public function edit_connection($platform, $id)
    {
        if (!has_permission('sm_posters', '', 'edit')) {
            access_denied('sm_posters');
        }

        // Get connection from correct platform table
        $connection = $this->sm_posters_model->get_connection($platform, $id);

        if (!$connection) {
            show_404();
        }

        if ($this->input->post()) {
            // Validate
            $this->form_validation->set_rules('client_id', 'Client', 'required');
            $this->form_validation->set_rules('account_id', 'Account ID', 'required');

            // Platform-specific validation
            switch ($platform) {
                case 'x':
                    $this->form_validation->set_rules('api_key', 'API Key', 'required');
                    $this->form_validation->set_rules('api_secret', 'API Secret', 'required');
                    $this->form_validation->set_rules('access_token', 'Access Token', 'required');
                    $this->form_validation->set_rules('access_token_secret', 'Access Token Secret', 'required');
                    break;
                
                case 'tumblr':
                    $this->form_validation->set_rules('access_token', 'Access Token', 'required');
                    $this->form_validation->set_rules('access_token_secret', 'Access Token Secret', 'required');
                    break;
                
                default:
                    $this->form_validation->set_rules('access_token', 'Access Token', 'required');
                    break;
            }

            if ($this->form_validation->run() == TRUE) {
                // Build data array
                $data = [
                    'client_id' => $this->input->post('client_id'),
                    'account_name' => $this->input->post('account_name'),
                    'account_id' => $this->input->post('account_id'),
                    'status' => $this->input->post('status') ? 1 : 0,
                ];

                // Add platform-specific fields
                switch ($platform) {
                    case 'x':
                        $data['api_key'] = $this->input->post('api_key');
                        $data['api_secret'] = $this->input->post('api_secret');
                        $data['access_token'] = $this->input->post('access_token');
                        $data['access_token_secret'] = $this->input->post('access_token_secret');
                        break;
                    
                    case 'tumblr':
                        $data['access_token'] = $this->input->post('access_token');
                        $data['access_token_secret'] = $this->input->post('access_token_secret');
                        break;
                    
                    default:
                        $data['access_token'] = $this->input->post('access_token');
                        $data['refresh_token'] = $this->input->post('refresh_token');
                        $data['token_expires_at'] = $this->input->post('token_expires_at');
                        break;
                }

                // Update connection
                $result = $this->sm_posters_model->update_connection($platform, $id, $data);

                if ($result) {
                    set_alert('success', 'Connection updated successfully');
                    redirect(admin_url('sm_posters/connections'));
                } else {
                    set_alert('danger', 'Failed to update connection');
                }
            }
        }

        $data['title'] = 'Edit Connection - ' . ucfirst($platform);
        $data['clients'] = $this->clients_model->get();
        $data['connection'] = $connection;
        $data['platform'] = $platform;
        
        $this->load->view('sm_posters/add_edit_connection', $data);
    }
    
    /**
     * Delete Connection
     * URL format: sm_posters/delete_connection/{platform}/{id}
     */
    public function delete_connection($platform, $id)
    {
        if (!has_permission('sm_posters', '', 'delete')) {
            access_denied('sm_posters');
        }

        $response = $this->sm_posters_model->delete_connection($platform, $id);

        if ($response) {
            set_alert('success', 'Connection deleted successfully');
        } else {
            set_alert('danger', 'Failed to delete connection');
        }

        redirect(admin_url('sm_posters/connections'));
    }
    
    /**
     * Toggle Connection Status
     * URL format: sm_posters/toggle_connection/{platform}/{id}
     */
    public function toggle_connection($platform, $id)
    {
        if (!has_permission('sm_posters', '', 'edit')) {
            access_denied('sm_posters');
        }

        $connection = $this->sm_posters_model->get_connection($platform, $id);

        if ($connection) {
            $new_status = $connection->status == 1 ? 0 : 1;
            $this->sm_posters_model->update_connection($platform, $id, ['status' => $new_status]);
            set_alert('success', 'Status updated successfully');
        }

        redirect(admin_url('sm_posters/connections'));
    }

    /**
     * Create Post
     */
    public function create_post()
    {
        if (!has_permission('sm_posters', '', 'create')) {
            access_denied('sm_posters');
        }

        if ($this->input->post()) {
            $this->form_validation->set_rules('message', 'Message', 'required');
            $this->form_validation->set_rules('platforms[]', 'Platforms', 'required');

            if ($this->form_validation->run() == TRUE) {
                $message = $this->input->post('message');
                $link = $this->input->post('link');
                $platforms = $this->input->post('platforms'); // Array of platform names
                $connections = $this->input->post('connections'); // Array: platform => connection_id
                $schedule_type = $this->input->post('schedule_type');
                
                $media_base64 = $this->input->post('media_base64');
                $media_type = $this->input->post('media_type') ? $this->input->post('media_type') : 'none';
                $media_mime = $this->input->post('media_mime');
                $media_filename = $this->input->post('media_filename');

                // Handle scheduling
                $is_scheduled = ($schedule_type == 'schedule');
                $scheduled_at = null;

                if ($is_scheduled) {
                    $scheduled_date = $this->input->post('scheduled_date');
                    $scheduled_time = $this->input->post('scheduled_time');
                    
                    if (empty($scheduled_date) || empty($scheduled_time)) {
                        set_alert('danger', 'Please provide both date and time for scheduling');
                        redirect(admin_url('sm_posters/create_post'));
                        return;
                    }
                    
                    $scheduled_at = $scheduled_date . ' ' . $scheduled_time . ':00';
                    
                    // Validate datetime
                    $timestamp = strtotime($scheduled_at);
                    if ($timestamp === false) {
                        set_alert('danger', 'Invalid date/time format');
                        redirect(admin_url('sm_posters/create_post'));
                        return;
                    }
                    
                    $scheduled_at = date('Y-m-d H:i:s', $timestamp);
                }

                // Get client_id from first connection
                $client_id = 0;
                if (!empty($connections)) {
                    $first_platform = reset(array_keys($connections));
                    $first_connection_id = $connections[$first_platform];
                    
                    // Get connection from correct platform table
                    $first_conn = $this->sm_posters_model->get_connection($first_platform, $first_connection_id);
                    $client_id = $first_conn ? $first_conn->client_id : 0;
                }

                // Create main post record
                $post_data = [
                    'client_id' => $client_id,
                    'message' => $message,
                    'link' => $link,
                    'media_type' => $media_type,
                    'media_data' => !empty($media_base64) ? $media_base64 : null,
                    'media_mime' => $media_mime,
                    'media_filename' => $media_filename,
                    'scheduled_at' => $scheduled_at,
                    'is_scheduled' => $is_scheduled ? 1 : 0,
                    'status' => $is_scheduled ? 'scheduled' : 'publishing'
                ];
                
                $post_id = $this->sm_posters_model->add_post($post_data);

                if (!$post_id) {
                    set_alert('danger', 'Failed to create post');
                    redirect(admin_url('sm_posters/create_post'));
                }

                // If scheduled, save platform connections and exit
                if ($is_scheduled) {
                    foreach ($platforms as $platform) {
                        if (isset($connections[$platform])) {
                            $this->sm_posters_model->add_post_platform([
                                'post_id' => $post_id,
                                'connection_id' => $connections[$platform],
                                'platform' => $platform,
                                'status' => 'pending'
                            ]);
                        }
                    }
                    
                    set_alert('success', 'Post scheduled successfully for ' . date('M d, Y h:i A', strtotime($scheduled_at)));
                    redirect(admin_url('sm_posters/posts'));
                }

                // Post immediately to all selected platforms
                $results = [];
                $success_platforms = [];
                $failed_platforms = [];
                
                foreach ($platforms as $platform) {
                    if (!isset($connections[$platform])) {
                        continue;
                    }
                    
                    $connection_id = $connections[$platform];
                    
                    // Get connection from correct platform table
                    $connection = $this->sm_posters_model->get_connection($platform, $connection_id);
                    
                    if (!$connection || $connection->status != 1) {
                        $failed_platforms[] = ucfirst($platform) . ' (inactive)';
                        continue;
                    }

                    // Create temp file if media exists
                    $media_path = null;
                    if (!empty($media_base64)) {
                        $media_path = $this->_create_temp_file($media_base64, $media_mime, $media_filename);
                    }

                    // Post to platform
                    $result = $this->_post_to_platform($platform, $connection, $message, $link, $media_path);

                    // Delete temp file
                    if ($media_path && file_exists($media_path)) {
                        unlink($media_path);
                    }

                    // Save platform post record
                    $this->sm_posters_model->add_post_platform([
                        'post_id' => $post_id,
                        'connection_id' => $connection_id,
                        'platform' => $platform,
                        'platform_post_id' => $result['success'] ? $result['post_id'] : null,
                        'status' => $result['success'] ? 'published' : 'failed',
                        'error_message' => !$result['success'] ? $result['error'] : null,
                        'published_at' => $result['success'] ? date('Y-m-d H:i:s') : null
                    ]);

                    $results[$platform] = $result;
                    
                    if ($result['success']) {
                        $success_platforms[] = ucfirst($platform);
                    } else {
                        $failed_platforms[] = ucfirst($platform) . ': ' . $result['error'];
                    }
                }

                // Update main post status
                $all_failed = !empty($results) && count(array_filter($results, function($r) { return $r['success']; })) == 0;
                $all_success = !empty($results) && count(array_filter($results, function($r) { return !$r['success']; })) == 0;
                
                $this->sm_posters_model->update_post($post_id, [
                    'status' => $all_failed ? 'failed' : 'published',
                    'published_at' => date('Y-m-d H:i:s')
                ]);

                // Show detailed results
                if (!empty($success_platforms)) {
                    set_alert('success', 'Posted successfully to: ' . implode(', ', $success_platforms));
                    log_activity('Posted to ' . count($success_platforms) . ' social media platform(s)');
                }
                
                if (!empty($failed_platforms)) {
                    set_alert('warning', 'Failed to post to:<br>' . implode('<br>', $failed_platforms));
                }
                
                redirect(admin_url('sm_posters/posts'));
            }
        }

        $data['title'] = 'Create Social Media Post';
        $data['connections'] = $this->sm_posters_model->get_active_connections();
        
        $this->load->view('sm_posters/create_post', $data);
    }
/**
 * Route to correct platform model for posting
 * 
 * @param string $platform
 * @param object $connection Connection object from platform-specific table
 * @param string $message Post message/content
 * @param string|null $link Optional link to include
 * @param string|null $media_path Optional media file path
 * @return array ['success' => bool, 'post_id' => string|null, 'error' => string|null]
 */
private function _post_to_platform($platform, $connection, $message, $link = null, $media_path = null)
{
    try {
        $result = null;
        
        switch ($platform) {
            case 'facebook':
                $result = $this->facebook_model->_post_to_facebook($connection, $message, $link, $media_path);
                break;
            
            case 'instagram':
                $result = $this->instagram_model->post_to_instagram($connection, $message, $media_path);
                break;
            
            case 'x':
                // X requires credentials in specific array format
                if (!isset($connection->api_key) || !isset($connection->api_secret) || 
                    !isset($connection->access_token) || !isset($connection->access_token_secret)) {
                    return [
                        'success' => false,
                        'post_id' => null,
                        'error' => 'Missing required X credentials (api_key, api_secret, access_token, access_token_secret)'
                    ];
                }
                
                $credentials = [
                    'api_key' => $connection->api_key,
                    'api_secret' => $connection->api_secret,
                    'access_token' => $connection->access_token,
                    'access_token_secret' => $connection->access_token_secret
                ];
                
                $media = !empty($media_path) ? [$media_path] : [];
                $result = $this->x_model->post_to_x($credentials, $message, $media);
                break;
            
            case 'linkedin':
                $result = $this->linkedin_model->post_to_linkedin($connection, $message, $link, $media_path);
                break;
            
            case 'tumblr':
                $result = $this->tumblr_model->post_to_tumblr($connection, $message, $media_path);
                break;
            
            case 'pinterest':
                $result = $this->pinterest_model->post_to_pinterest($connection, $message, $link, $media_path);
                break;
            
            default:
                return [
                    'success' => false,
                    'post_id' => null,
                    'error' => 'Unknown platform: ' . $platform
                ];
        }
        
        // Normalize response format across all platforms
        // Some platforms return 'message' key, others 'error', standardize here
        if (isset($result['success'])) {
            return [
                'success' => $result['success'],
                'post_id' => isset($result['post_id']) ? $result['post_id'] : null,
                'error' => !$result['success'] ? (
                    isset($result['error']) ? $result['error'] : (
                        isset($result['message']) ? $result['message'] : 'Unknown error'
                    )
                ) : null
            ];
        }
        
        // Fallback if result format is unexpected
        return [
            'success' => false,
            'post_id' => null,
            'error' => 'Invalid response format from platform model: ' . json_encode($result)
        ];
        
    } catch (Exception $e) {
        log_message('error', '[SM_POSTERS] Exception in _post_to_platform: ' . $e->getMessage());
        return [
            'success' => false,
            'post_id' => null,
            'error' => 'Exception: ' . $e->getMessage()
        ];
    }
}

    /**
     * Create temporary file from base64
     * 
     * @param string $base64_data
     * @param string $mime_type
     * @param string $filename
     * @return string File path
     */
    private function _create_temp_file($base64_data, $mime_type, $filename)
    {
        $binary_data = base64_decode($base64_data);
        
        $temp_dir = FCPATH . 'uploads/temp/';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $temp_filename = 'temp_' . uniqid() . '_' . time() . '.' . $extension;
        $temp_path = $temp_dir . $temp_filename;
        
        file_put_contents($temp_path, $binary_data);
        
        return $temp_path;
    }

    /**
     * View Posts
     */
    public function posts()
    {
        $data['title'] = 'Posts History';
        $data['posts'] = $this->sm_posters_model->get_all_posts();
        
        $this->load->view('sm_posters/posts', $data);
    }

    /**
     * Delete Post
     */
    public function delete_post($id)
    {
        if (!has_permission('sm_posters', '', 'delete')) {
            access_denied('sm_posters');
        }

        $response = $this->sm_posters_model->delete_post($id);

        if ($response) {
            set_alert('success', 'Post deleted successfully');
        } else {
            set_alert('danger', 'Failed to delete post');
        }

        redirect(admin_url('sm_posters/posts'));
    }

    /**
     * Get post details (AJAX)
     */
    public function get_post_details($id)
    {
        $post = $this->sm_posters_model->get_post($id);
        
        if (!$post) {
            echo '<div class="alert alert-danger">Post not found</div>';
            return;
        }

        $platforms = $this->sm_posters_model->get_post_platforms($id);
        
        ?>
        <div class="row">
            <div class="col-md-12">
                <h4>Post Message</h4>
                <div class="well">
                    <?php echo nl2br(htmlspecialchars($post->message)); ?>
                </div>
            </div>
        </div>

        <?php if ($post->link) { ?>
        <div class="row">
            <div class="col-md-12">
                <h4>Link</h4>
                <p><a href="<?php echo $post->link; ?>" target="_blank"><?php echo $post->link; ?></a></p>
            </div>
        </div>
        <?php } ?>

        <?php if ($post->media_type != 'none') { ?>
        <div class="row">
            <div class="col-md-12">
                <h4>Media</h4>
                <p>
                    <i class="fa fa-<?php echo $post->media_type == 'image' ? 'image' : 'video-camera'; ?>"></i>
                    <?php echo ucfirst($post->media_type); ?>: <?php echo $post->media_filename; ?>
                </p>
            </div>
        </div>
        <?php } ?>

        <div class="row">
            <div class="col-md-12">
                <h4>Platform Status</h4>
                <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Platform</th>
                        <th>Status</th>
                        <th>Post ID</th>
                        <th>Published</th>
                        <th>Error</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($platforms as $platform) { ?>
                    <tr>
                        <td><?php echo ucfirst($platform->platform); ?></td>
                        <td>
                            <span class="label label-<?php echo $platform->status == 'published' ? 'success' : ($platform->status == 'failed' ? 'danger' : 'warning'); ?>">
                                <?php echo ucfirst($platform->status); ?>
                            </span>
                        </td>
                        <td><?php echo $platform->platform_post_id ? $platform->platform_post_id : '-'; ?></td>
                        <td><?php echo $platform->published_at ? _dt($platform->published_at) : '-'; ?></td>
                        <td>
                            <?php 
                            if (!empty($platform->error_message)) {
                                echo '<span class="text-danger" style="word-break: break-word;">' . htmlspecialchars($platform->error_message) . '</span>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
            </table>
            </div>
        </div>
        <?php
    }

    /**
     * Process scheduled posts (called by cron via web)
     */
    public function process_scheduled()
    {
        // Security: Check secret key
        $secret = $this->input->get('secret');
        $expected_secret = 'sm_posters_cron_' . md5(APP_ENCRYPTION_KEY . 'sm_posters');
        
        if ($secret !== $expected_secret) {
            show_404();
            return;
        }

        // Prevent timeout
        set_time_limit(0);
        
        echo "<pre>";
        echo "========================================\n";
        echo "Social Media Posters - Cron Job\n";
        echo "Started at: " . date('Y-m-d H:i:s') . "\n";
        echo "========================================\n\n";

        // Run the cron job from model
        $stats = $this->sm_posters_model->run_scheduled_posts_cron();

        echo "Posts scanned: {$stats['scanned']}\n";
        echo "Posts due: {$stats['due']}\n";
        echo "Successfully posted: {$stats['success']}\n";
        echo "Failed: {$stats['failed']}\n";
        echo "Skipped: {$stats['skipped']}\n";

        echo "\n========================================\n";
        echo "Cron job completed at: " . date('Y-m-d H:i:s') . "\n";
        echo "========================================\n";
        echo "</pre>";
    }

    /**
     * Test cron manually (admins only)
     */
    public function test_cron()
    {
        if (!is_admin()) {
            show_404();
        }

        echo "<pre>";
        echo "Testing Cron Job...\n";
        echo "==================\n\n";

        $scheduled_posts = $this->sm_posters_model->get_due_posts();
        
        echo "Found " . count($scheduled_posts) . " scheduled posts\n\n";
        
        if (empty($scheduled_posts)) {
            echo "No posts to process.\n";
            echo "\nCreate a scheduled post with a past date/time to test.\n";
            echo "</pre>";
            return;
        }

        foreach ($scheduled_posts as $post) {
            echo "Post ID: {$post->id}\n";
            echo "Message: " . substr($post->message, 0, 50) . "...\n";
            echo "Scheduled: {$post->scheduled_at}\n";
            echo "Status: {$post->status}\n\n";
        }

        echo "\nTo process these posts, run the cron URL with the secret parameter.\n";
        echo "</pre>";
    }

    /**
 * Check specific post details
 */
public function check_instagram_post($media_id)
{
    if (!is_admin()) {
        show_404();
    }
    
    $connection = $this->sm_posters_model->get_connection('instagram', 1);
    
    if (!$connection) {
        echo "No connection";
        return;
    }
    
    echo "<h2>📸 Instagram Post Details</h2>";
    
    // Get detailed post info
    $url = 'https://graph.facebook.com/v18.0/' . $media_id . '?fields=id,media_type,media_url,thumbnail_url,permalink,caption,timestamp,like_count,comments_count,is_comment_enabled,media_product_type&access_token=' . $connection->access_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h3>API Response (HTTP {$http_code}):</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $result = json_decode($response, true);
    
    if (isset($result['permalink'])) {
        echo "<div style='background: #d4edda; padding: 20px; margin: 20px 0; border-radius: 5px;'>";
        echo "<h3>✅ Post Found!</h3>";
        echo "<strong>Post ID:</strong> " . $result['id'] . "<br>";
        echo "<strong>Type:</strong> " . $result['media_type'] . "<br>";
        echo "<strong>Posted:</strong> " . $result['timestamp'] . "<br>";
        echo "<strong>Product Type:</strong> " . ($result['media_product_type'] ?? 'N/A') . "<br>";
        echo "<br>";
        echo "<strong>🔗 Direct Link:</strong> <a href='" . $result['permalink'] . "' target='_blank' style='color: blue; font-size: 18px;'>" . $result['permalink'] . "</a><br>";
        echo "<br>";
        
        if (isset($result['media_url'])) {
            echo "<img src='" . $result['media_url'] . "' style='max-width: 300px; border: 2px solid #ddd; border-radius: 5px;'>";
        }
        
        echo "</div>";
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px;'>";
        echo "<strong>⚠️ If you can't see this on your profile:</strong><br>";
        echo "1. The post may be pending review by Instagram<br>";
        echo "2. Your account might need to be verified<br>";
        echo "3. The post might be in 'Content you shared' instead of main feed<br>";
        echo "4. Try clicking the permalink above to view it directly<br>";
        echo "</div>";
    }
}


    public function debug_instagram()
{
    if (!is_admin()) {
        show_404();
    }
    
    // Get your Instagram connection
    $connection = $this->sm_posters_model->get_connection('instagram', 1);
    
    if (!$connection) {
        echo "<h2>❌ No Instagram connection found</h2>";
        return;
    }
    
    echo "<h2>🔍 Instagram Connection Debug</h2>";
    echo "<div style='background: #f5f5f5; padding: 20px; border-radius: 5px; font-family: monospace;'>";
    
    echo "<h3>Database Info:</h3>";
    echo "<strong>Account ID:</strong> " . $connection->account_id . "<br>";
    echo "<strong>Account Name:</strong> " . $connection->account_name . "<br>";
    echo "<strong>Client:</strong> " . ($connection->company ?? 'N/A') . "<br>";
    echo "<br>";
    
    // Get actual Instagram account info from API
    $url = 'https://graph.facebook.com/v18.0/' . $connection->account_id . '?fields=id,username,name,profile_picture_url&access_token=' . $connection->access_token;
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<h3>API Response (HTTP {$http_code}):</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    $result = json_decode($response, true);
    
    if (isset($result['username'])) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<h3>✅ Connected Instagram Account:</h3>";
        echo "<strong>Username:</strong> @" . $result['username'] . "<br>";
        echo "<strong>Name:</strong> " . $result['name'] . "<br>";
        echo "<strong>Account ID:</strong> " . $result['id'] . "<br>";
        echo "<br>";
        echo "<strong>🔗 View Profile:</strong> <a href='https://instagram.com/" . $result['username'] . "' target='_blank'>https://instagram.com/" . $result['username'] . "</a><br>";
        echo "</div>";
        
        // Get recent media
        echo "<h3>Recent Posts:</h3>";
        $media_url = 'https://graph.facebook.com/v18.0/' . $connection->account_id . '/media?fields=id,caption,media_type,media_url,thumbnail_url,permalink,timestamp&access_token=' . $connection->access_token;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $media_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $media_response = curl_exec($ch);
        curl_close($ch);
        
        $media_result = json_decode($media_response, true);
        
        if (isset($media_result['data']) && count($media_result['data']) > 0) {
            echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Post ID</th><th>Caption</th><th>Type</th><th>Posted</th><th>Link</th></tr>";
            
            foreach ($media_result['data'] as $post) {
                echo "<tr>";
                echo "<td>" . $post['id'] . "</td>";
                echo "<td>" . substr($post['caption'] ?? 'N/A', 0, 50) . "...</td>";
                echo "<td>" . $post['media_type'] . "</td>";
                echo "<td>" . date('Y-m-d H:i', strtotime($post['timestamp'])) . "</td>";
                echo "<td><a href='" . $post['permalink'] . "' target='_blank'>View</a></td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No posts found or error: <pre>" . htmlspecialchars($media_response) . "</pre></p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Error:</h3>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        echo "</div>";
    }
    
    echo "</div>";
}


}