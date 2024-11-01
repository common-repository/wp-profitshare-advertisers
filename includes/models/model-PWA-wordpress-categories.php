<?php

class PWA_Wordpress_Categories_Model extends PWA_Model {
    protected $table_name = "profitshare_wordpress_categories";

    public function getCategories($wordpressCategoryId = null, $profitshareCategoryId = null) {
        $whereConditions = array();

        if(!empty($wordpressCategoryId)) {
            $whereConditions[] = "wordpress_category_id = ". (int) $wordpressCategoryId;
        }

        if(!empty($profitshareCategoryId)) {
            $whereConditions[] = "profitshare_category_id = ". (int) $profitshareCategoryId;
        }

        $categories = $this->select($whereConditions);
        $result = array();

        foreach($categories as $category) {
            $result[$category['wordpress_category_id']] = $category;
        }

        return $result;
    }

    public function add($profitshareCategoryId, $wordpressCategoryId, $categoryCommission = null) {
        $values = array(
            'profitshare_category_id' => (int) $profitshareCategoryId,
            'wordpress_category_id' => (int) $wordpressCategoryId,
            'category_commission' => (int) $categoryCommission
        );

        // update
        if(!empty($this->getCategories($wordpressCategoryId))) {
            $keys = array(
                'wordpress_category_id = ' .  (int) $wordpressCategoryId,
            );

            return $this->update($values, $keys);
        }

        // insert
        return $this->insert($values);
    }
}