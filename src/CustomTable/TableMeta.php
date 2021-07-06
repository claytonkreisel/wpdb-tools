<?php

    /*
        TableMeta extends the Table Class. It can be used to create two tables
        that create a model with a meta option.
    */

    namespace WPDBTools\CustomTable;

    use WPDBTools\CustomTable\Table;

    abstract class TableMeta extends Table {

        //Constructor
        public function __construct(){
            parent::__construct();
            $this->meta_table_structure();
        }

        //Defines the default columns for a meta table
        public function meta_columns(){
            return [
                'meta_id' => 'bigint(20) UNSIGNED AUTO_INCREMENT',
                'main_table_id' => 'bigint(20) UNSIGNED DEFAULT 0',
                'meta_key' => 'varchar(255) NULL',
                'meta_value' => 'longtext NULL'
            ];
        }

        //Defines a primary key for the meta table
        public function meta_primary_key(){
            return 'meta_id';
        }

        //Defines the meta table name based off of the Table name
        public function meta_table_name(){
            return $this->table_name() . 'meta';
        }

        //Update the Meta Table
        public function meta_table_structure(){
            global $wpdb;
            $dbv = $this->version();
            $table_name = esc_sql($this->meta_table_name());
            $columns = $this->meta_columns();
            $primary_key = $this->meta_primary_key();
            $curdbv = get_option($table_name . '_db_ver');

            if(version_compare($dbv, $curdbv) || !$curdbv){
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                $charset_collate = $wpdb->get_charset_collate();

                //Stats Table
                $sql = "CREATE TABLE " . $table_name . " (";
                if(is_array($columns)){
                    foreach($columns as $key => $value){
                        $sql .= $key . ' ' . $value . ',' . PHP_EOL;
                    }
                }
                $sql .= 'PRIMARY KEY (' . $primary_key . ')' . PHP_EOL;
                $sql .= ") $charset_collate;";
                dbDelta($sql);

                //Update Database Version
                update_option($table_name . '_db_ver', $dbv);

            }
        }

        //Insert a Row
        public function insert($data){
            global $wpdb;
            $table_name = esc_sql($this->table_name());

            if(is_array($data)){
                if(isset($data[0]) && is_array($data[0])){
                    return $this->insert_multiple($data);
                } else {
                    $meta = false;
                    if(isset($data['_meta'])){
                        $meta = $data['_meta'];
                        unset($data['_meta']);
                    }
                    if($wpdb->insert($table_name, $data)){
                        if($meta){
                            $main_table_id = $wpdb->insert_id;
                            $metas = array();
                            foreach($meta as $mk => $mv){
                                $metas[] = [
                                    'main_table_id' => $main_table_id,
                                    'meta_key' => $mk,
                                    'meta_value' => $mv
                                ];
                            }
                            $this->insert_meta_multiple($metas);
                        }
                        return true;
                    }
                }
            }

            return false;
        }

        //Insert Multiple Rows
        public function insert_multiple($data){

            global $wpdb;
            $table_name = esc_sql($this->table_name());
            // Setup arrays for Actual Values, and Placeholders
            $values = array();
            $place_holders = array();
            $query = "";
            $query_columns = "";
            $query .= 'INSERT INTO ' . $table_name . ' (';
            $metas = false;
            foreach($data as $count => $row_array){

                if(isset($row_array['_meta'])){
                    $metas[$count] = $row_array['_meta'];
                    unset($row_array['_meta']);
                }

                foreach($row_array as $key => $value) {

                    if($count == 0) {
                        if($query_columns) {
                            $query_columns .= ",".$key."";
                        } else {
                            $query_columns .= "".$key."";
                        }
                    }

                    $values[] =  $value;

                    if(is_numeric($value)) {
                        if(isset($place_holders[$count])) {
                            $place_holders[$count] .= ", '%d'";
                        } else {
                            $place_holders[$count] .= "( '%d'";
                        }
                    } else {
                        if(isset($place_holders[$count])) {
                            $place_holders[$count] .= ", '%s'";
                        } else {
                            $place_holders[$count] .= "( '%s'";
                        }
                    }
                }
                // mind closing the GAP
                $place_holders[$count] .= ")";
            }

            $query .= " $query_columns ) VALUES ";

            $query .= implode(', ', $place_holders);

            if($wpdb->query($wpdb->prepare($query, $values))){
                if($metas && is_array($metas)){
                    $last_id = $wpdb->insert_id;
                    $mdata = array();
                    $row_count = count($data);
                    foreach($metas as $mindex => $meta){
                        $mtid = $last_id + $mindex;
                        foreach($meta as $mk => $mv){
                            $mdata[] = [
                                'main_table_id' => $mtid,
                                'meta_key' => $mk,
                                'meta_value' => $mv
                            ];
                        }
                    }
                    $this->insert_meta_multiple($mdata);
                }
                return true;
            } else {
                return false;
            }

        }

        //Insert Meta
        public function insert_meta($data){
            global $wpdb;
            $meta_table_name = esc_sql($this->meta_table_name());
            if(is_array($data)){
                if($wpdb->insert($meta_table_name, $data)){
                    return true;
                }
            }
            return false;
        }

        //Insert Meta Multiple
        public function insert_meta_multiple($data){

            global $wpdb;
            $meta_table_name = esc_sql($this->meta_table_name());
            // Setup arrays for Actual Values, and Placeholders
            $values = array();
            $place_holders = array();
            $query = "";
            $query_columns = "";
            $query .= 'INSERT INTO ' . $meta_table_name . ' (';
            foreach($data as $count => $row_array){

                foreach($row_array as $key => $value) {

                    if($count == 0) {
                        if($query_columns) {
                            $query_columns .= ",".$key."";
                        } else {
                            $query_columns .= "".$key."";
                        }
                    }

                    $values[] =  $value;

                    if(is_numeric($value)) {
                        if(isset($place_holders[$count])) {
                            $place_holders[$count] .= ", '%d'";
                        } else {
                            $place_holders[$count] .= "( '%d'";
                        }
                    } else {
                        if(isset($place_holders[$count])) {
                            $place_holders[$count] .= ", '%s'";
                        } else {
                            $place_holders[$count] .= "( '%s'";
                        }
                    }
                }
                // mind closing the GAP
                $place_holders[$count] .= ")";
            }

            $query .= " $query_columns ) VALUES ";

            $query .= implode(', ', $place_holders);

            if($wpdb->query($wpdb->prepare($query, $values))){
                return true;
            } else {
                return false;
            }

        }

        //Deletion of a row in the main table


    }
