<?php

defined('BASEPATH') or exit('No direct script access allowed');

$CI = &get_instance();

echo "\n";
echo "========================================\n";
echo "UNINSTALLING Social Media Posters\n";
echo "========================================\n";
echo "\n";

// ============================================
// ASK FOR CONFIRMATION
// ============================================

if (!defined('FORCE_UNINSTALL')) {
    echo "WARNING: This will permanently delete:\n";
    echo "  - All social media connections\n";
    echo "  - All posts and scheduled posts\n";
    echo "  - All uploaded media files\n";
    echo "  - All database tables and data\n";
    echo "\n";
    echo "This action CANNOT be undone!\n";
    echo "\n";
    
    // In CLI mode, ask for confirmation
    if (is_cli()) {
        echo "Type 'yes' to continue: ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim($line) != 'yes') {
            echo "Uninstall cancelled.\n";
            return;
        }
    }
}

// ============================================
// BACKUP DATA (OPTIONAL)
// ============================================

$backup_dir = FCPATH . 'uploads/sm_posters_backup_' . date('Y-m-d_H-i-s');

if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Backup connections
$connections = $CI->db->get(db_prefix() . 'social_connections')->result_array();
if (!empty($connections)) {
    file_put_contents($backup_dir . '/connections.json', json_encode($connections, JSON_PRETTY_PRINT));
    echo "✓ Backed up " . count($connections) . " connections\n";
}

// Backup posts (without media data - too large)
$posts = $CI->db->select('id, client_id, message, link, media_type, media_mime, media_filename, scheduled_at, is_scheduled, status, created_by, created_at, published_at')
                ->get(db_prefix() . 'social_posts')
                ->result_array();
if (!empty($posts)) {
    file_put_contents($backup_dir . '/posts.json', json_encode($posts, JSON_PRETTY_PRINT));
    echo "✓ Backed up " . count($posts) . " posts\n";
}

// Backup post platforms
$platforms = $CI->db->get(db_prefix() . 'post_platforms')->result_array();
if (!empty($platforms)) {
    file_put_contents($backup_dir . '/post_platforms.json', json_encode($platforms, JSON_PRETTY_PRINT));
    echo "✓ Backed up " . count($platforms) . " post platforms\n";
}

echo "✓ Backup created at: $backup_dir\n\n";

// ============================================
// DROP FOREIGN KEYS FIRST
// ============================================

$CI->db->query('SET FOREIGN_KEY_CHECKS = 0');

// ============================================
// DROP TABLES
// ============================================

$tables = [
    db_prefix() . 'post_platforms',
    db_prefix() . 'social_posts',
    db_prefix() . 'social_connections',
    // Old tables (if they exist)
    db_prefix() . 'fb_posts',
    db_prefix() . 'fb_connections'
];

foreach ($tables as $table) {
    if ($CI->db->table_exists($table)) {
        $CI->db->query('DROP TABLE IF EXISTS `' . $table . '`');
        echo "✓ Dropped table: $table\n";
    }
}

$CI->db->query('SET FOREIGN_KEY_CHECKS = 1');

// ============================================
// DELETE OPTIONS
// ============================================

$options = [
    'sm_posters_version',
    'sm_posters_installed',
    'sm_posters_cron_enabled',
    'sm_posters_cron_last_run'
];

foreach ($options as $option) {
    $CI->db->where('name', $option);
    $CI->db->delete(db_prefix() . 'options');
}

echo "✓ Deleted module options\n";

// ============================================
// DELETE DIRECTORIES (OPTIONAL)
// ============================================

echo "\nDo you want to delete uploaded files? (yes/no): ";

$delete_files = false;

if (is_cli()) {
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    $delete_files = (trim($line) == 'yes');
} else {
    // In web mode, don't delete by default
    $delete_files = false;
}

if ($delete_files) {
    $directories = [
        FCPATH . 'uploads/sm_posters/images',
        FCPATH . 'uploads/sm_posters/videos',
        FCPATH . 'uploads/sm_posters',
        FCPATH . 'uploads/temp'
    ];

    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            // Delete all files in directory
            $files = glob($dir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            
            // Remove directory
            @rmdir($dir);
            echo "✓ Deleted directory: $dir\n";
        }
    }
}

// ============================================
// DELETE CRON FILE
// ============================================

$cron_file = SM_POSTERS_PATH . 'cron.php';
if (file_exists($cron_file)) {
    unlink($cron_file);
    echo "✓ Deleted cron file\n";
}

$cron_log = SM_POSTERS_PATH . 'cron.log';
if (file_exists($cron_log)) {
    unlink($cron_log);
    echo "✓ Deleted cron log\n";
}

// ============================================
// LOG UNINSTALLATION
// ============================================

log_activity('Social Media Posters Module Uninstalled');

echo "\n";
echo "========================================\n";
echo "✓ Uninstallation Complete!\n";
echo "========================================\n";
echo "\n";
echo "Important:\n";
echo "1. Remove cron job from crontab\n";
echo "2. Data backup saved at: $backup_dir\n";
echo "3. You can safely delete the module folder\n";
echo "\n";