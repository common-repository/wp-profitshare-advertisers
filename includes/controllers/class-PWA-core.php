<?php

/**
 * Class Profitshare
 *
 * This class is handling all profitshare operations
 */
class PWA_Core {
    // advertiser details url used for getting tracking_id and click_code
    public $advertiserDetailsUrl = "https://app.%s/advertiser/get-module-data";

    // current server
    private $server;

    // available profitshare servers
    const SERVERS = array(
        "profitshare.ro",
        "profitshare.bg"
    );

    // default profitshare server
    const DEFAULT_SERVER = self::SERVERS[0];

    // tracking script path
    const TRACKING_SCRIPT_NAME = "profitshare_tracking.js";
    
    // update tracking script after hours
    const TRACKING_SCRIPT_UPDATE_AFTER = 24; // hours

    public function __construct($server = null) {
        $this->server = (!empty($server) && in_array($server, self::SERVERS)) ? $server : self::DEFAULT_SERVER;
    }

    /**
     * Get selected server
     * 
     * @return string
     */
    public function getServer() {
        return $this->server;
    }

    /**
     * Get tracking script path
     * @return string
     */
    public function getTrackingScriptPath() {
        return (new PWA_Settings())->getUploadURL(self::TRACKING_SCRIPT_NAME);
    }

    /**
     * @return string
     */
    public function getTrackingScriptServerPath() {
        return (new PWA_Settings())->getUploadPath(self::TRACKING_SCRIPT_NAME);
    }

    /*
     * Generate tracking script
     */
    public function generateTrackingScript($advertiserCookieScriptURL) {
        if(!file_exists($this->getTrackingScriptServerPath())) {
            return $this->downloadTrackingScript($advertiserCookieScriptURL);
        }

        $lastUpdate = date('Y-m-d H:i:s', filemtime($this->getTrackingScriptServerPath()));
        $expireDate = date('Y-m-d H:i:s', strtotime($lastUpdate." +".self::TRACKING_SCRIPT_UPDATE_AFTER." hour"));
        $currentDate = date('Y-m-d H:i:s');

        if($currentDate < $expireDate) {
            return;
        }

        $this->downloadTrackingScript($advertiserCookieScriptURL);
    }

    /**
     * Download tracking script from profitshare
     * 
     * @param $advertiserCookieScriptURL
     */
    private function downloadTrackingScript($advertiserCookieScriptURL) {
        $trackingScript = fopen((new PWA_Settings())->getUploadPath(self::TRACKING_SCRIPT_NAME), "w");

        // get cookie script
        $cookieScriptJS = $this->callURL("https:".$advertiserCookieScriptURL);

        fwrite($trackingScript, $cookieScriptJS);
        fclose($trackingScript);
    }

    /**
     * Echo tracking script code for wordpress footer
     */
    public function getTrackingScript() {
        $updatedAt = filemtime($this->getTrackingScriptServerPath());

        echo "<script type='text/javascript' src='{$this->getTrackingScriptPath()}?v={$updatedAt}'></script>";
    }

    /**
     * CURL Function
     * @param $url call url
     * @param $values post values data
     * @return string or null
     */
    public function callURL($url, $values = null) {
        $settings = [
              'method' => empty($values) ? 'GET' : 'POST',
              'body' => $values,
        ];

        $response = wp_remote_get($url,  $settings);
        $result = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);

        if($http_code === 200) {
            return $result;
        }

        return;
    }

    /**
     * Encrypt function
     * @param $plaintext
     * @param $key
     * @return string
     */
    public function encrypt($plaintext, $key)
    {
        $cipher = "AES-128-CBC";
        $ivlen = openssl_cipher_iv_length($cipher);
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($plaintext, $cipher, $key, $options=OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $ciphertext_raw, $key, $as_binary=true);
        $encode = base64_encode( $iv.$hmac.$ciphertext_raw );
        $ciphertext = bin2hex($encode);

        return $ciphertext;
    }

    /**
     * Update profitshare categories
     */
    public function update_categories() {
        $profithsareNewCategories = $this->get_categories();

        $profitshareCategoriesModel = new PWA_Categories_Model();
        $profitshareCategoriesModel->deactivateCategories();

        foreach ($profithsareNewCategories as $category) {
            $profitshareCategoriesModel->add($category);
        }

        return true;
    }

    /*
     * Get profitshare categories, TODO add profitshare api call to get categories
     */
    public function get_categories(){
        return [
            [
                "id_category" => 1,
                "name" => "Main profitshare",
                "id_parent" => 0,
            ],
            [
                "id_category" => 2,
                "name" => "Test",
                "id_parent" => 1,
            ],
            [
                "id_category" => 3,
                "name" => "Test2",
                "id_parent" => 1,
            ],
            [
                "id_category" => 4,
                "name" => "Subcategorie",
                "id_parent" => 2,
            ],
            [
                "id_category" => 15,
                "name" => "Categorie 5",
                "id_parent" => 3,
            ],
        ];
    }
}