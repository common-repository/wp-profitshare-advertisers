<?php

class PWA_Categories_Model extends PWA_Model {
    protected $table_name = "profitshare_categories";

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';

    public function getCategories($status = self::STATUS_ACTIVE) {
        $whereConditions = array();

        if($status) {
            $whereConditions = array("status = '" . $status ."'");
        }

       return $this->select($whereConditions);
    }

    public function getCategory($id, $status = self::STATUS_ACTIVE) {
        $whereConditions = array(
            "id_category = " . (int) $id
        );

        if($status) {
            $whereConditions = array_merge($whereConditions, array(
                    "status = '" . $status ."'",
                )
            );
        }

        return $this->select($whereConditions);
    }

    public function add($category) {
        $category = $this->sanitize($category, self::STATUS_ACTIVE);

        if($this->getCategory($category['id_category'], null)) {
            $keys = array(
                'id_category = '. (int) $category['id_category']
            );

            $this->update($category, $keys);
        }

        $this->insert($category);
    }

    public function deactivateCategories() {
        $this->update(array("status" => "'".self::STATUS_INACTIVE."'"));
    }

    public function getwordpressInactiveCategories() {
        global $wpdb;

        $query = "
            SELECT `wordpress_category_id`
            FROM ".$this->getTableName()." AS ppc
            LEFT JOIN ".(new PWA_Wordpress_Categories_Model())->getTableName()." AS pppc ON (ppc.id_category = pppc.profitshare_category_id)
            WHERE ppc.status = ".$this->getStringValue(self::STATUS_INACTIVE)." 
            AND pppc.wordpress_category_id IS NOT NULL";

        $categories = $wpdb->get_results($query);

        $results = array();

        foreach($categories as $category) {
            $results[$category['wordpress_category_id']] = $category;
        }

        return $results;
    }

    private function sanitize($category, $status) {
        return array(
            'id_category' => (int) $category['id_category'],
            'id_parent' => (int) $category['id_parent'],
            'name' => $this->getStringValue(htmlspecialchars(strip_tags($category['name']))),
            'status' => $this->getStringValue(self::STATUS_ACTIVE),
        );
    }
}