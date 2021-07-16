<?php

    /*
        Mostly contains helper functions useful when using $wpdb
    */

    namespace WPDBTools;

    class Helpers{

            //Gives you the proper sprintf placholder from value
            public static function prepare_placeholder($value){
                $placeholder = '"%s"';
                if(is_numeric($value)){
                    $placeholder = '%d';
                    if(strpos($value, '.') != false){
                        $value = floatval($value);
                        $placeholder = '%f';
                    }
                }
                return $placeholder;
            }

            //Prepares a value for storage
            public static function prepare_value_storage($value){

                if(is_array($value) || is_object($value)){
                    return serialize($value);
                }
                return $value;

            }

            //Prepare values for storage with depth
            public static function prepare_value_storage_depth($data){

                if(!is_array($data) && ! is_object($data)) return $data;

                $values = array();

                foreach($data as $k => $v){
                    if(is_array($v) || is_object($v)){
                        $values[$k] = serialize($v);
                    } else {
                        $values[$k] = $v;
                    }
                }

                return $values;

            }

            //Clean a value from storage
            public static function clean_value_storage($value){

                if(is_serialized( $value )){
                    return unserialize($value);
                }
                return $value;

            }

    }
