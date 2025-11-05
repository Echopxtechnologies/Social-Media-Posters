<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once(FCPATH . 'vendor/autoload.php');
use Abraham\TwitterOAuth\TwitterOAuth;

/**
 * X (Twitter) Model - Updated for X API v2
 * 
 * Requirements:
 * - composer require abraham/twitteroauth
 * - X API Basic tier or higher ($100/month minimum)
 * - App must have Read and Write permissions
 * 
 * Free tier CANNOT post - it's read-only!
 */
class X_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Post to X (Twitter) using API v2
     * 
     * @param array|object $credentials Must contain api_key, api_secret, access_token, access_token_secret
     * @param string $content Tweet text (max 280 characters)
     * @param array $media Array of media file paths (Basic tier required for media)
     * @return array ['success' => bool, 'post_id' => string|null, 'error' => string|null, 'message' => string]
     */
    public function post_to_x($credentials, $content, $media = [])
    {
        try {
            // Convert credentials to array if object
            if (is_object($credentials)) {
                $credentials = (array) $credentials;
            }

            // Validate required credentials
            $required = ['api_key', 'api_secret', 'access_token', 'access_token_secret'];
            foreach ($required as $field) {
                if (empty($credentials[$field])) {
                    return [
                        'success' => false,
                        'message' => "Missing required credential: {$field}",
                        'error' => "Missing required credential: {$field}",
                        'post_id' => null
                    ];
                }
            }

            // Initialize TwitterOAuth
            $connection = new TwitterOAuth(
                $credentials['api_key'],
                $credentials['api_secret'],
                $credentials['access_token'],
                $credentials['access_token_secret']
            );
            
            // Set API version to 2 (important!)
            $connection->setApiVersion('2');
            
            // Set timeouts
            $connection->setTimeouts(10, 30);
            
            // Limit content to 280 characters
            $tweet_text = mb_substr($content, 0, 280);
            
            log_message('info', '[X_MODEL] Posting to X - Text length: ' . mb_strlen($tweet_text) . ', Has media: ' . (empty($media) ? 'No' : 'Yes'));
            
            // Filter valid media files
            $valid_media = array_filter($media, function($file) {
                return !empty($file) && file_exists($file) && is_readable($file);
            });

            // If no media, post text only
            if (empty($valid_media)) {
                return $this->post_text_only_v2($connection, $tweet_text);
            }

            // Post with media (requires Basic tier or higher)
            return $this->post_with_media_v2($connection, $tweet_text, $valid_media);
            
        } catch (Exception $e) {
            log_message('error', '[X_MODEL] Exception: ' . $e->getMessage());
            log_message('error', '[X_MODEL] Trace: ' . $e->getTraceAsString());
            
            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'error' => 'Exception: ' . $e->getMessage(),
                'post_id' => null
            ];
        }
    }
    
    /**
     * Post text-only tweet using API v2
     */
    private function post_text_only_v2($connection, $text)
    {
        // API v2 endpoint for creating tweets
        $endpoint = 'tweets';
        
        $parameters = [
            'text' => $text
        ];
        
        log_message('info', '[X_MODEL] Posting text to X API v2');
        
        // Use API v2
        $connection->setApiVersion('2');
        $result = $connection->post($endpoint, $parameters);
        $http_code = $connection->getLastHttpCode();
        
        log_message('info', '[X_MODEL] Post response - HTTP: ' . $http_code);
        log_message('info', '[X_MODEL] Response body: ' . json_encode($result));
        
        // Success response
        if ($http_code == 201 && isset($result->data->id)) {
            log_message('info', '[X_MODEL] Post successful - ID: ' . $result->data->id);
            return [
                'success' => true,
                'message' => 'Posted successfully to X',
                'post_id' => $result->data->id,
                'error' => null
            ];
        }
        
        // Parse error
        $error_message = $this->parse_x_api_error($result, $http_code);
        log_message('error', '[X_MODEL] Post failed: ' . $error_message);
        
        return [
            'success' => false,
            'message' => $error_message,
            'error' => $error_message,
            'post_id' => null
        ];
    }
    
    /**
     * Post tweet with media using API v2
     */
    private function post_with_media_v2($connection, $text, $media)
    {
        $media_ids = [];
        
        // Upload each media file using v1.1 endpoint (media upload still uses v1.1)
        foreach ($media as $media_file) {
            log_message('info', '[X_MODEL] Uploading media: ' . basename($media_file));
            
            $upload_result = $this->upload_media_v1($connection, $media_file);
            
            if ($upload_result['success']) {
                $media_ids[] = $upload_result['media_id'];
                log_message('info', '[X_MODEL] Media uploaded: ' . $upload_result['media_id']);
            } else {
                return [
                    'success' => false,
                    'message' => $upload_result['error'],
                    'error' => $upload_result['error'],
                    'post_id' => null
                ];
            }
            
            // Twitter allows max 4 images or 1 video
            if (count($media_ids) >= 4) {
                break;
            }
        }
        
        if (empty($media_ids)) {
            return [
                'success' => false,
                'message' => 'No media uploaded successfully',
                'error' => 'No media uploaded successfully',
                'post_id' => null
            ];
        }
        
        // Post tweet with media using API v2
        $connection->setApiVersion('2');
        
        $parameters = [
            'text' => $text,
            'media' => [
                'media_ids' => $media_ids
            ]
        ];
        
        log_message('info', '[X_MODEL] Posting tweet with media to API v2');
        
        $result = $connection->post('tweets', $parameters);
        $http_code = $connection->getLastHttpCode();
        
        log_message('info', '[X_MODEL] Post with media response - HTTP: ' . $http_code);
        
        if ($http_code == 201 && isset($result->data->id)) {
            return [
                'success' => true,
                'message' => 'Posted successfully to X with media',
                'post_id' => $result->data->id,
                'error' => null
            ];
        }
        
        $error_message = $this->parse_x_api_error($result, $http_code);
        log_message('error', '[X_MODEL] Post with media failed: ' . $error_message);
        
        return [
            'success' => false,
            'message' => $error_message,
            'error' => $error_message,
            'post_id' => null
        ];
    }
    
    /**
     * Upload media using v1.1 endpoint (media upload still uses v1.1)
     */
    private function upload_media_v1($connection, $media_path)
    {
        try {
            if (!file_exists($media_path)) {
                return [
                    'success' => false,
                    'error' => 'Media file not found: ' . basename($media_path)
                ];
            }
            
            $file_size = filesize($media_path);
            $mime_type = $this->_get_mime_type($media_path);
            
            log_message('info', '[X_MODEL] Media info - Size: ' . $file_size . ', Type: ' . $mime_type);
            
            // Check file size limits
            $max_size = 5242880; // 5MB for images
            if (strpos($mime_type, 'video') !== false) {
                $max_size = 536870912; // 512MB for videos
            } elseif (strpos($mime_type, 'gif') !== false) {
                $max_size = 15728640; // 15MB for GIFs
            }
            
            if ($file_size > $max_size) {
                return [
                    'success' => false,
                    'error' => 'File too large: ' . round($file_size/1048576, 2) . 'MB (max: ' . round($max_size/1048576, 2) . 'MB)'
                ];
            }
            
            // Use v1.1 for media upload
            $connection->setApiVersion('1.1');
            
            // Simple upload for small images
            if ($file_size < 5000000) {
                $media = $connection->upload('media/upload', [
                    'media' => $media_path
                ]);
                
                $http_code = $connection->getLastHttpCode();
                log_message('info', '[X_MODEL] Media upload HTTP: ' . $http_code);
                
                if (isset($media->media_id_string)) {
                    return [
                        'success' => true,
                        'media_id' => $media->media_id_string,
                        'error' => null
                    ];
                }
                
                $error = $this->parse_x_api_error($media, $http_code);
                log_message('error', '[X_MODEL] Media upload failed: ' . $error);
                
                return [
                    'success' => false,
                    'error' => $error
                ];
            }
            
            // Chunked upload for large files
            return $this->upload_media_chunked($connection, $media_path, $file_size, $mime_type);
            
        } catch (Exception $e) {
            log_message('error', '[X_MODEL] Upload exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Upload exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Parse X API error responses (works for both v1.1 and v2)
     */
    private function parse_x_api_error($result, $http_code)
    {
        // HTTP code meanings
        $http_messages = [
            400 => 'Bad Request - Invalid parameters',
            401 => 'Unauthorized - Invalid or expired credentials',
            403 => 'Forbidden - Insufficient permissions or Free tier (upgrade to Basic)',
            404 => 'Not Found - Invalid endpoint',
            410 => 'Gone - Endpoint deprecated or unavailable on your tier',
            429 => 'Rate Limit Exceeded - Too many requests',
            500 => 'Internal Server Error - X API issue',
            503 => 'Service Unavailable - X API down'
        ];
        
        $base_error = isset($http_messages[$http_code]) 
            ? $http_messages[$http_code] 
            : 'Unknown error (HTTP ' . $http_code . ')';
        
        // API v2 error format
        if (isset($result->errors) && is_array($result->errors) && count($result->errors) > 0) {
            $error = $result->errors[0];
            $message = isset($error->message) ? $error->message : $base_error;
            $detail = isset($error->detail) ? $error->detail : '';
            
            if (!empty($detail)) {
                $message .= ' - ' . $detail;
            }
            
            return $message;
        }
        
        // API v1.1 error format
        if (isset($result->errors) && is_array($result->errors)) {
            foreach ($result->errors as $error) {
                if (isset($error->message)) {
                    $msg = $error->message;
                    if (isset($error->code)) {
                        $msg .= ' (Code: ' . $error->code . ')';
                    }
                    return $msg;
                }
            }
        }
        
        // Single error
        if (isset($result->error)) {
            return $result->error;
        }
        
        // Detailed error with title
        if (isset($result->title) && isset($result->detail)) {
            return $result->title . ': ' . $result->detail;
        }
        
        return $base_error;
    }
    
    /**
     * Chunked upload for large files
     */
    private function upload_media_chunked($connection, $media_path, $file_size, $mime_type)
    {
        // Use v1.1 for chunked upload
        $connection->setApiVersion('1.1');
        
        $media_category = 'tweet_image';
        if (strpos($mime_type, 'video') !== false) {
            $media_category = 'tweet_video';
        } elseif (strpos($mime_type, 'gif') !== false) {
            $media_category = 'tweet_gif';
        }
        
        // INIT
        $init = $connection->upload('media/upload', [
            'command' => 'INIT',
            'media_type' => $mime_type,
            'media_category' => $media_category,
            'total_bytes' => $file_size
        ]);
        
        if (!isset($init->media_id_string)) {
            $error = $this->parse_x_api_error($init, $connection->getLastHttpCode());
            return ['success' => false, 'error' => 'Init failed: ' . $error];
        }
        
        $media_id = $init->media_id_string;
        
        // APPEND chunks
        $fp = fopen($media_path, 'rb');
        if (!$fp) {
            return ['success' => false, 'error' => 'Cannot read media file'];
        }
        
        $segment_index = 0;
        $chunk_size = 1000000; // 1MB
        
        while (!feof($fp)) {
            $chunk = fread($fp, $chunk_size);
            
            $connection->upload('media/upload', [
                'command' => 'APPEND',
                'media_id' => $media_id,
                'segment_index' => $segment_index,
                'media' => $chunk
            ]);
            
            $segment_index++;
        }
        
        fclose($fp);
        
        // FINALIZE
        $finalize = $connection->upload('media/upload', [
            'command' => 'FINALIZE',
            'media_id' => $media_id
        ]);
        
        if (isset($finalize->processing_info)) {
            $this->wait_for_processing($connection, $media_id);
        }
        
        return [
            'success' => true,
            'media_id' => $media_id,
            'error' => null
        ];
    }
    
    /**
     * Wait for media processing
     */
    private function wait_for_processing($connection, $media_id, $max_attempts = 30)
    {
        $connection->setApiVersion('1.1');
        $attempts = 0;
        
        while ($attempts < $max_attempts) {
            $status = $connection->upload('media/upload', [
                'command' => 'STATUS',
                'media_id' => $media_id
            ]);
            
            if (!isset($status->processing_info)) {
                return true;
            }
            
            $state = $status->processing_info->state;
            
            if ($state == 'succeeded') {
                return true;
            }
            
            if ($state == 'failed') {
                return false;
            }
            
            $wait = isset($status->processing_info->check_after_secs) 
                ? $status->processing_info->check_after_secs 
                : 5;
            sleep($wait);
            $attempts++;
        }
        
        return false;
    }
    
    /**
     * Test X API connection and access level
     */
    public function test_connection()
    {
        try {
            $connection = new TwitterOAuth(
                'y5tlodBF8HiKNQhAE3L81O3fn',
                'Ud7Dzu0KGsObEYclcxDBxImipD62wRp3sGz5BqpXkThQCqyRjG',
                '44420801-8JNDorQl277DqUEi2UKdtn0P6Z0ATboMHJv5CzsLj',
                'r82NLPgdGiFtSrVnUOMA0ACR4gpnsgZNIowPaSLkvgY6N'
            );

            // Test with API v2
            $connection->setApiVersion('2');
            $user = $connection->get('users/me');
            $http_code = $connection->getLastHttpCode();
            
            log_message('info', '[X_MODEL] Test connection HTTP: ' . $http_code);
            log_message('info', '[X_MODEL] Response: ' . json_encode($user));
            
            if ($http_code == 200 && isset($user->data)) {
                $user_data = $user->data;
                return [
                    'success' => true,
                    'user_id' => $user_data->id ?? 'unknown',
                    'username' => $user_data->username ?? 'unknown',
                    'name' => $user_data->name ?? 'unknown',
                    'message' => 'Connection successful! Using X API v2'
                ];
            }
            
            // Parse error
            $error = $this->parse_x_api_error($user, $http_code);
            
            // Add helpful context for common errors
            if ($http_code == 403) {
                $error .= ' | Likely cause: Free tier (read-only). Upgrade to Basic ($100/month) to post.';
            } elseif ($http_code == 401) {
                $error .= ' | Check your API keys and tokens are correct.';
            }
            
            return [
                'success' => false,
                'error' => $error,
                'http_code' => $http_code
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get MIME type
     */
    private function _get_mime_type($file_path)
    {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file_path);
            if ($mime) return $mime;
        }

        if (function_exists('mime_content_type')) {
            return mime_content_type($file_path);
        }

        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $types = [
            'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png', 'gif' => 'image/gif',
            'webp' => 'image/webp', 'mp4' => 'video/mp4'
        ];
        
        return $types[$ext] ?? 'application/octet-stream';
    }
}