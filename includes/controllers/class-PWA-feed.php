<?php
/*
 * class ProfitshareFeed
 *
 * Convert all WooCommerceProducts to ProfitshareProducts
 *
 */
class PWA_Feed {
    // list of products
    private $products = array();

    // csv file path
    private $csvFileName;

    // products filters
    const PRODUCTS_FILTERS = array(
        'orderby' => 'date',
        'order' => 'DESC',
        'limit' => -1,
        'visibility' => 'visible',
        'status' => 'publish',
    );

    public function __construct()
    {
        if(class_exists("WC_Product_Query")) {
            $this->products = $this->getProductsQuery()->get_products();
        } else {
            $this->products = wc_get_products(self::PRODUCTS_FILTERS);
        }

        $this->csvFileName = (new PWA_Settings())->getFeedFilePath();
        $this->update();
    }

    /**
     * Get products query.
     *
     * @return WC_Product_Query
     */
    private function getProductsQuery()
    {
        return new WC_Product_Query(self::PRODUCTS_FILTERS);
    }

    /**
     * Update WCProducts to ProfitshareProducts
     */
    private function update()
    {
        foreach($this->products as &$product) {
            $profitshareProduct = new PWA_Product($product);
            $product = $profitshareProduct->get();
        }
    }

    /**
     * Get current products
     *
     * @return array of ProfitshareProducts
     */
    public function get()
    {
        return $this->products;
    }

    /*
     * Save profitshare feed
     */
    public function save()
    {
        if(empty($this->csvFileName)){
            return;
        }

        $fp = fopen($this->csvFileName, 'w+');

        foreach($this->products as $product) {
            fputcsv($fp, $product);
        }

        fclose($fp);
    }
}