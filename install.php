<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

// ============================================
// CREATE TABLES
// ============================================

// Table 1: Social Media Connections
if (!$CI->db->table_exists(db_prefix() . 'social_connections')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'social_connections` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `client_id` int(11) NOT NULL,
        `platform` enum("facebook","instagram","x","linkedin","tumblr","pinterest") NOT NULL,
        `account_name` varchar(255) DEFAULT NULL,
        `account_id` varchar(255) NOT NULL,
        `access_token` text NOT NULL,
        `refresh_token` text DEFAULT NULL,
        `token_expires_at` datetime DEFAULT NULL,
        `status` tinyint(1) NOT NULL DEFAULT 1,
        `created_by` int(11) NOT NULL,
        `created_at` datetime NOT NULL,
        `updated_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `client_id` (`client_id`),
        KEY `platform` (`platform`),
        KEY `status` (`status`),
        KEY `idx_platform_status` (`platform`, `status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

// Table 2: Social Media Posts
if (!$CI->db->table_exists(db_prefix() . 'social_posts')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'social_posts` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `client_id` int(11) NOT NULL,
        `message` text,
        `link` varchar(500) DEFAULT NULL,
        `media_type` enum("none","image","video") DEFAULT "none",
        `media_data` LONGBLOB DEFAULT NULL,
        `media_mime` varchar(100) DEFAULT NULL,
        `media_filename` varchar(255) DEFAULT NULL,
        `scheduled_at` datetime DEFAULT NULL,
        `is_scheduled` tinyint(1) NOT NULL DEFAULT 0,
        `status` enum("draft","scheduled","publishing","published","failed") DEFAULT "draft",
        `created_by` int(11) NOT NULL,
        `created_at` datetime NOT NULL,
        `published_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `client_id` (`client_id`),
        KEY `scheduled_at` (`scheduled_at`),
        KEY `is_scheduled` (`is_scheduled`),
        KEY `status` (`status`),
        KEY `idx_scheduled_status` (`is_scheduled`, `status`, `scheduled_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

// Table 3: Post Platforms (Many-to-Many)
if (!$CI->db->table_exists(db_prefix() . 'post_platforms')) {
    $CI->db->query('CREATE TABLE `' . db_prefix() . 'post_platforms` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `post_id` int(11) NOT NULL,
        `connection_id` int(11) NOT NULL,
        `platform` enum("facebook","instagram","x","linkedin","tumblr","pinterest") NOT NULL,
        `platform_post_id` varchar(255) DEFAULT NULL,
        `status` enum("pending","published","failed") DEFAULT "pending",
        `error_message` text,
        `published_at` datetime DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `post_id` (`post_id`),
        KEY `connection_id` (`connection_id`),
        KEY `platform` (`platform`),
        KEY `status` (`status`),
        KEY `idx_post_platform` (`post_id`, `platform`),
        CONSTRAINT `fk_post_platforms_post` FOREIGN KEY (`post_id`) REFERENCES `' . db_prefix() . 'social_posts` (`id`) ON DELETE CASCADE,
        CONSTRAINT `fk_post_platforms_connection` FOREIGN KEY (`connection_id`) REFERENCES `' . db_prefix() . 'social_connections` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=' . $CI->db->char_set . ';');
}

// ============================================
// MIGRATE OLD DATA (if exists)
// ============================================

// Migrate from old fb_connections table
if ($CI->db->table_exists(db_prefix() . 'fb_connections')) {
    $old_connections = $CI->db->get(db_prefix() . 'fb_connections')->result();
    
    if (!empty($old_connections)) {
        foreach ($old_connections as $old_conn) {
            // Check if already migrated
            $exists = $CI->db->where('platform', 'facebook')
                             ->where('account_id', $old_conn->page_id)
                             ->get(db_prefix() . 'social_connections')
                             ->row();
            
            if (!$exists) {
                $CI->db->insert(db_prefix() . 'social_connections', [
                    'client_id' => $old_conn->client_id,
                    'platform' => 'facebook',
                    'account_name' => $old_conn->page_name,
                    'account_id' => $old_conn->page_id,
                    'access_token' => $old_conn->access_token,
                    'status' => $old_conn->status,
                    'created_by' => $old_conn->created_by,
                    'created_at' => $old_conn->created_at,
                    'updated_at' => $old_conn->updated_at
                ]);
            }
        }
    }
}

// Migrate from old fb_posts table
if ($CI->db->table_exists(db_prefix() . 'fb_posts')) {
    $old_posts = $CI->db->get(db_prefix() . 'fb_posts')->result();
    
    if (!empty($old_posts)) {
        foreach ($old_posts as $old_post) {
            // Check if already migrated
            $exists = $CI->db->where('id', $old_post->id)
                             ->get(db_prefix() . 'social_posts')
                             ->row();
            
            if (!$exists) {
                // Get client_id from connection
                $connection = $CI->db->where('id', $old_post->connection_id)
                                     ->get(db_prefix() . 'fb_connections')
                                     ->row();
                
                $client_id = $connection ? $connection->client_id : 0;
                
                // Insert post
                $CI->db->insert(db_prefix() . 'social_posts', [
                    'id' => $old_post->id,
                    'client_id' => $client_id,
                    'message' => $old_post->message,
                    'link' => isset($old_post->link) ? $old_post->link : null,
                    'media_type' => isset($old_post->media_type) ? $old_post->media_type : 'none',
                    'media_data' => isset($old_post->media_data) ? $old_post->media_data : null,
                    'media_mime' => isset($old_post->media_mime) ? $old_post->media_mime : null,
                    'media_filename' => isset($old_post->media_filename) ? $old_post->media_filename : null,
                    'status' => $old_post->status == 'published' ? 'published' : 'failed',
                    'created_by' => $old_post->posted_by,
                    'created_at' => $old_post->posted_at,
                    'published_at' => $old_post->posted_at
                ]);
                
                $post_id = $old_post->id;
                
                // Get new connection ID from migrated connections
                $new_connection = $CI->db->where('platform', 'facebook')
                                         ->where('client_id', $client_id)
                                         ->get(db_prefix() . 'social_connections')
                                         ->row();
                
                if ($new_connection) {
                    // Insert post platform
                    $CI->db->insert(db_prefix() . 'post_platforms', [
                        'post_id' => $post_id,
                        'connection_id' => $new_connection->id,
                        'platform' => 'facebook',
                        'platform_post_id' => isset($old_post->fb_post_id) ? $old_post->fb_post_id : null,
                        'status' => $old_post->status == 'published' ? 'published' : 'failed',
                        'error_message' => isset($old_post->error_message) ? $old_post->error_message : null,
                        'published_at' => $old_post->posted_at
                    ]);
                }
            }
        }
    }
}

// ============================================
// CREATE DIRECTORIES
// ============================================

$directories = [
    FCPATH . 'uploads/sm_posters',
    FCPATH . 'uploads/temp'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// ============================================
// SET MODULE OPTIONS
// ============================================

$options = [
    'sm_posters_version' => '2.0.0',
    'sm_posters_installed' => date('Y-m-d H:i:s'),
    'sm_posters_cron_enabled' => '1',
    'sm_posters_cron_last_run' => '', // Empty string instead of null
];

foreach ($options as $key => $value) {
    $exists = $CI->db->where('name', $key)->get(db_prefix() . 'options')->row();
    
    if (!$exists) {
        $CI->db->insert(db_prefix() . 'options', [
            'name' => $key,
            'value' => $value,
            'autoload' => 1
        ]);
    }
}

// ============================================
// CREATE CRON FILE
// ============================================

$cron_file = SM_POSTERS_PATH . 'cron.php';
if (!file_exists($cron_file)) {
    $cron_content = '<?php
/**
 * Social Media Posters - Cron Job Handler
 * 
 * Add to crontab:
 * */5 * * * * /usr/bin/php ' . FCPATH . 'modules/sm_posters/cron.php >> ' . FCPATH . 'modules/sm_posters/cron.log 2>&1
 */

define(\'BASEPATH\', TRUE);
require_once(__DIR__ . \'/../../index.php\');

$CI =& get_instance();
$CI->load->model(\'sm_posters/sm_posters_model\');

// Get scheduled posts
$scheduled = $CI->sm_posters_model->get_due_posts();

if (!empty($scheduled)) {
    // Load controller and process
    require_once(__DIR__ . \'/controllers/Sm_posters.php\');
    $sm_posters = new Sm_posters();
    $sm_posters->process_scheduled();
    
    echo "[" . date(\'Y-m-d H:i:s\') . "] Processed " . count($scheduled) . " scheduled posts\n";
} else {
    echo "[" . date(\'Y-m-d H:i:s\') . "] No scheduled posts to process\n";
}

// Update last run time
$CI->db->where(\'name\', \'sm_posters_cron_last_run\');
$CI->db->update(db_prefix() . \'options\', [\'value\' => date(\'Y-m-d H:i:s\')]);
';
    
    file_put_contents($cron_file, $cron_content);
    chmod($cron_file, 0755);
}

// ============================================
// CREATE .htaccess FOR SECURITY
// ============================================

$htaccess_file = FCPATH . 'uploads/sm_posters/.htaccess';
if (!file_exists($htaccess_file)) {
    $htaccess_content = '# Deny direct access
<FilesMatch "\.(jpg|jpeg|png|gif|mp4|mov|avi)$">
    Order Allow,Deny
    Deny from all
</FilesMatch>
';
    file_put_contents($htaccess_file, $htaccess_content);
}

// ============================================
// LOG INSTALLATION
// ============================================

log_activity('Social Media Posters Module v2.0.0 Installed Successfully');