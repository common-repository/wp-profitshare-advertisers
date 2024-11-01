<?php

class PWA_Wordpress_Category_Validator extends PWA_Validator {
    private $wordpressCategoryId;
    private $profitshareCategoryId;
    private $commission;

    public function __construct($wordpressCategoryId, $profitshareCategoryId, $commission) {
        $this->wordpressCategoryId = $wordpressCategoryId;
        $this->profitshareCategoryId = $profitshareCategoryId;
        $this->commission = $commission;
    }

    public function isValid() {
        if(!$this->isInteger($this->wordpressCategoryId) || !$this->isWordpressCategoryValid()) {
            $this->errors['wordpressCategoryId'] = "Invalid wordpress category!";
        }

        if(!$this->isInteger($this->profitshareCategoryId) || !$this->isProfitshareCategoryValid()) {
            $this->errors['profitshareCategoryId'] = "Invalid profitshare category!";
        }

        if(!$this->checkCommission($this->commission, 24)) {
            $this->errors['commission'] = "Invalid profitshare commission!";
        }

        return empty($this->errors);
    }

    private function isWordpressCategoryValid() {
        return get_the_category_by_ID($this->wordpressCategoryId);
    }

    private function isProfitshareCategoryValid() {
        return (new PWA_Categories_Model())->getCategory($this->profitshareCategoryId);
    }
}