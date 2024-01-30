<?php

class InvalidTokenException extends Exception {}

class MprofiAPIConnector{

    // Base URL for public API
    public $url_base = 'https://api.mprofi.pl';

    // API version
    public $api_version = '1.0';

    // Name of send endpoint
    public $send_endpoint = 'send';

    // Name of sendbulk endpoint
    public $sendbulk_endpoint = 'sendbulk';

    // Name of status endpoint
    public $status_endpoint = 'status';

    // Name of get_incoming endpoint
    public $get_incoming_endpoint = 'getincoming';


    // API Token
    private $api_token = '';

    // Store for messages to send
    private $payload = array();

    // CURL instance
    private $curl = NULL;

    // Public constructor
    public function __construct($api_token) {
        // check if we can use curl
        if (!function_exists('curl_version')) {
            throw new Exception('It seems curl is not installed. Please install php-curl and try again');
        }

        // init curl
        $this->curl = curl_init();
        $this->api_token = $api_token;
    }


    public function __destruct() {
        if ($this->curl) {
            curl_close($this->curl);
        }
    }


    public function add_message($recipient, $message, $options = array()) {
        if (!$recipient) {
            throw new InvalidArgumentException('recipient cannot be empty');
        }

        if (!$message) {
            throw new InvalidArgumentException('message cannot be empty');
        }

        $entry = array('recipient' => $recipient, 'message' => $message);
        foreach (['reference', 'date', 'encoding'] as $opt) {
            if ($this->is_valid_option($opt, $options)) {
                $entry[$opt] = $options[$opt];
            }
        }

        array_push($this->payload, $entry);
    }


    public function send() {
        if (count($this->payload) == 1) {
            $bulk = false;
            $used_endpoint = $this->send_endpoint;
            $this->payload[0]['apikey'] = $this->api_token;
            $encoded_payload = json_encode($this->payload[0]);
        } elseif (count($this->payload) > 1) {
            $bulk = true;
            $used_endpoint = $this->sendbulk_endpoint;
            $encoded_payload = json_encode(array(
                'apikey' => $this->api_token,
                'messages' => $this->payload
            ));
        } else {
            throw new Exception('Empty payload. Please use add_message first.');
        }

        $full_url = join('/', array($this->url_base, $this->api_version, $used_endpoint, ''));

        curl_setopt($this->curl, CURLOPT_URL, $full_url);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $encoded_payload);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($this->curl);
        $httpcode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->payload = array();

        if ($httpcode != "200") {
            switch ($httpcode) {
                case "401":
                    throw new Exception('API call failed with HTTP ' . $httpcode . ' - make sure the supplied API Token is valid');
                    break;

                default:
                    throw new Exception('API call failed with HTTP ' . $httpcode);
            }
        }

        $decoded_response = json_decode($response, true);

        if ($bulk) {
            $ids = array();
            foreach ($decoded_response['result'] as $result) {
                array_push($ids, $result['id']);
            }

            return $ids;
        } else {
            return array($decoded_response['id']);
        }
    }


    public function get_status($id) {
        $full_url = join('/', array($this->url_base, $this->api_version, $this->status_endpoint, ''));
        $full_url .= '?apikey=' . $this->api_token . '&id=' . $id;

        curl_setopt($this->curl, CURLOPT_URL, $full_url);
        curl_setopt($this->curl, CURLOPT_POST, false);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($this->curl);
        $httpcode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);

        if ($httpcode != "200") {
            switch($httpcode){
                case "401":
                    throw new Exception('API call failed with HTTP ' . $httpcode . ' - make sure the supplied API Token is valid');
                    break;

                default:
                    throw new Exception('API call failed with HTTP ' . $httpcode);
            }
        }

        $decoded_response = json_decode($response, true);

        return $decoded_response;
    }


    public function get_incoming($date_from, $date_to) {
        // validate parameters
        $format = 'Y-m-d H:i:s';
        $dt_from = DateTime::createFromFormat($format, $date_from);
        $dt_to = DateTime::createFromFormat($format, $date_to);
        if (!$dt_from || !$date_to) {
            throw new InvalidArgumentException("Invalid date fromat. The correct format is: 'YYYY-mm-dd HH:MM:SS'");
        }

        if ($dt_from > $dt_to) {
            throw new InvalidArgumentException("Start date cannot be later than the end date");
        }

        $full_url = join('/', array($this->url_base, $this->api_version, $this->get_incoming_endpoint, ''));

        $encoded_payload = json_encode(array(
            'apikey' => $this->api_token,
            'date_from' => $date_from,
            'date_to' => $date_to
        ));

        curl_setopt($this->curl, CURLOPT_URL, $full_url);
        curl_setopt($this->curl, CURLOPT_POST, true);
        curl_setopt($this->curl, CURLOPT_POSTFIELDS, $encoded_payload);
        curl_setopt($this->curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($this->curl);
        $httpcode = curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
        $this->payload = array();

        if($httpcode != "200"){
            switch($httpcode){
                case "401":
                    throw new Exception('API call failed with HTTP ' . $httpcode . ' - make sure the supplied API Token is valid');
                    break;

                default:
                    throw new Exception('API call failed with HTTP ' . $httpcode);
            }
        }

        return json_decode($response, true);
    }


    private function is_valid_option($name, $options) {
        return array_key_exists($name, $options) && $options[$name] !== null && $options[$name] !== '';
    }

}

?>
