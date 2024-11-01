<?php

/*
 * class ProfitshareCategory
 *
 * Works with get_term_by helper function and return details about current category
 */
class PWA_Category {
    // category object
    private $category;

    public function __construct($categoryId) {
        $this->category = get_term_by('id', $categoryId, 'product_cat');
    }

    /**
     * Get category id
     *
     * @return int
     */
    public function getId() {
        // using to prevent categories without parents, check getParent function
        if(empty($this->category)) {
            return 0;
        }

        return $this->category->term_id;
    }

    /**
     * Get category name
     *
     * @return string
     */
    public function getName() {
        if (gettype($this->category) == 'object'){
            return $this->category->name;
        }
    }

    /**
     * Get category slug
     *
     * @return string
     */
    public function getSlug() {
        return $this->category->slug;
    }

    /**
     * Get parent category
     *
     * @return PWA_Category
     */
    public function getParent() {
        return new PWA_Category($this->category->parent);
    }

    /**
     * Get average price
     *
     * @return float
     */
    public function getAveragePrice()
    {
        $args = array(
            'post_type'             => 'product',
            'post_status'           => 'publish',
            'ignore_sticky_posts'   => 1,
            'posts_per_page'        => '12',
            'tax_query'             => array(
                array(
                    'taxonomy'      => 'product_cat',
                    'field' => 'term_id',
                    'terms'         => $this->getId(),
                    'operator'      => 'IN'
                ),
                array(
                    'taxonomy'      => 'product_visibility',
                    'field'         => 'slug',
                    'terms'         => 'exclude-from-catalog',
                    'operator'      => 'NOT IN'
                )
            )
        );
        $query = new WP_Query($args);

        $totalProducts = 0;
        $averagePrice = 0;

        while ($query->have_posts()) {
            $query->the_post();
            $productId = get_the_ID();

            $product = new PWA_Product($productId);
            $salePrice = $product->getSalePrice();

            // there was a problem with vouchers
            if(empty($salePrice) || is_array($salePrice)) {
                continue;
            }

            $averagePrice += $salePrice;
            $totalProducts++;
        }

        if($totalProducts > 0) {
            return number_format($averagePrice / $totalProducts, 2, '.', '');
        }

        return 0;
    }
}