<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once(FCPATH . 'vendor/autoload.php');
use Tumblr\API\Client;

/**
 * Tumblr Model (Post text and media)
 * Based on official Tumblr PHP SDK: https://github.com/tumblr/tumblr.php
 */
class Tumblr_model extends App_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Post to Tumblr
     * 
     * @param array|object $credentials Must contain consumer_key, consumer_secret, oauth_token, oauth_token_secret, blog_name
     * @param string $content Post caption or text
     * @param array $media Array of local image file paths (optional)
     * @return array
     */
    public function post_to_tumblr($credentials, $content, $media = [])
    {
        try {
            if (is_object($credentials)) {
                $credentials = (array) $credentials;
            }

            // Validate required credentials
            $required = ['consumer_key', 'consumer_secret', 'oauth_token', 'oauth_token_secret', 'blog_name'];
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

            // Validate blog name format
            $blog_name = $credentials['blog_name'];
            if (!$this->validate_blog_name($blog_name)) {
                return [
                    'success' => false,
                    'message' => 'Invalid blog name format. Use: blogname.tumblr.com',
                    'error' => 'Invalid blog name format. Use: blogname.tumblr.com',
                    'post_id' => null
                ];
            }

            log_message('info', '[TUMBLR_MODEL] Connecting to blog: ' . $blog_name);

            // Create Tumblr client
            $client = new Client(
                $credentials['consumer_key'],
                $credentials['consumer_secret'],
                $credentials['oauth_token'],
                $credentials['oauth_token_secret']
            );

            // Validate and filter media files
            $valid_media = [];
            if (!empty($media) && is_array($media)) {
                foreach ($media as $file) {
                    if (!empty($file) && is_string($file) && file_exists($file) && is_readable($file)) {
                        $valid_media[] = $file;
                        log_message('debug', '[TUMBLR_MODEL] Valid media file: ' . basename($file));
                    } else {
                        log_message('warning', '[TUMBLR_MODEL] Invalid media file: ' . var_export($file, true));
                    }
                }
            }

            // Post with or without media
            if (empty($valid_media)) {
                log_message('info', '[TUMBLR_MODEL] Posting text-only to Tumblr');
                return $this->post_text($client, $blog_name, $content);
            }

            log_message('info', '[TUMBLR_MODEL] Posting with ' . count($valid_media) . ' media file(s)');
            return $this->post_photo($client, $blog_name, $content, $valid_media);

        } catch (Exception $e) {
            log_message('error', '[TUMBLR_MODEL] Exception: ' . $e->getMessage());
            log_message('error', '[TUMBLR_MODEL] Stack trace: ' . $e->getTraceAsString());

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'error' => 'Exception: ' . $e->getMessage(),
                'post_id' => null
            ];
        }
    }

    /**
     * Validate Tumblr blog name format
     * 
     * @param string $blog_name
     * @return bool
     */
    private function validate_blog_name($blog_name)
    {
        // Must be in format: blogname.tumblr.com or custom domain
        if (empty($blog_name)) {
            return false;
        }

        // Check if it contains a dot (domain format)
        if (strpos($blog_name, '.') === false) {
            return false;
        }

        return true;
    }

    /**
     * Post text-only to Tumblr
     * 
     * @param Client $client
     * @param string $blog
     * @param string $text
     * @return array
     */
    private function post_text($client, $blog, $text)
    {
        log_message('info', '[TUMBLR_MODEL] Posting text to Tumblr blog: ' . $blog);

        try {
            $response = $client->createPost($blog, [
                'type' => 'text',
                'body' => $text,
                'state' => 'published' // or 'draft', 'queue', 'private'
            ]);

            return $this->handle_response($response, 'Posted successfully to Tumblr (Text)');
        } catch (Exception $e) {
            log_message('error', '[TUMBLR_MODEL] Text post failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Text post failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'post_id' => null
            ];
        }
    }

    /**
     * Post photo to Tumblr
     * Uses direct file path method (most reliable)
     * 
     * @param Client $client
     * @param string $blog
     * @param string $caption
     * @param array $media_files
     * @return array
     */
    private function post_photo($client, $blog, $caption, $media_files)
    {
        log_message('info', '[TUMBLR_MODEL] Posting photo to Tumblr blog: ' . $blog . ' with ' . count($media_files) . ' file(s)');

        try {
            // Prepare data array
            $post_data = [
                'type' => 'photo',
                'caption' => $caption,
                'state' => 'published'
            ];

            // For single image, use 'data' parameter with file path
            if (count($media_files) === 1) {
                $file_path = $media_files[0];
                
                // Verify file exists
                if (!file_exists($file_path)) {
                    return [
                        'success' => false,
                        'message' => 'Media file not found: ' . $file_path,
                        'error' => 'Media file not found',
                        'post_id' => null
                    ];
                }

                log_message('debug', '[TUMBLR_MODEL] Uploading single file: ' . basename($file_path));
                $post_data['data'] = $file_path;
            } 
            // For multiple images, use 'data' parameter with array of file paths
            else {
                log_message('debug', '[TUMBLR_MODEL] Uploading ' . count($media_files) . ' files');
                $post_data['data'] = $media_files;
            }

            // Make the API call
            $response = $client->createPost($blog, $post_data);

            return $this->handle_response($response, 'Posted successfully to Tumblr (Photo)');

        } catch (Exception $e) {
            log_message('error', '[TUMBLR_MODEL] Photo post failed: ' . $e->getMessage());
            
            // If file path method fails, try base64 method as fallback
            log_message('info', '[TUMBLR_MODEL] Trying base64 upload method as fallback...');
            return $this->post_photo_base64($client, $blog, $caption, $media_files);
        }
    }

    /**
     * Post photo using base64 encoding (fallback method)
     * 
     * @param Client $client
     * @param string $blog
     * @param string $caption
     * @param array $media_files
     * @return array
     */
    private function post_photo_base64($client, $blog, $caption, $media_files)
    {
        log_message('info', '[TUMBLR_MODEL] Posting photo via base64 method');

        try {
            $base64_images = [];

            foreach ($media_files as $file) {
                if (!file_exists($file) || !is_readable($file)) {
                    log_message('error', '[TUMBLR_MODEL] File not accessible: ' . $file);
                    continue;
                }

                // Read file and encode
                $file_content = file_get_contents($file);
                if ($file_content === false) {
                    log_message('error', '[TUMBLR_MODEL] Failed to read file: ' . $file);
                    continue;
                }

                $base64_data = base64_encode($file_content);
                $base64_images[] = $base64_data;
                
                log_message('debug', '[TUMBLR_MODEL] Encoded file: ' . basename($file) . ' (' . strlen($file_content) . ' bytes)');
            }

            if (empty($base64_images)) {
                return [
                    'success' => false,
                    'message' => 'No valid media files to upload',
                    'error' => 'No valid media files',
                    'post_id' => null
                ];
            }

            // Prepare post data
            $post_data = [
                'type' => 'photo',
                'caption' => $caption,
                'state' => 'published'
            ];

            // Use 'data64' parameter for base64 encoded images
            if (count($base64_images) === 1) {
                $post_data['data64'] = $base64_images[0];
            } else {
                $post_data['data64'] = $base64_images;
            }

            $response = $client->createPost($blog, $post_data);

            return $this->handle_response($response, 'Posted successfully to Tumblr (Photo via base64)');

        } catch (Exception $e) {
            log_message('error', '[TUMBLR_MODEL] Base64 photo post failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Photo post failed: ' . $e->getMessage(),
                'error' => $e->getMessage(),
                'post_id' => null
            ];
        }
    }

    /**
     * Handle Tumblr API response
     * 
     * @param object $response
     * @param string $success_message
     * @return array
     */
    private function handle_response($response, $success_message)
    {
        log_message('debug', '[TUMBLR_MODEL] Response: ' . json_encode($response));

        // Check for successful response
        if (isset($response->id) || isset($response->id_string)) {
            $post_id = isset($response->id_string) ? $response->id_string : $response->id;
            
            log_message('info', '[TUMBLR_MODEL] Success! Post ID: ' . $post_id);
            
            return [
                'success' => true,
                'message' => $success_message,
                'post_id' => $post_id,
                'error' => null
            ];
        }

        // Handle error response
        $error = 'Unknown error';
        
        if (isset($response->errors)) {
            $error = is_array($response->errors) ? json_encode($response->errors) : $response->errors;
        } elseif (isset($response->meta) && isset($response->meta->msg)) {
            $error = $response->meta->msg;
        } elseif (isset($response->response) && isset($response->response->errors)) {
            $error = json_encode($response->response->errors);
        }

        log_message('error', '[TUMBLR_MODEL] Post failed: ' . $error);

        return [
            'success' => false,
            'message' => $error,
            'error' => $error,
            'post_id' => null
        ];
    }

    /**
     * Test Tumblr connection
     * 
     * @param array $credentials
     * @return array
     */
    public function test_connection($credentials)
    {
        try {
            $client = new Client(
                $credentials['consumer_key'],
                $credentials['consumer_secret'],
                $credentials['oauth_token'],
                $credentials['oauth_token_secret']
            );

            // Get user info to test connection
            $info = $client->getUserInfo();
            
            if (isset($info->user)) {
                return [
                    'success' => true,
                    'username' => $info->user->name ?? 'unknown',
                    'message' => 'Tumblr connection successful'
                ];
            }

            return [
                'success' => false,
                'error' => 'Failed to retrieve user info'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Test Tumblr photo upload with debug output
     * 
     * @param array $credentials
     * @param string $image_path
     * @return array
     */
    public function test_photo_upload($credentials, $image_path)
    {
        try {
            if (!file_exists($image_path)) {
                return ['success' => false, 'error' => 'Image file not found: ' . $image_path];
            }

            $client = new Client(
                $credentials['consumer_key'],
                $credentials['consumer_secret'],
                $credentials['oauth_token'],
                $credentials['oauth_token_secret']
            );

            $blog = $credentials['blog_name'];

            echo "Testing Tumblr Upload...\n";
            echo "Blog: {$blog}\n";
            echo "Image: {$image_path}\n";
            echo "File exists: " . (file_exists($image_path) ? 'YES' : 'NO') . "\n";
            echo "File size: " . filesize($image_path) . " bytes\n\n";

            // Try Method 1: Direct file path
            echo "Attempting Method 1: Direct file path...\n";
            try {
                $response1 = $client->createPost($blog, [
                    'type' => 'photo',
                    'caption' => 'Test post via direct file path - ' . date('Y-m-d H:i:s'),
                    'data' => $image_path,
                    'state' => 'published'
                ]);
                
                echo "Method 1 SUCCESS!\n";
                print_r($response1);
                return ['success' => true, 'method' => 'direct_path', 'response' => $response1];
            } catch (Exception $e1) {
                echo "Method 1 FAILED: " . $e1->getMessage() . "\n\n";
            }

            // Try Method 2: Base64
            echo "Attempting Method 2: Base64...\n";
            try {
                $file_content = file_get_contents($image_path);
                $base64 = base64_encode($file_content);
                
                $response2 = $client->createPost($blog, [
                    'type' => 'photo',
                    'caption' => 'Test post via base64 - ' . date('Y-m-d H:i:s'),
                    'data64' => $base64,
                    'state' => 'published'
                ]);
                
                echo "Method 2 SUCCESS!\n";
                print_r($response2);
                return ['success' => true, 'method' => 'base64', 'response' => $response2];
            } catch (Exception $e2) {
                echo "Method 2 FAILED: " . $e2->getMessage() . "\n\n";
            }

            return ['success' => false, 'error' => 'All methods failed'];

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}