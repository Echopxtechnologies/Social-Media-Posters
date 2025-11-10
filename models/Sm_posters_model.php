<?php

defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Social Media Poster Model
 * Handles connections and posts across multiple platforms
 * Each platform has its own dedicated table
 */
class Sm_posters_model extends App_Model
{
    // Post-related tables (still unified)
    private $posts_table;
    private $post_platforms_table;
    
    // Platform list
    private $platforms = ['facebook', 'instagram', 'x', 'linkedin', 'tumblr', 'pinterest'];

    public function __construct()
    {
        parent::__construct();
        
        // Posts tables remain unified
        $this->posts_table = db_prefix() . 'social_posts';
        $this->post_platforms_table = db_prefix() . 'post_platforms';
    }

    /**
     * Get table name for a specific platform
     * 
     * @param string $platform Platform identifier
     * @return string Full table name with prefix
     */
    private function table_for_platform($platform)
    {
        return db_prefix() . $platform . '_connections';
    }

    /**
     * Validate platform name
     * 
     * @param string $platform
     * @return bool
     */
    private function is_valid_platform($platform)
    {
        return in_array(strtolower($platform), $this->platforms);
    }

    // ============================================
    // CONNECTION METHODS
    // ============================================

    /**
     * Get all connections across all platforms
     * Returns unified result set with platform identifier
     * 
     * @return array Array of connection objects
     */
    public function get_all_connections()
    {
        $all_connections = [];
        
        foreach ($this->platforms as $platform) {
            $table = $this->table_for_platform($platform);
            
            // Check if table exists first
            if (!$this->db->table_exists($table)) {
                continue;
            }
            
            $this->db->select($table . '.*, ' . db_prefix() . 'clients.company');
            $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . $table . '.client_id', 'left');
            $this->db->order_by($table . '.created_at', 'DESC');
            
            $connections = $this->db->get($table)->result();
            
            // Add platform identifier to each connection
            foreach ($connections as $connection) {
                $connection->platform = $platform;
                $all_connections[] = $connection;
            }
        }
        
        // Sort by created_at DESC across all platforms
        usort($all_connections, function($a, $b) {
            return strtotime($b->created_at) - strtotime($a->created_at);
        });
        
        return $all_connections;
    }

    /**
     * Get active connections only (status = 1)
     * 
     * @return array Array of active connection objects
     */
    public function get_active_connections()
    {
        $active_connections = [];
        
        foreach ($this->platforms as $platform) {
            $table = $this->table_for_platform($platform);
            
            if (!$this->db->table_exists($table)) {
                continue;
            }
            
            $this->db->select($table . '.*, ' . db_prefix() . 'clients.company');
            $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . $table . '.client_id', 'left');
            $this->db->where($table . '.status', 1);
            $this->db->order_by($table . '.created_at', 'DESC');
            
            $connections = $this->db->get($table)->result();
            
            // Add platform identifier
            foreach ($connections as $connection) {
                $connection->platform = $platform;
                $active_connections[] = $connection;
            }
        }
        
        return $active_connections;
    }

    /**
     * Get single connection by platform and ID
     * 
     * @param string $platform Platform name (facebook, instagram, etc.)
     * @param int $id Connection ID
     * @return object|null Connection object or null
     */
    public function get_connection($platform, $id)
    {
        if (!$this->is_valid_platform($platform)) {
            return null;
        }
        
        $table = $this->table_for_platform($platform);
        
        if (!$this->db->table_exists($table)) {
            return null;
        }
        
        $this->db->select($table . '.*, ' . db_prefix() . 'clients.company');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . $table . '.client_id', 'left');
        $this->db->where($table . '.id', $id);
        
        $connection = $this->db->get($table)->row();
        
        // Add platform identifier
        if ($connection) {
            $connection->platform = $platform;
        }
        
        return $connection;
    }

    /**
     * Get connections by platform only
     * 
     * @param string $platform
     * @return array
     */
    public function get_connections_by_platform($platform)
    {
        if (!$this->is_valid_platform($platform)) {
            return [];
        }
        
        $table = $this->table_for_platform($platform);
        
        if (!$this->db->table_exists($table)) {
            return [];
        }
        
        $this->db->select($table . '.*, ' . db_prefix() . 'clients.company');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . $table . '.client_id', 'left');
        $this->db->order_by($table . '.created_at', 'DESC');
        
        $connections = $this->db->get($table)->result();
        
        // Add platform identifier
        foreach ($connections as $connection) {
            $connection->platform = $platform;
        }
        
        return $connections;
    }

    /**
     * Add new connection to platform-specific table
     * 
     * @param string $platform Platform name
     * @param array $data Connection data (without 'platform' key)
     * @return int|false Insert ID on success, false on failure
     */
    public function add_connection($platform, $data)
    {
        if (!$this->is_valid_platform($platform)) {
            log_message('error', '[SM_POSTERS] Invalid platform: ' . $platform);
            return false;
        }
        
        $table = $this->table_for_platform($platform);
        
        if (!$this->db->table_exists($table)) {
            log_message('error', '[SM_POSTERS] Table does not exist: ' . $table);
            return false;
        }
        
        // Add metadata
        $data['created_by'] = get_staff_user_id();
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Remove 'platform' key if present (no longer stored in individual tables)
        unset($data['platform']);
        
        $this->db->insert($table, $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Social Media Connection Added [Platform: ' . ucfirst($platform) . ', ID: ' . $insert_id . ']');
            return $insert_id;
        }

        return false;
    }

    /**
     * Update connection
     * 
     * @param string $platform
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_connection($platform, $id, $data)
    {
        if (!$this->is_valid_platform($platform)) {
            return false;
        }
        
        $table = $this->table_for_platform($platform);
        
        if (!$this->db->table_exists($table)) {
            return false;
        }
        
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // Remove 'platform' key if present
        unset($data['platform']);
        
        $this->db->where('id', $id);
        $this->db->update($table, $data);

        if ($this->db->affected_rows() > 0) {
            log_activity('Social Media Connection Updated [Platform: ' . ucfirst($platform) . ', ID: ' . $id . ']');
            return true;
        }

        return false;
    }

    /**
     * Delete connection
     * 
     * @param string $platform
     * @param int $id
     * @return bool
     */
    public function delete_connection($platform, $id)
    {
        if (!$this->is_valid_platform($platform)) {
            return false;
        }
        
        $table = $this->table_for_platform($platform);
        
        if (!$this->db->table_exists($table)) {
            return false;
        }
        
        $this->db->where('id', $id);
        $this->db->delete($table);

        if ($this->db->affected_rows() > 0) {
            log_activity('Social Media Connection Deleted [Platform: ' . ucfirst($platform) . ', ID: ' . $id . ']');
            return true;
        }

        return false;
    }

    /**
     * Get platform connection (alias for get_connection with better naming)
     * Used by cron and posting logic
     * 
     * @param string $platform
     * @param int $id
     * @return object|null
     */
    public function get_platform_connection($platform, $id)
    {
        return $this->get_connection($platform, $id);
    }

    // ============================================
    // POST METHODS
    // ============================================

    /**
     * Get all posts
     * 
     * @param int|null $limit
     * @param int $offset
     * @return array
     */
    public function get_all_posts($limit = null, $offset = 0)
    {
        $this->db->select($this->posts_table . '.*, ' . db_prefix() . 'clients.company');
        $this->db->join(db_prefix() . 'clients', db_prefix() . 'clients.userid = ' . $this->posts_table . '.client_id', 'left');
        $this->db->order_by($this->posts_table . '.created_at', 'DESC');
        
        if ($limit) {
            $this->db->limit($limit, $offset);
        }
        
        return $this->db->get($this->posts_table)->result();
    }

    /**
     * Get post by ID
     * 
     * @param int $id
     * @return object|null
     */
    public function get_post($id)
    {
        $this->db->where('id', $id);
        return $this->db->get($this->posts_table)->row();
    }

    /**
     * Add post
     * 
     * @param array $data
     * @return int|false
     */
    public function add_post($data)
    {
        $data['created_by'] = get_staff_user_id();
        $data['created_at'] = date('Y-m-d H:i:s');
        
        $this->db->insert($this->posts_table, $data);
        $insert_id = $this->db->insert_id();

        if ($insert_id) {
            log_activity('New Social Media Post Created [ID: ' . $insert_id . ']');
            return $insert_id;
        }

        return false;
    }

    /**
     * Update post
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_post($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update($this->posts_table, $data);

        return $this->db->affected_rows() > 0;
    }

    /**
     * Delete post
     * 
     * @param int $id
     * @return bool
     */
    public function delete_post($id)
    {
        // Delete post platforms first
        $this->db->where('post_id', $id);
        $this->db->delete($this->post_platforms_table);

        // Delete main post
        $this->db->where('id', $id);
        $this->db->delete($this->posts_table);

        if ($this->db->affected_rows() > 0) {
            log_activity('Social Media Post Deleted [ID: ' . $id . ']');
            return true;
        }

        return false;
    }

    // ============================================
    // POST PLATFORM METHODS
    // ============================================

    /**
     * Get platforms for a post
     * 
     * @param int $post_id
     * @return array
     */
    public function get_post_platforms($post_id)
    {
        $this->db->select($this->post_platforms_table . '.*');
        $this->db->where($this->post_platforms_table . '.post_id', $post_id);
        
        return $this->db->get($this->post_platforms_table)->result();
    }

    /**
     * Add post platform record
     * 
     * @param array $data Must include: post_id, connection_id, platform
     * @return int|false
     */
    public function add_post_platform($data)
    {
        $this->db->insert($this->post_platforms_table, $data);
        return $this->db->insert_id();
    }

    /**
     * Update post platform record
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function update_post_platform($id, $data)
    {
        $this->db->where('id', $id);
        $this->db->update($this->post_platforms_table, $data);
        
        return $this->db->affected_rows() > 0;
    }

    // ============================================
    // DASHBOARD STATS
    // ============================================

    /**
     * Get dashboard statistics across all platforms
     * 
     * @return array
     */
    public function get_dashboard_stats()
    {
        $stats = [];

        // Total and active connections per platform
        $stats['total_connections'] = 0;
        $stats['active_connections'] = 0;
        $stats['platforms'] = [];
        
        foreach ($this->platforms as $platform) {
            $table = $this->table_for_platform($platform);
            
            if (!$this->db->table_exists($table)) {
                continue;
            }
            
            // Total for this platform
            $total = $this->db->count_all_results($table);
            $stats['total_connections'] += $total;
            
            // Active for this platform
            $this->db->where('status', 1);
            $active = $this->db->count_all_results($table);
            $stats['active_connections'] += $active;
            
            // Store per-platform count
            $stats['platforms'][$platform] = $active;
        }

        // Posts stats
        $stats['total_posts'] = $this->db->count_all_results($this->posts_table);

        $this->db->where('status', 'published');
        $stats['published_posts'] = $this->db->count_all_results($this->posts_table);

        $this->db->where('status', 'scheduled');
        $this->db->where('scheduled_at >', date('Y-m-d H:i:s'));
        $stats['scheduled_posts'] = $this->db->count_all_results($this->posts_table);

        $this->db->where('status', 'failed');
        $stats['failed_posts'] = $this->db->count_all_results($this->posts_table);

        $this->db->where('MONTH(created_at)', date('m'));
        $this->db->where('YEAR(created_at)', date('Y'));
        $stats['posts_this_month'] = $this->db->count_all_results($this->posts_table);

        // Recent posts
        $this->db->limit(5);
        $this->db->order_by('created_at', 'DESC');
        $stats['recent_posts'] = $this->db->get($this->posts_table)->result();

        return $stats;
    }

    // ============================================
    // CRON / SCHEDULED POSTING
    // ============================================

    /**
     * Get scheduled posts that are due for publishing
     * 
     * @return array
     */
    public function get_due_posts()
    {
        $this->db->where('is_scheduled', 1);
        $this->db->where('status', 'scheduled');
        $this->db->where('scheduled_at <=', date('Y-m-d H:i:s'));
        $this->db->order_by('scheduled_at', 'ASC');
        
        return $this->db->get($this->posts_table)->result();
    }

    /**
     * Run scheduled posts cron job
     * Called by Perfex's cron or manual trigger
     * 
     * @return array Statistics about the cron run
     */
    public function run_scheduled_posts_cron()
    {
        $CI = &get_instance();
        
        // Load platform models
        $CI->load->model('sm_posters/facebook_model');
        $CI->load->model('sm_posters/instagram_model');
        $CI->load->model('sm_posters/x_model');
        $CI->load->model('sm_posters/linkedin_model');
        $CI->load->model('sm_posters/tumblr_model');
        $CI->load->model('sm_posters/pinterest_model');
        
        // Initialize counters
        $scanned = 0;
        $due = 0;
        $success = 0;
        $failed = 0;
        $skipped = 0;
        
        // Get all scheduled posts
        $scheduled_posts = $this->get_due_posts();
        $scanned = count($scheduled_posts);
        
        if (empty($scheduled_posts)) {
            return compact('scanned', 'due', 'success', 'failed', 'skipped');
        }
        
        // Process each scheduled post
        foreach ($scheduled_posts as $post) {
            $due++;
            
            // Update status to publishing
            $this->update_post($post->id, ['status' => 'publishing']);
            
            // Get platforms for this post
            $platforms = $this->get_post_platforms($post->id);
            
            if (empty($platforms)) {
                $skipped++;
                $this->update_post($post->id, ['status' => 'failed']);
                log_message('error', "[SM_POSTERS] Post {$post->id} has no platforms");
                continue;
            }
            
            $post_success_count = 0;
            $post_fail_count = 0;
            
            // Post to each platform
            foreach ($platforms as $platform_record) {
                // CRITICAL: Get connection from correct platform table
                $connection = $this->get_platform_connection(
                    $platform_record->platform, 
                    $platform_record->connection_id
                );
                
                if (!$connection || $connection->status != 1) {
                    $post_fail_count++;
                    $this->update_post_platform($platform_record->id, [
                        'status' => 'failed',
                        'error_message' => 'Connection inactive or not found'
                    ]);
                    continue;
                }
                
                // Create temp file if media exists
                $media_path = null;
                if (!empty($post->media_data)) {
                    $media_path = $this->_create_temp_file_from_base64(
                        $post->media_data,
                        $post->media_mime,
                        $post->media_filename
                    );
                }
                
                // Post to platform
                $result = $this->_post_to_platform(
                    $platform_record->platform,
                    $connection,
                    $post->message,
                    $post->link,
                    $media_path,
                    $CI
                );
                
                // Delete temp file
                if ($media_path && file_exists($media_path)) {
                    unlink($media_path);
                }
                
                // Update platform record
                $this->update_post_platform($platform_record->id, [
                    'platform_post_id' => $result['success'] ? $result['post_id'] : null,
                    'status' => $result['success'] ? 'published' : 'failed',
                    'error_message' => !$result['success'] ? $result['error'] : null,
                    'published_at' => $result['success'] ? date('Y-m-d H:i:s') : null
                ]);
                
                if ($result['success']) {
                    $post_success_count++;
                    log_message('info', sprintf(
                        '[SM_POSTERS] Post=%d Platform=%s Success PostID=%s',
                        $post->id,
                        $platform_record->platform,
                        $result['post_id']
                    ));
                } else {
                    $post_fail_count++;
                    log_message('error', sprintf(
                        '[SM_POSTERS] Post=%d Platform=%s Failed Error=%s',
                        $post->id,
                        $platform_record->platform,
                        $result['error']
                    ));
                }
            }
            
            // Update main post status
            $final_status = ($post_fail_count > 0 && $post_success_count == 0) ? 'failed' : 'published';
            $this->update_post($post->id, [
                'status' => $final_status,
                'published_at' => date('Y-m-d H:i:s')
            ]);
            
            if ($post_success_count > 0) {
                $success++;
            } else {
                $failed++;
            }
            
            // Log detailed activity
            log_activity(sprintf(
                'Scheduled post published [Post ID: %d, Success: %d, Failed: %d]',
                $post->id,
                $post_success_count,
                $post_fail_count
            ));
        }
        
        // Update last run time
        $CI->db->where('name', 'sm_posters_cron_last_run');
        $CI->db->update(db_prefix() . 'options', ['value' => date('Y-m-d H:i:s')]);
        
        return compact('scanned', 'due', 'success', 'failed', 'skipped');
    }

    /**
     * Create temporary file from base64 data
     * 
     * @param string $base64_data
     * @param string $mime_type
     * @param string $filename
     * @return string Temp file path
     */
    private function _create_temp_file_from_base64($base64_data, $mime_type, $filename)
    {
        $binary_data = base64_decode($base64_data);
        
        $temp_dir = FCPATH . 'uploads/temp/';
        if (!is_dir($temp_dir)) {
            mkdir($temp_dir, 0755, true);
        }
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $temp_filename = 'cron_temp_' . uniqid() . '_' . time() . '.' . $extension;
        $temp_path = $temp_dir . $temp_filename;
        
        file_put_contents($temp_path, $binary_data);
        
        return $temp_path;
    }

    /**
     * Post to specific platform - routes to correct model
     * 
     * @param string $platform
     * @param object $connection
     * @param string $message
     * @param string|null $link
     * @param string|null $media_path
     * @param object $CI CodeIgniter instance
     * @return array ['success' => bool, 'post_id' => string, 'error' => string]
     */
    private function _post_to_platform($platform, $connection, $message, $link = null, $media_path = null, $CI)
    {
        try {
            switch ($platform) {
                case 'facebook':
                    return $CI->facebook_model->_post_to_facebook($connection, $message, $link, $media_path);
                
                case 'instagram':
                    return $CI->instagram_model->post_to_instagram($connection, $message, $media_path);
                
                case 'x':
                    return $CI->x_model->post_to_x($connection, $message, $media_path);
                
                case 'linkedin':
                    return $CI->linkedin_model->post_to_linkedin($connection, $message, $link, $media_path);
                
                case 'tumblr':
                // CRITICAL FIX: Tumblr requires credentials array with blog_name
                $tumblr_credentials = [
                    'consumer_key' => $connection->consumer_key,
                    'consumer_secret' => $connection->consumer_secret,
                    'oauth_token' => $connection->oauth_token,
                    'oauth_token_secret' => $connection->oauth_token_secret,
                    'blog_name' => $connection->account_id  // Map account_id to blog_name
                ];
                
                $media = !empty($media_path) ? [$media_path] : [];
                return $CI->tumblr_model->post_to_tumblr($tumblr_credentials, $message, $media);
                
                case 'pinterest':
                    return $CI->pinterest_model->post_to_pinterest($connection, $message, $link, $media_path);
                
                default:
                    return [
                        'success' => false,
                        'error' => 'Unknown platform: ' . $platform
                    ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
}