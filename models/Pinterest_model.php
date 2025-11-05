<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Pinterest_model extends App_Model
{
    public function post_to_pinterest($connection, $message, $link = null, $media_path = null)
    {
        try {
            // Pinterest requires an image
            if (empty($media_path) || !file_exists($media_path)) {
                return ['success' => false, 'error' => 'Pinterest requires an image'];
            }

            $url = 'https://api.pinterest.com/v5/pins';

            $post_data = [
                'board_id' => $connection->account_id, // Board ID
                'description' => $message,
                'media_source' => [
                    'source_type' => 'image_url',
                    'url' => $link // Pinterest needs image URL
                ]
            ];

            if (!empty($link)) {
                $post_data['link'] = $link;
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $connection->access_token,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);

            if ($http_code == 201 && isset($result['id'])) {
                return ['success' => true, 'post_id' => $result['id']];
            } else {
                $error = isset($result['message']) ? $result['message'] : 'Unknown error';
                return ['success' => false, 'error' => $error];
            }

        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}