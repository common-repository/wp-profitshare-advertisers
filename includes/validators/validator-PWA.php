<?php

abstract class PWA_Validator {
    protected $errors = array();

    public function isInteger($value) {
        return is_int($value);
    }

    public function checkCommission($value, $minValue) {
        if(empty($value)) {
            return true;
        }

        if(!$this->isInteger($value)) {
            return false;
        }

        if($value < $minValue || $value > 50) {
            return false;
        }

        return true;
    }

    public function getErrors() {
        return $this->errors;
    }

    abstract public function isValid();
}