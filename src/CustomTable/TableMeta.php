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

        public function meta_columns(){
            return [
                'meta_id' => 'bigint(20) UNSIGNED AUTO_INCREMENT',
                'main_table_id' => 'bigint(20) UNSIGNED DEFAULT 0',
                'meta_key' => 'varchar(255) NULL',
                'meta_value' => 'longtext NULL'
            ];
        }

        public function meta_primary_key(){
            return 'meta_id';
        }

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

    }
