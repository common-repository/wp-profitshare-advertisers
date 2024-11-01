<?php

class ExchangeRatesAPI
{
    // currency currency
    private $baseCurrency;

    // api url
    private $url = "https://api.exchangeratesapi.io/latest";

    // default currency value
    const DEFAULT_CURRENCY = "RON";

    /**
     * ExchangeRatesAPI constructor.
     *
     * @param string $baseCurrency
     */
    public function __construct($baseCurrency = self::DEFAULT_CURRENCY)
    {
        // build URL
        $this->url .= "?base={$baseCurrency}";

        // set base currency
        $this->baseCurrency = $baseCurrency;
    }

    /**
     * @param string $currency
     * @return float
     * @throws Exception
     */
    public function getRate($currency = self::DEFAULT_CURRENCY)
    {
        $context = stream_context_create(array('https'=>
            array(
                'timeout' => 5,
            )
        ));

        try {
            $result = @file_get_contents($this->url, false, $context);
        } catch(Exception $e) {
            throw new Exception("ExchangeRatesAPI does not respond!");
        }

        if(empty($result)) {
            throw new Exception("ExchangeRatesAPI does not respond!");
        }

        $result = json_decode($result, true);

        if(empty($result['rates'][$currency])) {
            throw new Exception("ExchangeRatesAPI - There are no results!");
        }

        return (float) $result['rates'][$currency];
    }
}