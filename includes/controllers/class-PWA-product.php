<?php

/*
 * class ProfitshareProduct
 *
 * Convert a WooCommerce product to ProfitshareProduct
 */
class PWA_Product {
    // Wordpress WooCommerce object
    private $wooCommerceProduct;

    // Array of product details
    private $product = array();

    // ProfitshareCategory of product
    private $category;

    // Variation id
    private $variationId;

    // Availability messages
    const AVAILABILITY_IN_STOCK = "in stock";
    const AVAILABILITY_OUT_OF_STOCK = "out of stock";

    // Because in woocommerce we don't have manufacturer then this one will be used for all products
    // Default manufacturer name is shop name
    const DEFAULT_MANUFACTURER_ID = 1;

    public function __construct($product, $variationId = null) {
        // take woocommerce product by id
        if(is_int($product)) {
            $product = wc_get_product($product);
        }

        if (!empty($variationId) && is_int($variationId)) {
            $this->variationId = $variationId;
        }

        $this->wooCommerceProduct = $product;
        $this->update();
    }

    /**
     * Update current WProduct to ProfitshareProduct
     */
    private function update() {
        $vatValue = (get_option(PWA_Plugin::PLUGIN_OPTION_VAT_KEY, 0) / 100) + 1;

        $price = $this->wooCommerceProduct->get_regular_price();

        $salePrice = 0;

        if($this->wooCommerceProduct->get_sale_price()) {
            $salePrice = $this->wooCommerceProduct->get_sale_price() / $vatValue;
        }

        if(empty($price)) {
            $price = $this->wooCommerceProduct->get_price();
        }

        if (!empty($this->variationId)) {
            $variations = new WC_Product_Variation($this->variationId);

            if(!empty($variations->sale_price)) {
                $salePrice = $variations->sale_price / $vatValue;
            }

            if(!empty($variations->regular_price)) {
                $price = $variations->regular_price;
            }

            if (empty($variations->sale_price) && !empty($variations->regular_price)) {
                $salePrice = $variations->regular_price / $vatValue;
            }
        }

        $priceWithoutTva = $price / $vatValue;

        if(empty($priceWithoutTva)) {
            $priceWithoutTva = 0;
        }

        $quantity = (empty($this->wooCommerceProduct->get_stock_quantity())) ? 0 : $this->wooCommerceProduct->get_stock_quantity();

        $availability = (
            $quantity > 0 ||
            $this->wooCommerceProduct->get_stock_status() == "instock"
        ) ? self::AVAILABILITY_IN_STOCK : self::AVAILABILITY_OUT_OF_STOCK;

        $this->category = new PWA_Category($this->wooCommerceProduct->get_category_ids()[0]);

        $this->product = array(
            'categoryId'        => $this->category->getId(),
            'categoryName'      => $this->category->getName(),
            'parent_category'   => $this->category->getParent()->getName(),
            'manufacturer'      => get_bloginfo('name'),
            'manufacturer_id'   => 0,
            'model'             => ((!empty($this->wooCommerceProduct->get_sku())) ? urlencode($this->wooCommerceProduct->get_sku()) : $this->wooCommerceProduct->get_id()),
            'id'                => $this->wooCommerceProduct->get_id(),
            'name'              => $this->wooCommerceProduct->get_name(),
            'description'       => substr(apply_filters('get_the_excerpt', $this->wooCommerceProduct->post->post_content),0,500),
            'link'              => get_permalink($this->wooCommerceProduct->get_id()),
            'image'             => (empty($productImage = wp_get_attachment_url($this->wooCommerceProduct->get_image_id()))) ? '-' : $productImage,
            'priceVat'          => number_format($priceWithoutTva, 2, '.', ''),
            'price'             => number_format($price, 2, '.', ''),
            'priceDiscounted'   => number_format($salePrice, 2, '.', ''),
            'currency'          => get_option("woocommerce_currency"),
            'availability'      => $availability,
            'freeShipping'      => 0,
            'giftIncluded'      => 0,
            'status'            => 1,
        );
    }

    /**
     * Get current product converted to Profitshare format
     *
     * @return array
     */
    public function get($parameter = null)
    {
        if($parameter && isset($this->product[$parameter])) {
            return $this->product[$parameter];
        }

        return $this->product;
    }

    /**
     * Get current product sale price
     *
     * @return float
     */
    public function getSalePrice()
    {
        $price = $this->get('priceDiscounted');

        if (empty($price) || $price == '0.00') {
            $price = $this->get('price');
        }

        if (empty($price) || is_array($price)) {
            $price = 0;
        }

        $productSalePrice = ($this->wooCommerceProduct->get_sale_price()) ? $this->wooCommerceProduct->get_sale_price() : $price;
        $exchangeValue = (new PWA_Settings())->getExchangeValue();

        return $exchangeValue * $productSalePrice;
    }
}