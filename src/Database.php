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
                    if(is_float($value)){
                        $placeholder = '%f';
                    }
                    $placeholder = '%d';
                }
                return $placeholder;
            }

    }
