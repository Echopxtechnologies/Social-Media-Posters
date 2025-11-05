<?php
defined('BASEPATH') or exit('No direct script access allowed');

/*
Module Name: Social Media Posters
Description: Multi-platform social media manager - Post to Facebook, Instagram, X, LinkedIn, Tumblr, Pinterest with scheduling
Version: 2.0.0
Requires at least: 2.3.*
Author: EchoPx
*/

define('SM_POSTERS_MODULE', 'sm_posters');
define('SM_POSTERS_PATH', module_dir_path(SM_POSTERS_MODULE));
define('SM_POSTERS_URL',  module_dir_url(SM_POSTERS_MODULE));

// Register language files (if function exists in your Perfex version)
if (function_exists('register_language_files')) {
    register_language_files(SM_POSTERS_MODULE, ['sm_posters']);
}

// Hooks
hooks()->add_action('admin_init', 'sm_posters_admin_menu');
hooks()->add_action('admin_init', 'sm_posters_permissions');
//cron hook
hooks()->add_action('after_cron_run', 'sm_posters_after_cron_run');

// Lifecycle
register_activation_hook(SM_POSTERS_MODULE, 'sm_posters_activate');
register_deactivation_hook(SM_POSTERS_MODULE, 'sm_posters_deactivate');
register_uninstall_hook(SM_POSTERS_MODULE, 'sm_posters_uninstall');


/* -----------------------------------------------------------------------------
 |  CRON HOOK
 * ---------------------------------------------------------------------------*/


/**
 * Process scheduled posts via Perfex's built-in cron
 */
function sm_posters_after_cron_run()
{
    $CI = &get_instance();
    
    // Log cron ping
    log_activity('SM Posters: Cron ping');
    
    try {
        // Load model
        $CI->load->model('sm_posters/sm_posters_model');
        
        // Run the cron job
        $result = $CI->sm_posters_model->run_scheduled_posts_cron();
        
        // Log summary
        $summary = sprintf(
            'SM Posters Cron: scanned=%d, due=%d, success=%d, failed=%d, skipped=%d',
            $result['scanned'],
            $result['due'],
            $result['success'],
            $result['failed'],
            $result['skipped']
        );
        
        log_activity($summary);
        
        if ($result['success'] > 0 || $result['failed'] > 0) {
            log_message('info', '[SM_POSTERS] ' . $summary);
        }
        
    } catch (Throwable $e) {
        $error_msg = sprintf(
            'SM Posters Cron Exception: %s @%s:%s',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );
        
        log_activity($error_msg);
        log_message('error', '[SM_POSTERS] ' . $error_msg);
    }
}

/**
 * Add Admin sidebar menu
 */
function sm_posters_admin_menu()
{
    if (!has_permission('sm_posters', '', 'view')) {
        return;
    }

    $CI = &get_instance();
    
    // Main menu
    $CI->app_menu->add_sidebar_menu_item('sm_posters', [
        'name'     => 'Social Media',
        'href'     => admin_url('sm_posters'),
        'icon'     => 'fa fa-share-alt',
        'position' => 35,
    ]);
    
    // Submenus
    $CI->app_menu->add_sidebar_children_item('sm_posters', [
        'slug'     => 'sm_posters_dashboard',
        'name'     => 'Dashboard',
        'href'     => admin_url('sm_posters'),
        'icon'     => 'fa fa-dashboard',
        'position' => 1,
    ]);
    
    $CI->app_menu->add_sidebar_children_item('sm_posters', [
        'slug'     => 'sm_posters_create_post',
        'name'     => 'Create Post',
        'href'     => admin_url('sm_posters/create_post'),
        'icon'     => 'fa fa-plus',
        'position' => 2,
    ]);
    
    $CI->app_menu->add_sidebar_children_item('sm_posters', [
        'slug'     => 'sm_posters_posts',
        'name'     => 'Posts History',
        'href'     => admin_url('sm_posters/posts'),
        'icon'     => 'fa fa-history',
        'position' => 3,
    ]);
    
    $CI->app_menu->add_sidebar_children_item('sm_posters', [
        'slug'     => 'sm_posters_connections',
        'name'     => 'Connections',
        'href'     => admin_url('sm_posters/connections'),
        'icon'     => 'fa fa-plug',
        'position' => 4,
    ]);
    
    // $CI->app_menu->add_sidebar_children_item('sm_posters', [
    //     'slug'     => 'sm_posters_add_connection',
    //     'name'     => 'Add Connection',
    //     'href'     => admin_url('sm_posters/add_connection'),
    //     'icon'     => 'fa fa-plus-circle',
    //     'position' => 5,
    // ]);
}

/**
 * Register permissions
 */
function sm_posters_permissions()
{
    $capabilities = [
        'view'   => 'View Social Media',
        'create' => 'Create Posts',
        'edit'   => 'Edit Posts & Connections',
        'delete' => 'Delete Posts & Connections',
    ];

    register_staff_capabilities('sm_posters', $capabilities, 'Social Media Manager');
}

/**
 * Activation: create tables/options
 */
function sm_posters_activate()
{
    require_once SM_POSTERS_PATH . 'install.php';
}

/**
 * Deactivation: keep data (no-op)
 */
function sm_posters_deactivate()
{
    // Intentionally left empty - data is preserved
    log_activity('Social Media Posters Module Deactivated');
}

/**
 * Uninstall: drop tables/options
 */
function sm_posters_uninstall()
{
    require_once SM_POSTERS_PATH . 'uninstall.php';
}