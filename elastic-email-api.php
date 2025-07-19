<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class Elastic_Email_Api {

    private $api_key;
    private $api_base_url = 'https://api.elasticemail.com/v2/';

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    private function make_request($endpoint, $data = array()) {
        $url = $this->api_base_url . $endpoint;

        $data['apikey'] = $this->api_key;

        $response = wp_remote_post($url, array(
            'body' => $data,
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body);

        if ($result->success) {
            return $result->data;
        }

        return false;
    }

    public function get_sub_account_list($email) {
        return $this->make_request('account/getsubaccountlist', array('email' => $email));
    }

    public function get_summary($from, $to, $subaccount_email) {
        return $this->make_request('log/summary', array(
            'from' => $from,
            'to' => $to,
            'subAccountEmail' => $subaccount_email,
        ));
    }
}
