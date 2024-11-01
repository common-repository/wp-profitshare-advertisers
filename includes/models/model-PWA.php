<?php

abstract class PWA_Model {
    // return table name
    public function getTableName() {
        global $wpdb;

        return $wpdb->prefix.$this->table_name;
    }

    // select
    public function select($whereConditions = array()) {
        global $wpdb;

        $query = 'select * from '.$this->getTableName();

        foreach($whereConditions as $key => $condition) {
            $query .= ($key == 0) ? " WHERE " : ' AND ';

            $query .= $condition;
        }

        return $wpdb->get_results($query);
    }

    public function update($values, $keys = array()) {
        global $wpdb;

        $setValues = array();

        foreach($values as $key => $value) {
            $setValues[] = $key . " = ".$value;
        }

        $query = 'UPDATE `'.$this->getTableName().'` 
             SET '.implode(", ", $setValues);

        if($keys) {
            $query .= ' WHERE ('.implode(", ", $keys).');';
        }

        return $wpdb->query($query);
    }

    public function insert($values) {
        global $wpdb;

        return $wpdb->query(
            'INSERT INTO `'.$this->getTableName().'` ('.implode(", ", array_keys($values)).')
             VALUES ('.implode(", ", $values).');'
        );
    }

    public function getStringValue($value) {
        return "'".$value."'";
    }
}