<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Pinterest Model - API v5
 * Updated for App ID: 1536005
 */
class Pinterest_model extends App_Model
{
    private $api_base = 'https://api.pinterest.com/v5';
    
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Post to Pinterest with base64 image upload
     * 
     * @param object $connection Connection with access_token and account_id (board_id)
     * @param string $message Pin description (max 500 chars)
     * @param string|null $link Destination URL (where pin leads to)
     * @param string|null $media_path Local path to image file
     */
    public function post_to_pinterest($connection, $message, $link = null, $media_path = null)
    {
        try {
            // Validate access token
            if (empty($connection->access_token)) {
                return [
                    'success' => false,
                    'error' => 'Missing Pinterest access token',
                    'post_id' => null
                ];
            }

            // Validate board ID
            if (empty($connection->account_id)) {
                return [
                    'success' => false,
                    'error' => 'Missing Pinterest board ID',
                    'post_id' => null
                ];
            }

            // Pinterest REQUIRES an image - this is critical!
            if (empty($media_path) || !file_exists($media_path)) {
                return [
                    'success' => false,
                    'error' => 'Pinterest requires an image. Please upload an image to create a pin.',
                    'post_id' => null
                ];
            }

            log_message('info', '[PINTEREST] Creating pin - Board: ' . $connection->account_id);
            log_message('info', '[PINTEREST] Image: ' . basename($media_path));

            // Prepare pin data
            $pin_data = [
                'board_id' => $connection->account_id,
                'description' => mb_substr($message, 0, 500), // Pinterest max 500 chars
            ];

            // Add destination link if provided (this is where users go when clicking the pin)
            if (!empty($link)) {
                $pin_data['link'] = $link;
                log_message('info', '[PINTEREST] Destination link: ' . $link);
            }

            // Convert image to base64 for upload
            $base64_result = $this->convert_image_to_base64($media_path);
            
            if (!$base64_result['success']) {
                return [
                    'success' => false,
                    'error' => $base64_result['error'],
                    'post_id' => null
                ];
            }
            
            // Set media source using base64
            $pin_data['media_source'] = [
                'source_type' => 'image_base64',
                'data' => $base64_result['data'],
                'content_type' => $base64_result['mime_type']
            ];
            
            log_message('info', '[PINTEREST] Image encoded, size: ' . strlen($base64_result['data']) . ' bytes');

            // Make API request
            $url = $this->api_base . '/pins';
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($pin_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $connection->access_token,
                'Content-Type: application/json',
                'User-Agent: Perfex-Social-Media-Poster/1.0'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);
            
            log_message('info', '[PINTEREST] API HTTP code: ' . $http_code);
            log_message('info', '[PINTEREST] API response: ' . $response);

            // Handle cURL errors
            if (!empty($curl_error)) {
                log_message('error', '[PINTEREST] cURL error: ' . $curl_error);
                return [
                    'success' => false,
                    'error' => 'Connection error: ' . $curl_error,
                    'post_id' => null
                ];
            }

            $result = json_decode($response, true);

            // Success response (201 Created)
            if ($http_code == 201 && isset($result['id'])) {
                log_message('info', '[PINTEREST] Pin created successfully: ' . $result['id']);
                return [
                    'success' => true,
                    'post_id' => $result['id'],
                    'error' => null,
                    'message' => 'Posted successfully to Pinterest'
                ];
            }

            // Parse error
            $error_message = $this->parse_error($result, $http_code);
            log_message('error', '[PINTEREST] Failed: ' . $error_message);

            return [
                'success' => false,
                'error' => $error_message,
                'post_id' => null
            ];

        } catch (Exception $e) {
            log_message('error', '[PINTEREST] Exception: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Exception: ' . $e->getMessage(),
                'post_id' => null
            ];
        }
    }

    /**
     * Convert image to base64 for Pinterest API
     */
    private function convert_image_to_base64($file_path)
    {
        try {
            if (!file_exists($file_path)) {
                return ['success' => false, 'error' => 'Image file not found'];
            }

            $file_size = filesize($file_path);
            
            // Pinterest max: 32MB
            if ($file_size > 33554432) {
                return [
                    'success' => false,
                    'error' => 'Image too large: ' . round($file_size / 1048576, 2) . 'MB (max 32MB)'
                ];
            }

            // Get MIME type
            $mime_type = $this->get_mime_type($file_path);
            
            // Validate type
            $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/bmp', 'image/webp'];
            if (!in_array($mime_type, $allowed)) {
                return ['success' => false, 'error' => 'Unsupported image type: ' . $mime_type];
            }

            // Read and encode
            $image_data = file_get_contents($file_path);
            if ($image_data === false) {
                return ['success' => false, 'error' => 'Failed to read image file'];
            }

            return [
                'success' => true,
                'data' => base64_encode($image_data),
                'mime_type' => $mime_type,
                'error' => null
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Base64 error: ' . $e->getMessage()];
        }
    }

    /**
     * Parse Pinterest API error
     */
    private function parse_error($result, $http_code)
    {
        $messages = [
            400 => 'Bad Request - Invalid parameters',
            401 => 'Unauthorized - Invalid or expired access token',
            403 => 'Forbidden - Trial access pending or insufficient permissions',
            404 => 'Not Found - Board not found',
            429 => 'Rate Limit Exceeded',
            500 => 'Pinterest API Error',
            503 => 'Pinterest API Unavailable'
        ];

        $base_error = $messages[$http_code] ?? 'Unknown error (HTTP ' . $http_code . ')';

        if (isset($result['message'])) {
            return $result['message'] . ' (HTTP ' . $http_code . ')';
        }

        if (isset($result['error'])) {
            return $result['error'] . ' (HTTP ' . $http_code . ')';
        }

        // Add helpful context
        if ($http_code == 403) {
            $base_error .= '. Your app may be in trial mode. Check Pinterest Developer Console.';
        } elseif ($http_code == 401) {
            $base_error .= '. Generate new token from Pinterest Developer Console.';
        } elseif ($http_code == 404) {
            $base_error .= '. Check Board ID is correct.';
        }

        return $base_error;
    }

    /**
     * Test Pinterest connection
     */
    public function test_connection($access_token)
    {
        try {
            $url = $this->api_base . '/user_account';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($http_code == 200 && isset($result['username'])) {
                return [
                    'success' => true,
                    'username' => $result['username'],
                    'account_type' => $result['account_type'] ?? 'unknown',
                    'error' => null
                ];
            }

            return [
                'success' => false,
                'error' => $this->parse_error($result, $http_code)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Exception: ' . $e->getMessage()];
        }
    }

    /**
     * Get user's boards (to find Board ID)
     */
    public function get_boards($access_token)
    {
        try {
            $url = $this->api_base . '/boards';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            $result = json_decode($response, true);

            if ($http_code == 200 && isset($result['items'])) {
                $boards = [];
                foreach ($result['items'] as $board) {
                    $boards[] = [
                        'id' => $board['id'],
                        'name' => $board['name'],
                        'description' => $board['description'] ?? '',
                        'pin_count' => $board['pin_count'] ?? 0
                    ];
                }

                return ['success' => true, 'boards' => $boards, 'error' => null];
            }

            return [
                'success' => false,
                'boards' => [],
                'error' => $this->parse_error($result, $http_code)
            ];

        } catch (Exception $e) {
            return ['success' => false, 'boards' => [], 'error' => $e->getMessage()];
        }
    }

    /**
     * Get MIME type
     */
    private function get_mime_type($file_path)
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
            'bmp' => 'image/bmp',
            'webp' => 'image/webp'
        ];

        return $types[$ext] ?? 'application/octet-stream';
    }
}