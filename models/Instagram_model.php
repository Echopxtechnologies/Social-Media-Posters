<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Instagram_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Post to Instagram
     * Fixed to ensure posts appear in public feed
     */
    public function post_to_instagram($connection, $message, $media_path = null)
    {
        try {
            // Validate inputs
            if (empty($connection->access_token)) {
                return ['success' => false, 'error' => 'Access token is empty'];
            }

            if (empty($connection->account_id)) {
                return ['success' => false, 'error' => 'Instagram account ID is empty'];
            }

            // Instagram REQUIRES media
            if (empty($media_path) || !file_exists($media_path)) {
                return [
                    'success' => false,
                    'error' => 'Instagram requires an image or video. Please upload media.'
                ];
            }

            // Get public URL
            $public_url = $this->_get_public_url($media_path);
            
            if (!$public_url) {
                return ['success' => false, 'error' => 'Could not generate public URL'];
            }

            // Determine media type
            $mime_type = $this->_get_mime_type($media_path);
            $is_video = strpos($mime_type, 'video') !== false;

            log_message('info', '[INSTAGRAM] Starting upload - URL: ' . $public_url);

            // ============================================
            // STEP 1: CREATE MEDIA CONTAINER
            // ============================================
            $container_url = 'https://graph.facebook.com/v18.0/' . $connection->account_id . '/media';
            
            $container_data = [
                'caption' => $message,
                'access_token' => $connection->access_token
            ];

            // Instagram requires URL parameters
            if ($is_video) {
                $container_data['media_type'] = 'VIDEO';
                $container_data['video_url'] = $public_url;
            } else {
                $container_data['image_url'] = $public_url;
            }

            log_message('info', '[INSTAGRAM] Creating container with data: ' . json_encode($container_data));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $container_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($container_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                log_message('error', '[INSTAGRAM] CURL error: ' . $error);
                return ['success' => false, 'error' => 'Upload Error: ' . $error];
            }
            
            curl_close($ch);
            
            log_message('info', '[INSTAGRAM] Container response (HTTP ' . $http_code . '): ' . $response);
            
            $result = json_decode($response, true);

            if ($http_code != 200 || !isset($result['id'])) {
                log_message('error', '[INSTAGRAM] Container creation failed: ' . $response);
                return [
                    'success' => false,
                    'error' => $this->_parse_instagram_error($result)
                ];
            }

            $creation_id = $result['id'];
            log_message('info', '[INSTAGRAM] Container created: ' . $creation_id);

            // ============================================
            // STEP 1.5: WAIT FOR VIDEO PROCESSING (IF VIDEO)
            // ============================================
            if ($is_video) {
                log_message('info', '[INSTAGRAM] Waiting for video processing...');
                $processing_result = $this->_wait_for_video_processing($creation_id, $connection->access_token);
                
                if (!$processing_result['success']) {
                    log_message('error', '[INSTAGRAM] Video processing failed');
                    return $processing_result;
                }
                log_message('info', '[INSTAGRAM] Video processing complete');
            } else {
                // For images, wait a bit to ensure Instagram has processed the image
                log_message('info', '[INSTAGRAM] Waiting 5 seconds for image processing...');
                sleep(5);
            }

            // ============================================
            // STEP 2: PUBLISH THE CONTAINER TO FEED
            // ============================================
            log_message('info', '[INSTAGRAM] Publishing container to feed...');
            
            $publish_url = 'https://graph.facebook.com/v18.0/' . $connection->account_id . '/media_publish';
            
            $publish_data = [
                'creation_id' => $creation_id,
                'access_token' => $connection->access_token
            ];

            log_message('info', '[INSTAGRAM] Publish URL: ' . $publish_url);
            log_message('info', '[INSTAGRAM] Publish data: ' . json_encode($publish_data));

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $publish_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($publish_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                log_message('error', '[INSTAGRAM] Publish CURL error: ' . $error);
                return ['success' => false, 'error' => 'Publish Error: ' . $error];
            }
            
            curl_close($ch);
            
            log_message('info', '[INSTAGRAM] Publish response (HTTP ' . $http_code . '): ' . $response);
            
            $result = json_decode($response, true);

            if ($http_code == 200 && isset($result['id'])) {
                log_message('info', '[INSTAGRAM] Post published successfully! Post ID: ' . $result['id']);
                return [
                    'success' => true,
                    'post_id' => $result['id'],
                    'type' => $is_video ? 'video' : 'image'
                ];
            } else {
                log_message('error', '[INSTAGRAM] Publish failed: ' . $response);
                return [
                    'success' => false,
                    'error' => $this->_parse_instagram_error($result)
                ];
            }

        } catch (Exception $e) {
            log_message('error', '[INSTAGRAM] Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Convert local file path to public URL
     */
    private function _get_public_url($local_path)
    {
        $filename = basename($local_path);
        $public_url = base_url('uploads/temp/' . $filename);
        
        log_message('info', '[INSTAGRAM] Generated public URL: ' . $public_url);
        
        return $public_url;
    }

    /**
     * Wait for video processing
     */
    private function _wait_for_video_processing($container_id, $access_token)
    {
        $max_attempts = 30;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            sleep(10);
            
            $status_url = 'https://graph.facebook.com/v18.0/' . $container_id . '?fields=status_code&access_token=' . $access_token;
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $status_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            
            $response = curl_exec($ch);
            
            if (curl_errno($ch)) {
                curl_close($ch);
                $attempt++;
                continue;
            }
            
            curl_close($ch);
            
            $status_data = json_decode($response, true);
            
            log_message('info', '[INSTAGRAM] Status check attempt ' . ($attempt + 1) . ': ' . $response);
            
            if (isset($status_data['status_code'])) {
                if ($status_data['status_code'] == 'FINISHED') {
                    return ['success' => true];
                } elseif ($status_data['status_code'] == 'ERROR') {
                    return [
                        'success' => false,
                        'error' => 'Video processing failed'
                    ];
                }
            }
            
            $attempt++;
        }
        
        return ['success' => false, 'error' => 'Video processing timeout'];
    }

    /**
     * Parse Instagram error messages
     */
    private function _parse_instagram_error($result)
    {
        if (!isset($result['error'])) {
            return 'Unknown error: ' . json_encode($result);
        }

        $error = $result['error'];
        $code = isset($error['code']) ? $error['code'] : 'N/A';
        $message = isset($error['message']) ? $error['message'] : 'Unknown error';

        $error_guide = [
            3 => 'App needs permissions. Request: instagram_basic, instagram_content_publish',
            4 => 'Rate limit reached. Wait a few minutes.',
            190 => 'Access token expired. Generate new token.',
            200 => 'No permission. Check Instagram Business Account.',
            9007 => 'Media upload failed. Check file size and format.',
            10 => 'Permission denied. Check app permissions.',
            100 => 'Invalid parameter. Ensure URL is publicly accessible.',
            352 => 'Publishing too fast. Wait 20-30 seconds between posts.',
            2207026 => 'Container not ready. Try waiting longer before publishing.',
        ];

        $help = isset($error_guide[$code]) ? ' | ' . $error_guide[$code] : '';

        return "(#{$code}) {$message}{$help}";
    }

    /**
     * Verify connection
     */
    public function verify_connection($account_id, $access_token)
    {
        $url = 'https://graph.facebook.com/v18.0/' . $account_id . '?fields=id,username,name&access_token=' . $access_token;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if (isset($result['id'])) {
            return [
                'success' => true,
                'account_name' => isset($result['username']) ? '@' . $result['username'] : $result['name'],
                'account_id' => $result['id']
            ];
        }
        
        return [
            'success' => false,
            'error' => $this->_parse_instagram_error($result)
        ];
    }

    /**
     * Get MIME type with fallback
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
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'mp4' => 'video/mp4',
            'mov' => 'video/quicktime'
        ];
        
        return isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
    }
}
