<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Facebook_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Post to Facebook
     */
    public function _post_to_facebook($connection, $message, $link = null, $media_path = null)
    {
        try {
            // Validate inputs
            if (empty($connection->access_token)) {
                return ['success' => false, 'error' => 'Access token is empty'];
            }

            if (empty($connection->account_id)) {
                return ['success' => false, 'error' => 'Page ID is empty'];
            }

            $has_media = !empty($media_path) && file_exists($media_path);

            // ============================================
            // TEXT ONLY POST
            // ============================================
            if (!$has_media) {
                $url = 'https://graph.facebook.com/v18.0/' . $connection->account_id . '/feed';
                
                $post_data = [
                    'message' => $message,
                    'access_token' => $connection->access_token
                ];
                
                if (!empty($link)) {
                    $post_data['link'] = $link;
                }
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if (curl_errno($ch)) {
                    $error = curl_error($ch);
                    curl_close($ch);
                    return ['success' => false, 'error' => 'CURL Error: ' . $error];
                }
                
                curl_close($ch);
                
                $result = json_decode($response, true);
                
                if ($http_code == 200 && isset($result['id'])) {
                    return [
                        'success' => true,
                        'post_id' => $result['id'],
                        'type' => 'text'
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => $this->_parse_facebook_error($result)
                    ];
                }
            }

            // ============================================
            // IMAGE + TEXT POST
            // ============================================
            $url = 'https://graph.facebook.com/v18.0/' . $connection->account_id . '/photos';
            
            $mime_type = $this->_get_mime_type($media_path);
            $image_data = new CURLFile($media_path, $mime_type, basename($media_path));
            
            $post_data = [
                'message' => $message,
                'access_token' => $connection->access_token,
                'source' => $image_data
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 120);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            if (curl_errno($ch)) {
                $error = curl_error($ch);
                curl_close($ch);
                return ['success' => false, 'error' => 'Upload Error: ' . $error];
            }
            
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($http_code == 200 && isset($result['id'])) {
                return [
                    'success' => true,
                    'post_id' => $result['id'],
                    'type' => 'image'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $this->_parse_facebook_error($result)
                ];
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Parse Facebook error messages
     */
    private function _parse_facebook_error($result)
    {
        if (!isset($result['error'])) {
            return 'Unknown error: ' . json_encode($result);
        }

        $error = $result['error'];
        $code = isset($error['code']) ? $error['code'] : 'N/A';
        $message = isset($error['message']) ? $error['message'] : 'Unknown error';
        $type = isset($error['type']) ? $error['type'] : '';

        // Common error codes
        $error_guide = [
            3 => 'App needs permissions. Go to App Review → Request: pages_manage_posts',
            4 => 'Rate limit reached. Wait a few minutes.',
            190 => 'Access token expired. Generate new token.',
            200 => 'No permission. Check you are Page Admin.',
            102 => 'Invalid session. Re-authenticate.',
            10 => 'Permission denied. Check app permissions.',
        ];

        $help = isset($error_guide[$code]) ? ' | ' . $error_guide[$code] : '';

        return "(#{$code}) {$message}{$help}";
    }

    /**
     * Verify token and get page info
     */
    public function verify_connection($page_id, $access_token)
    {
        $url = 'https://graph.facebook.com/v18.0/' . $page_id . '?fields=id,name,access_token&access_token=' . $access_token;
        
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
                'page_name' => $result['name'],
                'page_id' => $result['id']
            ];
        }
        
        return [
            'success' => false,
            'error' => $this->_parse_facebook_error($result)
        ];
    }
    /**
 * Get MIME type with fallback
 */
private function _get_mime_type($file_path)
{
    // Try finfo first (most reliable)
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file_path);
        if ($mime) return $mime;
    }

    // Try mime_content_type if available
    if (function_exists('mime_content_type')) {
        return mime_content_type($file_path);
    }

    // Fallback to extension mapping
    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $types = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg',
        'png' => 'image/png', 'gif' => 'image/gif',
        'webp' => 'image/webp', 'mp4' => 'video/mp4'
    ];
    
    return isset($types[$ext]) ? $types[$ext] : 'application/octet-stream';
}
}