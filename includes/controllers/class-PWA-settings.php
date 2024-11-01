<?php

/**
 * Class ProfitshareSettings
 *
 * This class is handling all profitshare settings
 */
class PWA_Settings {
    private $tracking_code;
    private $encryption_key;
    private $vat_value;
    private $tracking_url;
    private $click_code;
    private $country;
    private $feedFileName;
    private $currencyExchangeValue;

    const CURRENCIES = [
        PWA_Core::SERVERS[0] => self::DEFAULT_CURRENCY,
        PWA_Core::SERVERS[1] => self::BULGARY_CURRENCY,
    ];

    const DEFAULT_CURRENCY = "RON";
    const BULGARY_CURRENCY = "BGN";

    public function __construct()
    {
        $this->tracking_code = get_option(PWA_Plugin::PLUGIN_OPTION_ADVERTISER_KEY, null);
        $this->encryption_key = get_option(PWA_Plugin::PLUGIN_OPTION_ENCRYPTION_KEY, null);
        $this->vat_value = get_option(PWA_Plugin::PLUGIN_OPTION_VAT_KEY, 0);
        $this->tracking_url = get_option(PWA_Plugin::PLUGIN_OPTION_TRACKING_URL, null);
        $this->click_code = get_option(PWA_Plugin::PLUGIN_OPTION_CLICK_CODE, null);
        $this->country = get_option(PWA_Plugin::PLUGIN_OPTION_COUNTRY, null);
        $this->feedFileName = get_option(PWA_Plugin::PLUGIN_OPTION_FEED_FILE_NAME, null);
        $this->currencyExchangeValue = get_option(PWA_Plugin::PLUGIN_OPTION_EXCHANGE_VALUE, PWA_Plugin::PLUGIN_OPTION_DEFAULT_EXCHANGE_VALUE);
    }

    public function getTrackingCode()
    {
        return $this->tracking_code;
    }

    public function getEncryptionKey()
    {
        return $this->encryption_key;
    }

    public function getVatValue()
    {
        return $this->vat_value;
    }

    public function getTrackingUrl()
    {
        return $this->tracking_url;
    }

    public function getClickCode()
    {
        return $this->click_code;
    }

    public function getCountry()
    {
        return $this->country;
    }

    public function getExchangeValue()
    {
        if(empty($this->currencyExchangeValue)) {
            return PWA_Plugin::PLUGIN_OPTION_DEFAULT_EXCHANGE_VALUE;
        }

        return $this->currencyExchangeValue;
    }

    public function getUploadPath($file = null)
    {
        $path = wp_upload_dir()['basedir'].'/'.PWA_Plugin::PLUGIN_NAME;

        // verify id dir exists
        if(!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        if($file) {
            return $path.'/'.$file;
        }

        return $path;
    }

    public function getUploadURL($file = null)
    {
        $path = wp_upload_dir()['baseurl'].'/'.PWA_Plugin::PLUGIN_NAME;

        if($file) {
            return $path.'/'.$file;
        }

        return $path;
    }

    public function getFeedFileName()
    {
        $feedFileName = $this->feedFileName;

        if(!empty($feedFileName)) {
            $feedFileName = str_replace("https", "http", $this->getUploadURL($feedFileName));
        }

        return $feedFileName;
    }

    public function getFeedFilePath()
    {
        $feedFileName = $this->feedFileName;

        if(!empty($feedFileName)) {
            $feedFileName = $this->getUploadPath($feedFileName);
        }

        return $feedFileName;
    }

    public function getServerCurrency()
    {
        return (!empty(self::CURRENCIES[$this->country])) ? self::CURRENCIES[$this->country] : self::DEFAULT_CURRENCY;
    }
}