<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Tumblr_model extends App_Model
{
    public function post_to_tumblr($connection, $message, $media_path = null)
    {
        try {
            // Tumblr API - text post or photo post
            $url = 'https://api.tumblr.com/v2/blog/' . $connection->account_id . '/post';

            if (!empty($media_path) && file_exists($media_path)) {
                // Photo post
                $post_data = [
                    'type' => 'photo',
                    'caption' => $message,
                    'data' => new CURLFile($media_path)
                ];
            } else {
                // Text post
                $post_data = [
                    'type' => 'text',
                    'body' => $message
                ];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $connection->access_token
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);

            if ($http_code == 201 && isset($result['response']['id'])) {
                return ['success' => true, 'post_id' => $result['response']['id']];
            } else {
                $error = isset($result['meta']['msg']) ? $result['meta']['msg'] : 'Unknown error';
                return ['success' => false, 'error' => $error];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}