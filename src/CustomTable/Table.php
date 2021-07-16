<?php

    /*
        Table Class
    */

    namespace WPDBTools\CustomTable;

    use Exception;
    use WPDBTools\Helpers;

    abstract class Table {

        //Initialize the table
        public function __construct(){
            $this->table_structure();
        }

        //Return name of main table
        abstract function name();

        //Return table columns
        abstract function columns();

        //Return the table key
        abstract function primary_key();

        //Return the table version for the database update
        abstract function version();

        //Returns the full name with the proper prefix
        public function table_name(){
            global $wpdb;
            $name = $this->name();
            if(strpos($name, $wpdb->prefix) === 0){
                return $name;
            }
            return $wpdb->prefix . $name;
        }

        //Update the Main Table and Main Meta Table
        public function table_structure(){
            global $wpdb;
            $dbv = $this->version();
            $table_name = esc_sql($this->table_name());
            $columns = $this->columns();
            $primary_key = $this->primary_key();
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
                    if($wpdb->insert($table_name, $data)){
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
            foreach($data as $count => $row_array){

                foreach($row_array as $key => $value) {

                    if($count == 0) {
                        if($query_columns) {
                            $query_columns .= ",".$key."";
                        } else {
                            $query_columns .= "".$key."";
                        }
                    }

                    $values[] = Helpers::prepare_value_storage($value);

                    if(isset($place_holders[$count])) {
                        $place_holders[$count] .= ", " . Helpers::prepare_placeholder($value);
                    } else {
                        $place_holders[$count] .= "( " . Helpers::prepare_placeholder($value);
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

        //Delete a Row
        public function delete($data){
            global $wpdb;
            $table_name = esc_sql($this->table_name());

            if(!is_array($data)){
                if($wpdb->query($wpdb->prepare('DELETE FROM ' . $table_name . ' WHERE ' . $data))){
                    return true;
                }
            } else {
                if(isset($data[0]) && is_array($data[0])){
                    $sql = 'DELETE FROM ' . $table_name . ' WHERE ';
                    $values = array();
                    foreach($data as $set){
                        $v = $set['value'];
                        $placeholder = Helpers::prepare_placeholder($v);
                        $compare = '=';
                        $operator = "AND";
                        if(isset($set['operator'])){
                            $operator = $set['operator'];
                        }
                        if(isset($set['compare'])){
                            $compare = $set['compare'];

                            if($compare == "%LIKE%"){
                                $v = "%" . $v . "%";
                                $compare = "LIKE";
                            }

                            if($compare == "%LIKE"){
                                $v = "%" . $v;
                                $compare = "LIKE";
                            }

                            if($compare == "LIKE%"){
                                $v .= "%";
                                $compare = "LIKE";
                            }
                        }
                        $values[] = $v;
                        $k = '`' . $set['key'] . '`';
                        if(isset($set['is_numeric']) && $set['is_numeric']){
                            $k = 'ABS(' . $k . ')';
                        }
                        $sql .= $k . ' ' . $compare . ' ' . $placeholder . ' ' . $operator . ' ';
                    }
                    $sql = rtrim($sql, ' AND ');
                    $sql = rtrim($sql, ' OR ');
                    if($wpdb->query($wpdb->remove_placeholder_escape($wpdb->prepare($sql, $values)))){
                        return true;
                    }
                } else {
                    if($wpdb->delete($table_name, $data)){
                        return true;
                    }
                }
            }
            return false;
        }

        //Delete all Rows
        public function delete_all(){
            global $wpdb;
            $table_name = esc_sql($this->table_name());
            if($wpdb->query($wpdb->prepare('DELETE FROM ' . $table_name))){
                return true;
            }
            return false;
        }

        //Get a row
        public function select($data, $order = false, $return_type = ARRAY_A){
            global $wpdb;
            $table_name = esc_sql($this->table_name());

            //If data is an array
            if(is_array($data)){
                return $this->select_array($data, 'AND', $order, $return_type);
            } else {
                $sql = 'SELECT * FROM ' . $table_name . ' WHERE ' . $data;
                if($order){
                    $sql = $this->add_order_to_sql($sql, $order);
                }
                return $wpdb->get_results($sql, $return_type);
            }
            return false;
        }

        //Get a row using OR as operator
        public function select_or($data, $order = false, $return_type = ARRAY_A){
            global $wpdb;
            $table_name = esc_sql($this->table_name());

            //If data is an array
            if(is_array($data)){
                return $this->select_array($data, 'OR', $order, $return_type);
            }
            return false;
        }

        //Get a set of rows using an array of data
        private function select_array($data, $operator = 'AND', $order = false, $return_type = ARRAY_A){
            global $wpdb;
            $table_name = esc_sql($this->table_name());
            $sql = 'SELECT * FROM ' . $table_name . ' WHERE ';
            $values = array();

            if(!is_array($data[0])){
                foreach($data as $column_key => $column_value){
                    $placeholder = Helpers::prepare_placeholder($column_value);
                    $values[] = $column_value;
                    $sql .= '`'.$column_key.'` = ' . $placeholder . ' ' . $operator . ' ';
                }
            } else {
                foreach($data as $set){
                    $v = $set['value'];
                    $placeholder = Helpers::prepare_placeholder($v);
                    $compare = '=';
                    if(isset($set['operator'])){
                        $operator = $set['operator'];
                    }
                    if(isset($set['compare'])){
                        $compare = $set['compare'];

                        if($compare == "%LIKE%"){
                            $v = "%" . $v . "%";
                            $compare = "LIKE";
                        }

                        if($compare == "%LIKE"){
                            $v = "%" . $v;
                            $compare = "LIKE";
                        }

                        if($compare == "LIKE%"){
                            $v .= "%";
                            $compare = "LIKE";
                        }
                    }
                    $values[] = $v;
                    $k = '`' . $set['key'] . '`';
                    if(isset($set['is_numeric']) && $set['is_numeric']){
                        $k = 'ABS(' . $k . ')';
                    }
                    $sql .= $k . ' ' . $compare . ' ' . $placeholder . ' ' . $operator . ' ';
                }
            }
            $sql = rtrim($sql, ' AND ');
            $sql = rtrim($sql, ' OR ');
            if($order){
                $sql = $this->add_order_to_sql($sql, $order);
            }
            return $wpdb->get_results($wpdb->remove_placeholder_escape($wpdb->prepare($sql, $values)), $return_type);
        }

        //Get all rows
        public function select_all($order = false, $return_type = ARRAY_A){
            global $wpdb;
            $table_name = esc_sql($this->table_name());
            $sql = 'SELECT * FROM ' . $table_name;
            if($order){
                $sql = $this->add_order_to_sql($sql, $order);
            }
            return $wpdb->get_results($sql, $return_type);
        }

        //Adds the ORDER text to the SQL query for selecting
        public function add_order_to_sql($sql, $order){
            if(isset($order['orderby'])){
                $direction = 'ASC';
                if(isset($order['order'])){
                    $direction = $order['order'];
                }
                $orderby = '`' . esc_sql($order['orderby']) . '`';
                if(isset($order['is_numeric']) && $order['is_numeric']){
                    $orderby = 'ABS(' . $orderby . ')';
                }
                $sql .= ' ORDER BY ' . $orderby . ' ' . esc_sql($direction);
            } elseif(isset($order[0]) && is_array($order[0])) {
                $order_clause = ' ORDER_BY ';
                foreach($order as $rule){
                    $orderby = '`' . esc_sql($rule['orderby']) . '`';
                    if(isset($rule['is_numeric']) && $rule['is_numeric']){
                        $orderby = 'ABS(' . $orderby . ')';
                    }
                    $order_clause .= $orderby . ' ' . esc_sql($rule['order']) . ', ';
                }
                $order_clause = trim($order_clause, ', ');
                $sql .= $order_clause;
            }
            return $sql;
        }

        //Update Row(s)
        public function update($data, $where){
            global $wpdb;
            $table_name = esc_sql($this->table_name());

            //String provided for WHERE
            if(!is_array($where)){
                //Do the data SQL
                $values = array();
                $sql = "UPDATE " . $table_name . " SET ";
                foreach($data as $dk => $dv){
                    $placeholder = Helpers::prepare_placeholder($dv);
                    $sql .= "`" . $dk . "` = " . $placeholder;
                    if ($dk === array_key_last($data)){
                        $sql .= " ";
                    } else {
                        $sql .= ", ";
                    }
                    $values[] = $dv;
                }
                $sql .= 'WHERE ' . $where;
                return $wpdb->get_results($wpdb->remove_placeholder_escape($wpdb->prepare($sql, $values)));
            }

            //Array provided for Where
            if(isset($where[0])){

                //Do the data SQL
                $values = array();
                $sql = "UPDATE " . $table_name . " SET ";
                foreach($data as $dk => $dv){
                    $placeholder = Helpers::prepare_placeholder($dv);
                    $sql .= "`" . $dk . "` = " . $placeholder;
                    if ($dk === array_key_last($data)){
                        $sql .= " ";
                    } else {
                        $sql .= ", ";
                    }
                    $values[] = $dv;
                }

                //Do the Where SQL
                $sql .= 'WHERE ';
                if(!is_array($where[0])){
                    foreach($where as $column_key => $column_value){
                        $placeholder = Helpers::prepare_placeholder($column_value);
                        $values[] = $column_value;
                        $sql .= '`'.$column_key.'` = ' . $placeholder . ' ' . $operator . ' ';
                    }
                } else {
                    foreach($where as $set){
                        $v = $set['value'];
                        $placeholder = Helpers::prepare_placeholder($v);
                        $compare = '=';
                        $operator = 'AND';
                        if(isset($set['operator'])){
                            $operator = $set['operator'];
                        }
                        if(isset($set['compare'])){
                            $compare = $set['compare'];

                            if($compare == "%LIKE%"){
                                $v = "%" . $v . "%";
                                $compare = "LIKE";
                            }

                            if($compare == "%LIKE"){
                                $v = "%" . $v;
                                $compare = "LIKE";
                            }

                            if($compare == "LIKE%"){
                                $v .= "%";
                                $compare = "LIKE";
                            }
                        }
                        $values[] = $v;
                        $k = '`' . $set['key'] . '`';
                        if(isset($set['is_numeric']) && $set['is_numeric']){
                            $k = 'ABS(' . $k . ')';
                        }
                        $sql .= $k . ' ' . $compare . ' ' . $placeholder . ' ' . $operator . ' ';
                    }
                    $sql = rtrim($sql, ' AND ');
                    $sql = rtrim($sql, ' OR ');
                }
                return $wpdb->get_results($wpdb->remove_placeholder_escape($wpdb->prepare($sql, $values)), $return_type);
            } else {
                if($wpdb->update($table_name, $data, $where)){
                    return true;
                }
            }
            return false;
        }

        //Remove a column from the table
        function remove_column($column_name){
            global $wpdb;
            $table_name = esc_sql($this->table_name());
            return $wpdb->query($wpdb->prepare('ALTER TABLE ' . $table_name . ' DROP COLUMN ' . esc_sql($column_name)));
        }

    }
