<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Linkedin_model extends App_Model
{
    public function post_to_linkedin($connection, $message, $link = null, $media_path = null)
    {
        try {
            $url = 'https://api.linkedin.com/v2/ugcPosts';

            $post_data = [
                'author' => 'urn:li:person:' . $connection->account_id,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $message
                        ],
                        'shareMediaCategory' => 'NONE'
                    ]
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC'
                ]
            ];

            // Add link if provided
            if (!empty($link)) {
                $post_data['specificContent']['com.linkedin.ugc.ShareContent']['shareMediaCategory'] = 'ARTICLE';
                $post_data['specificContent']['com.linkedin.ugc.ShareContent']['media'] = [[
                    'status' => 'READY',
                    'originalUrl' => $link
                ]];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $connection->access_token,
                'Content-Type: application/json',
                'X-Restli-Protocol-Version: 2.0.0'
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