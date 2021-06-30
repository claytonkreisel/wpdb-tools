<?php

    /*
        Table Class
    */

    namespace WPDBTools\CustomTable;

    class Table {

        //Initialize the table
        public static function init(){
            self::update_table(self::table());
        }

        //Singleton Class
        public static function get_instance() {

            static $instance = null;

            if ( $instance == null ) {
                $instance = new self();
            }

            $instance::init();

            return $instance;
        }

        //Return name of main table
        private static function name(){
            throw new Exception('No table name established in table object. Create a private static function named "name()" that returns the name of the table you wish to work on in this class');
        }

        //Return table columns
        private static function columns(){
            throw new Exception('No table columns established in the table object. Create a private static function named "columns()" that returns an array of columns and there properties')
        }

        //Return the table key
        private static function primary_key(){
            throw new Exception('No primary key established in the table object. Create a private static function named "primary_key()" that returns the id of the column to use for the key')
        }

        //Return the table version for the database update
        private static function version(){
            throw new Exception('No table version established in the table object. Create a private static function named "version()" that returns the table version number')
        }

        //Update the Main Table and Main Meta Table
        private static function update_table(){
            global $wpdb;
            $dbv = self::version();
            $table_name = self::name();
            $columns = self::columns();
            $primary_key = self::primary_key();
            $curdbv = get_option($table_name . '_db_ver');

            if($dbv > $curdbv || !$curdbv){
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                $charset_collate = $wpdb->get_charset_collate();

                //Stats Table
                $sql = "CREATE TABLE " . $table_name . " (";
                if(is_array($columns)){
                    foreach($columns as $key => $value){
                        $sql .= $key . ' ' . $value . ',';
                    }
                }
                $sql .= 'PRIMARY KEY (' . $primary_key . ')';
                $sql .= ") $charset_collate;";
                dbDelta($sql);

                //Update Database Version
                update_option($table_name . '_db_ver', $dbv);

            }
        }

    }
