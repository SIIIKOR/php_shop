<?php

class Db_Loader
/**
 * Class used to comunicate with database.
 * Very often used throughout whole app.
 */
{
    private $db_host;
    private $db_port;
    private $db_name;
    private $db_user_name;
    private $db_password;
    private $schema_name;

    function __construct()
    /**
     * Predefined, could only be changed by hand.
     */
    {
        $this->db_host = "localhost";
        $this->db_port = 5432;
        $this->db_name = "sklep_php";
        $this->db_user_name = "mateusz";
        $this->db_password = 9326;
        $this->schema_name = "public";
    }

    function set_db_login_data($db_host, $db_port, $db_name, $db_user_name, $db_password)
    /**
     * Method used to set|change all important information used to connect with
     * database via pdo.
     */
    {
        $this->db_host = $db_host;
        $this->db_port = $db_port;
        $this->db_name = $db_name;
        $this->db_user_name = $db_user_name;
        $this->db_password = $db_password;
    }

    function set_records_per_page($records_per_page)
    {
        /**
         * Sets how many records at max will be displayed at once.
         * It's used in combination with set_page_number.
         */
        $this->records_per_page = $records_per_page;
    }

    function set_page_number($page_num)
    {
        /**
         * Sets for which page data should be fetched.
         * It's used in combination with set_records_per_page.
         */
        $this->page_num = $page_num;
    }

    function set_preparer($preparer)
    {
        /**
         * Dependency injection.
         * Method used to inject preparer class.
         */
        $this->prep = $preparer;
    }

    function set_handler($handler)
    {
        /**
         * Dependency injection.
         * Method used to inject handler class.
         */
        $this->handl = $handler;
    }

    private function get_db_string()
    {
        /**
         * Method that returns string required to establish connection with database.
         * 
         * @return string
         */
        return "pgsql:host={$this->db_host};port={$this->db_port};dbname={$this->db_name};";
    }

    private function get_pdo_obj()
    {
        /**
         * Method that returns PDO object if connection is establishede else false.
         * 
         * @return bool|array
         */
        $conn = new PDO($this->get_db_string(), $this->db_user_name, $this->db_password);
        // enables psql to throw errors
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    }

    private function run_query($query)
    {
        /**
         * Method that runs query on a given table from given database.
         * Sql injection prone.
         * 
         * @param string $query string conteining psql query
         * @return bool|array
         */
        $conn = $this->get_pdo_obj();
        if ($conn) {
            try {
                $prepared_query = $conn->prepare($query);
                $prepared_query->execute();
                $data = $prepared_query->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                $text = "{$e->getMessage()}<br>Try again.";
                $err_mess = new Text_Field($text, "err_mess");
                $err_mess->create();
                return FALSE;
            }
        }
        $conn = NULL;
        if (is_array($data)) {
            return $data;
        }
        return TRUE;
    }

    function get_table_names()
    {
        /**
         * Method that returns table names from database. 
         * Safe.
         * 
         * @return array
         */
        $query = "SELECT table_name
                    FROM information_schema.tables
                    WHERE table_schema='{$this->schema_name}'
                    AND table_type='BASE TABLE';";
        $data = $this->run_query($query);
        $out = [];
        foreach ($data as $row) {
            foreach ($row as $el) {
                array_push($out, $el);
            }
        }
        return $out;
    }

    function get_table_row_amount($table_name)
    {
        /**
         * Method that returns row count input a given table.
         * Safe.
         * 
         * @param string $table_name
         * @return int
         */
        $query = "SELECT count(*)
                  FROM {$table_name}";
        $data = $this->run_query($query);
        return array_values($data[0])[0];
    }

    function get_col_names($table_name)
    {
        /**
         * Method that returns row names from a given table.
         * Sage.
         * 
         * @param string $table_name
         * @return array
         */
        $query = "SELECT column_name
                  FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_NAME = '{$table_name}'
                  AND table_schema='{$this->schema_name}';";
        $data = $this->run_query($query);
        $out = [];
        foreach ($data as $d) {
            array_push($out, $d["column_name"]);
        }
        return $out;
    }

    function get_primary_key_names()
    {
        /**
         * Method that returns table primary key names.
         * Safe.
         * 
         * @return array
         */
        $query = "SELECT conrelid::regclass AS table_name,
                            conname AS primary_key,
                            pg_get_constraintdef(oid)
                  FROM   pg_constraint
                  WHERE  contype = 'p'
                  AND    connamespace = '{$this->schema_name}'::regnamespace
                  ORDER  BY conrelid::regclass::text, contype DESC;";
        $data = $this->run_query($query);
        $out = [];
        // output is a bit complicated, that's why regex is used.
        foreach ($data as $row) {
            $val = $row["pg_get_constraintdef"];
            preg_match("/\((.*)\)/", $val, $matches);
            $pk_list = explode(", ", $matches[1]);
            $pk_set = [];
            foreach ($pk_list as $el) {
                $pk_set[$el] = TRUE;
            }
            $out[$row["table_name"]] = $pk_set;
        }
        return $out;
    }

    function get_table_contents($table_name, $condition=NULL, $col_names="*", 
                           $distinct=FALSE, $page_num=NULL, $records_per_page=NULL)
    {
        /**
         * Method used to create and run queries that are used to select records
         * from a given table.
         * 
         * @param string $table_name
         * @param string $condition psql format condition, usualy prepered by special method
         * @param string|array $col_names array or string that specifies which columns to select
         * @param bool $distinct defines whether selection should be distinct or not
         * @param integer $page_num
         * @param integer $records_per_page
         * @return bool|array
         */
        $col_name = $col_names;
        if (is_array($col_names)) {
            $selection_str = "";
            $i = 0;
            foreach ($col_names as $name) {
                if ($i == 0) {
                    $selection_str .= "$name";
                } else {
                    $selection_str .= ", $name";
                }
                $i++;
            }
            $col_name = $selection_str;
        }
        if ($distinct) {
            $selection = "SELECT DISTINCT {$col_name}";
        } else {
            $selection = "SELECT {$col_name}";
        }
        $query = "{$selection} FROM {$table_name}";
        if (!is_null($condition)) {
            $query .= " WHERE {$condition}";
        }
        if (!is_null($records_per_page)) {
            $offset = 0 + ($page_num) * $records_per_page;
            $query .= " LIMIT {$records_per_page} OFFSET {$offset}";
        }
        $query .= ";";
        return $this->run_query($query);
    }

    private function insert_table_row($table_name, $values)
    {
        /**
         * Method used to insert record into given table.
         * 
         * @param string $table_name
         * @param string $values psql acceptable values
         * @return bool
         */
        $query = "INSERT INTO {$table_name}
                  VALUES {$values};";
        return $this->run_query($query);
    }

    private function update_table_row($table_name, $condition, $values)
    {
        /**
         * Method used to udpate record in given table.
         * 
         * @param string $table_name
         * @param string $condition psql acceptable where condition
         * @return bool
         */
        $query = "UPDATE {$table_name}
                  SET {$values}
                  WHERE {$condition};";
        return $this->run_query($query);
    }

    private function delete_table_row($table_name, $condition)
    {
        /**
         * Method used to delete record in given table.
         * 
         * @param string $table_name
         * @param string $condition psql acceptable where condition
         * @return bool
         */
        $query = "DELETE FROM $table_name
                  WHERE {$condition};";
        return $this->run_query($query);
    }

    function perform_action($action, $table_name, $condition=NULL, 
                            $update_values=NULL, $insert_values=NULL) 
    {
        /**
         * Method that based on $action decides what operation should be performed
         * 
         * @param string @action update|delete|add
         * @param string $condition psql acceptable where condition
         * @param string $update_values psql acceptable values
         * @param string $insert_values psql acceptable values
         * @return void
         */
        if ($action == "update") {
            $this->update_table_row($table_name, $condition, $update_values);
        } elseif ($action == "delete") {
            $this->delete_table_row($table_name, $condition);
        } elseif ($action == "add") {
            $this->insert_table_row($table_name, $insert_values);
        }
    }

    function handle_crud_action($table_name)
    {
        /**
         * Method used to handle crud operation.
         * 
         * @param string $table_name
         * @return void
         */
        $action_arr = $this->handl->handle_form();
        $action = $action_arr[0];
        $input_data = $action_arr[1];
        $cond_values = $action_arr[2];
        if ($action) {
            if ($this->prep->check_user_input($input_data)) {
                if ($action == "update") {
                    $condition = $this->prep->get_query_params($cond_values, "pk");
                    $update_values = $this->prep->get_query_params($input_data, "up");
                    $this->perform_action($action, $table_name, $condition, $update_values);
                } elseif ($action == "delete") {
                    $condition = $this->prep->get_query_params($input_data, "pk");
                    $this->perform_action($action, $table_name, $condition);
                } else {  // insert
                    $insert_values = $this->prep->get_query_params($input_data, "in");
                    $this->perform_action($action, $table_name, NULL, NULL, $insert_values);
                }
            } else {
                $err_mess = new Text_Field("Unallowed input.<br>Try again.", "err_mess");
                $err_mess->create();
            }
        }
    }

    function get_occupied_ids($id_name, $table_name)
    {
        /**
         * Method that return list of occupied ids in table_name.
         * 
         * @param string $id_name name of column
         * @param string $table_name table to perform search
         * @return array
         */
        $query = "SELECT {$id_name}
                  FROM $table_name;";
        return $this->run_query($query);
    }

    function get_unoccupied_id($id_name, $table_name)
    {
        /**
         * Method that returns list of unoccupied ids.
         * 
         * @param string $id_name name of column
         * @param string $table_name table to perform search
         * @return array
         */
        $occupied_ids = $this->get_occupied_ids($id_name, $table_name);
        $ids_set = [];
        for ($i=0; $i<count($occupied_ids); $i++) {
            $ids_set[$occupied_ids[$i][$id_name]] = TRUE;
        }
        $found = FALSE;
        while (!$found) { // to głupie mogłem użyć incrementu.
            $random_id = rand(1, 999999);
            if (!array_key_exists($random_id, $ids_set)) {
                $found = TRUE;
            }
        }
        return $random_id;
    }

    function register_user($register_data_array)
    {
        /**
         * Method that run query that registers user.
         * 
         * @param array $register_data_array data with all required data to register
         * 
         * @return bool
         */
        $unoccupied_id = $this->get_unoccupied_id("user_id", "users");
        array_unshift($register_data_array, $unoccupied_id);
        $values = $this->prep->get_query_params($register_data_array, "in");
        $is_successful = $this->insert_table_row("users", $values);
        // is user got registered, creates token that will be stored in cookie to sustain login
        if ($is_successful) {
            $token = $this->prep->get_random_token(8);
            $this->insert_table_row("tokens", $this->prep->get_query_params([$unoccupied_id, $token], "in"));
        }
        return $is_successful;
    }

    function get_token_by_mail($mail)
    {
        /**
         * Method that returns data from token table via mail parameter.
         * data = [user_id, cookie_token]
         * 
         * @param string $mail
         * @return array
         */
        $query = "WITH user_ids as (
                     SELECT user_id from users where mail = '{$mail}')
                 SELECT tokens.user_id, cookie_token
                 FROM tokens, user_ids
                 where user_ids.user_id = tokens.user_id;";
        return $this->run_query($query);
    }

    private function check_login_attempt($login_data, $initial=FALSE)
    {
        /**
         * Method used to verify login attempt.
         * Works in two ways:
         * 1) login by mail and password - initial
         * 2) relogin by cookie: mail and token
         * 
         * @param array $login_data array with all required data to check login
         * @param bool $initial if TRUE it's user login else it's cookie sustaining login
         *  with token and mail after initial login
         */
        $err_mess = "Unallowed input.<br>Try again.";
        if ($this->prep->check_user_input($login_data, "/^[\w\s.@]+$/") or !$initial) {
            if ($initial) {  // login with password which will be compared with hashed password in db
                $password = $login_data["password"];
                $login_data = ["mail"=>$login_data["mail"]];
                $condition = $this->prep->get_query_params($login_data, "pk");
                $results = $this->get_table_contents("users", $condition);
                $db_password = $results[0]["password"];
                $is_correct = password_verify($password, $db_password);
                // second statment is used when password in db in not hashed(test purpose)
                if ($is_correct or $password == $db_password) {
                    return [TRUE, $results[0]["user_id"]];
                }
            } else { // login with token
                $token_data = $this->get_token_by_mail($login_data["mail"])[0];
                $good_token = $token_data["cookie_token"];
                if ($login_data["cookie_token"] == $good_token) {
                    return [TRUE, $token_data["user_id"]]; // data[0] is user_id
                }
            }
            $err_mess = "Invalid login information.<br>Try again.";
        }
        $err_mess = new Text_Field($err_mess, "err_mess");
        $err_mess->create();
        return [FALSE, NULL];
    }

    function check_admin_status($user_id)
    {
        /**
         * Method that check whether user has admin privileges.
         * 
         * @return bool
         */
        $condition = $this->prep->get_query_params($user_id, "pk");
        $results = $this->get_table_contents("staff", $condition);
        if (is_array($results)) {
            return TRUE;
        }
        return FALSE;
    }

    function check_user_login_attempt($login_data)
    {
        /**
         * Method that check whether user should be able to login
         * 
         * @param array $login_data
         * @return array
         */
        $login_data_out = $this->check_login_attempt($login_data, TRUE);
        $is_successful_login = $login_data_out[0];
        if ($is_successful_login) {
            $user_id = $login_data_out[1];
            $is_admin = $this->check_admin_status(["user_id" => $user_id]);
        }
        return [$is_successful_login, $is_admin];
    }

    function check_login_status($cookie)
    {
        /**
         * Method that check whether cookie should log user in
         * 
         * @param array $cookie
         * @return array
         */
        if (isset($cookie["cookie_token"])) {
            $login_data_out = $this->check_login_attempt($cookie);
            $is_successful_login = $login_data_out[0];
            if ($is_successful_login) {
                $user_id = $login_data_out[1];
                $is_admin = $this->check_admin_status(["user_id" => $user_id]);
            }
        }
        return [$is_successful_login, $is_admin];
    }

    private function get_table_contents_with($selection, $table_name, $condition, $with_selection, $with_condition, $page_num, $records_per_page)
    {
        $query = "WITH prod_ids as (
            SELECT {$with_selection} FROM {$table_name} WHERE {$with_condition}
            )
            SELECT {$selection}
            FROM product_groups, products, prod_ids
            WHERE {$condition}";
        if ($records_per_page) {
            $offset = 0 + ($page_num) * $records_per_page;
            $query .= " LIMIT {$records_per_page} OFFSET {$offset}";
        }
        $query .= ";";
        return $this->run_query($query);
    }

    function get_cart_contents($user_id, $table_name, $count=FALSE, $page_num=NULL, $records_per_page=NULL)
    {
        if ($table_name == "cart") {
            $with_sel = "product_id";
            $selection = "products.product_id, product_name, price, category_name, description";
        } elseif ($table_name == "orders") {
            $with_sel = "product_id, order_placed_date";
            $selection = "products.product_id, product_name, category_name, price, order_placed_date";
        } if ($count) {
            $selection = "COUNT(*)";
        }
        $query = "WITH prod_ids as (
            SELECT {$with_sel} FROM {$table_name} WHERE user_id = {$user_id}
            )
            SELECT {$selection}
            FROM product_groups, products, prod_ids
            WHERE prod_ids.product_id = products.product_id
             and products.group_id = product_groups.group_id";
        if ($records_per_page) {
            $offset = 0 + ($page_num) * $records_per_page;
            $query .= " LIMIT {$records_per_page} OFFSET {$offset}";
        }
        $query .= ";";
        return $this->run_query($query);
    }

    function test_conn()
    {
        // Method that prints out information about connection status.
        $conn = $this->get_pdo_obj();
        if ($conn) {
            echo "Connected";
        } else {
            echo "Connection failed";
        }
        $conn = NULL;
    }
}

abstract class Psql_Query
/**
 * Abstract class for psql_query has some universal methods.
 */
{
    protected $table_names;
    protected $condition;

    function set_table_names_arr($table_names)
    {
        /**
         * Method that sets array of table names with which statement will
         * be performed.
         * 
         * @param array $table_names
         */
        $this->table_names = $table_names;
    }

    function set_condition_arr($condition)
    {
        /**
         * Method that sets key(col_name)=>value(col_value) array
         */
        $this->condition = $condition;
    }

    function set_preparer($preparer)
    {
        /**
         * Dependency injection.
         * Method used to inject preparer class.
         */
        $this->prep = $preparer;
    }

    protected function get_condition_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        return $this->prep->get_query_params($this->condition, "pk");
    }

    protected function get_table_names_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        return $this->prep->get_query_params($this->table_names, "n");
    }

    abstract protected function get_query_contents();

    function get_query($is_final=TRUE)
    {
        /**
         * Method that returns prepared query.
         * 
         * @return string
         */
        $query = $this->get_query_contents();
        if ($is_final) {
            $query .= ";";
        }
        return $query;
    }
}

class Psql_Query_Select extends Psql_Query
/**
 * Class for creating select psql queries
 */
{
    protected $statement_values;
    protected $page_num;
    protected $records_per_page;

    function set_statement_values_arr($statement_values)
    {
        /**
         * Method that sets array of col_names to select.
         */
        $this->statement_values = $statement_values;
    }

    function set_limit_arr($limit) {
        /**
         * Method that sets page number and records per page.
         * This is useful when selecting records for table visualisation.
         */
        $this->page_num = $limit[0];
        $this->records_per_page = $limit[1];
    }

    protected function get_statement_values_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        return $this->prep->get_query_params($this->statement_values, "n");
    }

    protected function get_limit_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        $offset = 0 + ($this->page_num) * $this->records_per_page;
        return "LIMIT {$this->records_per_page} OFFSET {$offset}";
    }

    protected function get_query_contents()
    {
        /**
         * Method that returns ready to be used selection query.
         * 
         * @return string
         */
        $query = "SELECT {$this->get_statement_values_str()}
                  FROM {$this->get_table_names_str()}";
        if (isset($this->condition)) {
            $query .= " WHERE {$this->get_condition_str()}";
        }
        if (isset($this->page_num) and isset($this->records_per_page)) {
            $query .= " {$this->get_limit_str()}";
        }
        return $query;
    }
}

class Psql_Query_Insert extends Psql_Query
/**
 * Class for creating insert psql queries
 */
{
    protected $insert_values;

    function set_insert_values_arr($insert_values)
    {
        /**
         * Method that sets array with column name values to insert.
         */
        $this->insert_values = $insert_values;
    }

    function get_insert_values_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        return $this->prep->get_query_params($this->insert_values, "in");
    }

    protected function get_query_contents()
    {
        /**
         * Method that returns ready to be used insert query.
         * 
         * @return string
         */
        $query = "INSERT INTO {$this->get_table_names_str()}
                  VALUES {$this->get_insert_values_str()}";
        return $query;
    }
}

class Psql_Query_Update extends Psql_Query
/**
 * Class for creating update psql queries
 */
{
    protected $update_values;

    function set_update_values_arr($update_values)
    {
        /**
         * Method that sets key(col_name)=>value(col_value) array
         * data in this array will be used to change old values to new ones
         * in selected columns(col_name).
         */
        $this->update_values = $update_values;
    }

    function get_update_values_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        return $this->prep->get_query_params($this->update_values, "up");
    }

    protected function get_query_contents()
    {
        /**
         * Method that returns ready to be used update query.
         * 
         * @return string
         */
        $query = "UPDATE {$this->get_table_names_str()}
                  SET {$this->get_update_values_str()}
                  WHERE {$this->get_condition_str()}";
        return $query;
    }
}

class Psql_Query_Delete extends Psql_Query
/**
 * Class for creating delete psql queries
 */
{
    protected function get_query_contents()
    {
        /**
         * Method that returns ready to be used delete query.
         * 
         * @return string
         */
        $query = "DELETE FROM {$this->get_table_names_str()}
                  WHERE {$this->get_condition_str()}";
        return $query;
    }
}

class Psql_Query_Select_With extends Psql_Query_Select
{
    protected $w_statement_values;
    protected $w_table_names;
    protected $w_condition;
    protected $sub_query_name;

    function __construct()
    {
        $this->sub_query_name = "sq_name;";
    }

    function set_wstatement_values_arr($w_statement_values)
    {
        /**
         * Method that sets array of col_names to select.
         */
        $this->w_statement_values = $w_statement_values;
    }

    function set_wtable_names_arr($w_table_names)
    {
        /**
         * Method that sets array of table names with which statement will
         * be performed.
         * 
         * @param array $w_table_names
         */
        $this->w_table_names = $w_table_names;
    }

    function set_wcondition_arr($w_condition)
    {
        /**
         * Method that sets key(col_name)=>value(col_value) array
         */
        $this->w_condition = $w_condition;
    }

    protected function get_wstatement_values_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        return $this->prep->get_query_params($this->w_statement_values, "n");
    }

    protected function get_wtable_names_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        return $this->prep->get_query_params($this->w_table_names, "n");
    }

    protected function get_wcondition_str()
    {
        /**
         * Method that transforms array to psql compatible statement fragment
         * 
         * @return string
         */
        return $this->prep->get_query_params($this->w_condition, "pk");
    }

    protected function get_query_contents()
    {
        /**
         * Method that returns ready to be used selection query with single 'with' statement.
         * 
         * @return string
         */
        $w_query = new Psql_Query_Select();  // subquery
        $w_query->set_preparer($this->prep);
        $w_query->set_statement_values_arr($this->w_statement_values);
        $w_query->set_table_names_arr($this->w_table_names);
        $w_query->set_condition_arr($this->w_condition);

        $query = new Psql_Query_Select();  // query
        $query->set_preparer($this->prep);
        $query->set_statement_values_arr($this->statement_values);
        $query->set_table_names_arr($this->table_names);
        $query->set_condition_arr($this->condition);

        $final_query = "WITH {$this->sub_query_name} as 
         ({$w_query->get_query(FALSE)})
          {$query->get_query(FALSE)}";
        return $final_query;
    }
}

class Data_Handler
/**
 * Class used to fetch and process post data.
 */
{
    private $post_data;
    private $predef_par_amount;
    private $id;

    function __construct($post_data, $predef_par_amount=2)
    {
        $this->post_data = $post_data;
        $this->predef_par_amount = $predef_par_amount;
        $this->id = $this->get_identifier();
    }

    function get_post_arg($arg_name)
    {
        /**
         * Method that returns value from post data by specific key.
         * 
         * @param mixed $arg_name
         * @return mixed
         */
        if (isset($this->post_data[$arg_name])) {
            return $this->post_data[$arg_name];
        }
        return FALSE;
    }

    private function get_identified_data_amount()
    {
        /**
         * Method connected with get_identified_data() from preperer class.
         * Based on $id returns amount of data with that $id.
         */
        return $this->post_data["{$this->id}-amount"];
    }

    private function get_identifier()
    {
        /**
         * Method that returns id string.
         * 
         */
        if (isset($this->post_data["id"])) {
            // adds 2 because of key-value pair with id string and identified data amount
            $this->predef_par_amount += 2;
            return $this->post_data["id"];
        }
        return NULL;
    }

    private function get_colective_data_end_index()
    {
        /**
         * Method that returns index of the last key-value pair
         * which is not identified data nor predef.
         * 
         * @return integer
         */
        $iden_amount = $this->get_identified_data_amount();
        return count($this->post_data) - 1 - $iden_amount - $this->predef_par_amount;
    }

    function get_colective_data()
    {
        /**
         * Method that returns key-value array made purely of data which is not 
         * identidied data nor predef
         * 
         * @return array
         */
        $cd_end_index = -$this->predef_par_amount -1;
        if ($this->id) {
            $cd_end_index = $this->get_colective_data_end_index($this->predef_par_amount, $this->id);
        }
        return array_slice($this->post_data, 0, $cd_end_index);
    }

    private function get_identified_data()
    {
        /**
         * Method that return key-value array made of purely identified data from post.
         * 
         * @return array
         */
        $identified_data_start = - $this->predef_par_amount - $this->get_identified_data_amount() - 1;
        $identified_data = array_slice($this->post_data, $identified_data_start, -$this->predef_par_amount-1);
        $unidentified_data = [];
        foreach ($identified_data as $k=>$v) {
            $unidentified_data[explode("-", $k)[1]] = $v;
        }
        return $unidentified_data;
    }

    function handle_form()
    {
        /**
         * Method that is used to perfom crud opertaion.
         * Based on type of the button presed, determines action type.
         * Also fetches colective and identified data from post.
         * 
         * @return array
         */
        if (isset($this->post_data["f_u_btn_submit"])) {
            $action = "update";
        } elseif (isset($this->post_data["f_d_btn_submit"])) {
            $action = "delete";
        } elseif (isset($this->post_data["f_a_btn_submit"])) {
            $action = "add";
        }
        return [$action, $this->get_colective_data(), $this->get_identified_data()];
    }
}

class Data_Preparer
/**
 * Class used primary to transform data in array to psql compatible strings.
 */
{
    function tag_data($tag, $data)
    {
        return [$tag => $data];
    }

    function get_identified_data($data, $id)
    {
        /**
         * Method that transforms key into keys with identifier, for post method.
         * This is made for easier distinction between keys in post.
         * Array will also contain additional key-value pair with information
         * about the amount of identified data.
         * 
         * @param array $data
         * @param string $id
         * @return array
         */
        $new_data = [];
        foreach ($data as $k=>$v) {
            $new_data["{$id}-{$k}"] = $v;
        }
        $new_data["id"] = $id;
        $new_data["{$id}-amount"] = count($data);
        return $new_data;
    }

    function get_query_params($val_arr, $mode = "pk")
    {
        /**
         * Method that transform key=>value array 
         * pk - prepers condition
         * up - prepers for update
         * in - prepers for insert
         * 
         * @param array $val_arr 
         * @param string $mode
         * @return string
         */
        $cond = "";
        $i = 0;
        foreach($val_arr as $key => $value) {
            if ($i == 0) {
                if ($mode == "pk") {
                    $cond .= "$key = '$value' ";
                } elseif ($mode == "up") {
                    $cond .= "$key = '$value'";
                } elseif ($mode == "in") {
                    $cond .= "('$value'";
                } elseif ($mode == "n") {
                    $cond .= "{$value}";
                }
            } else {
                if ($mode == "pk") {
                    $cond .= " and $key = '$value'";
                } elseif ($mode == "up") {
                    $cond .= ", $key = '$value'";
                } elseif ($mode == "in") {
                    $cond .= ", '$value'";
                } elseif ($mode == "n") {
                    $cond .= ", {$value}";
                }
            }
            $i++;
        }
        if ($mode == "in") {
            $cond .= ")";
        }
        return $cond;
    }

    function get_query_output_col_to_list($query_out, $row_name)
    {
        /**
         * Takes single row from query and transforms it to a list
         * 
         * @param array $query_out query data array
         * @param string $col_name
         * @return array
         */
        $list = [];
        foreach ($query_out as $row) {
            array_push($list, $row[$row_name]);
        }
        return $list;
    }

    private function is_valid($user_input, $pattern)
    {
        /**
         * Method that checks user input whether it is safe for database.
         * 
         * @param string $user_input
         * @param string $pattern regex pattern
         * @return bool
         */
        return preg_match($pattern, $user_input);
    }

    function check_user_input($user_input_arr, $pattern="/^[\w\s]+$/")
    {
        /**
         * Method that checks user input whether it is safe for database.
         * 
         * @param array $user_input_arr
         * @param string $pattern regex pattern
         * @return bool
         */
        $is_valid = TRUE;
        foreach (array_values($user_input_arr) as $inpt) {
            if (!$this->is_valid($inpt, $pattern)) {
                $is_valid = FALSE;
                break;
            }
        }
        return $is_valid;
    }

    function get_random_token($length)
    {
        /**
         * Method that generate random token by first getting radnom bytes
         * and transforming them to hex.
         * 
         * @return string
         */
        return bin2hex(openssl_random_pseudo_bytes($length));
    }
}

abstract class Html_Object
{
    protected $class_name;
    protected $id_name;

    abstract protected function get_contents();

    protected function submerge_in_div($contents)
    {
        /**
         * Method that submerges contents in html div with given class name 
         * and id.
         * 
         * @return string
         */
        $html_code = "<div class=\"{$this->class_name}\"";
        if ($this->id_name) {
            $html_code .= " id=\"{$this->id_name}\">";
        } else {
            $html_code .= ">";
        }
        $html_code .= "{$contents}</div>";
        return $html_code;
    }

    protected function get_html()
    {
        /**
         * Universal method for returning html code for creating html objects.
         * Decides based on whether class_name|id_name is provided if contents
         * should be submerged in div or not.
         * 
         * @return string
         */
        $html_code = $this->get_contents();
        if ($this->class_name or $this->id_name) {
            $html_code = $this->submerge_in_div($html_code);
        }
        return $html_code;
    }

    function create()
    {
        /**
         * Method that create html_object.
         * 
         * @return void
         */
        print($this->get_html());
    }
}

abstract class Form extends Html_Object
/**
 * Abstract class with universal methods and parameters.
 * inherited from with later classes.
 */
{
    protected $link;
    protected $data;
    protected $class_name;
    protected $id_name;

    function __construct($class_name = Null, $id_name = NULL)
    {
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    function set_data($data) {
        $this->data = $data;
    }

    function set_link($link) {
        $this->link = $link;
    }

    protected function submerge_in_form($contents, $link, $method = "post")
    {
        /**
         * Method that submerges contents in form code block. 
         * Defines where form will be send and what method will be used.
         */
        return "<form action=\"{$link}\" method=\"{$method}\">{$contents}</form>";
    }

    protected function submerge_logic($contents)
    {
        /**
         * Method that based on whether link or class_name|id_name is set
         * decides how to submerge contents.
         * 
         * @return string
         */
        $html_code = $contents;
        if ($this->link) {
            $html_code = $this->submerge_in_form($html_code, $this->link);
        }
        if ($this->class_name or $this->id_name) {
            $html_code = $this->submerge_in_div($html_code, $this->class_name, $this->id_name);
        }
        return $html_code;
    }

    protected function get_input_row($type, $name, $value = NULL, $id = NULL, $req = FALSE)
    {
        /** Method that returns html code for creating input row, with
         *  desired options
         * 
         * @param string $type desired type of input form
         * @param string $name name of the input form
         * @param mixed $value value of the input can be string, int, bool, etc.
         * @param string $id id of the input form
         * @param bool $req whether this field is required to submit
         * @return string $input html code for input row 
         */
        $input = "<input type=\"{$type}\"";
        if ($id) {
            $input .= " id=\"{$id}\"";
        }
        $input .= " name=\"{$name}\"";
        if (!is_null($value)) {
            if ($value == FALSE) {
                $value = "0";
            }
            $input .= " value=\"{$value}\"";
        }
        if ($req) {
            $input .= " required";
        }
        $input .= ">";
        return $input;
    }

    protected function add_hidden_data()
    {
        /**
         * Method used to add additional data to sent via post.
         * Usually used in buttons.
         * 
         * @return string
         */
        $inputs = "";
        foreach ($this->data as $name=>$value) {
            $inputs .= $this->get_input_row("hidden", $name, $value);
        }
        return $inputs;
    }

    function get_html() {
        /**
         * Method that returns html code for later processing.
         * 
         * @return string
         */
        $contents = $this->get_contents();
        $html_code = $this->submerge_logic($contents);
        return $html_code;
    }
}

class Btn_Form extends Form
/**
 * Class that creates buttons embeded in form.
 * Can act as link with additional benefit which is moving data with post.
 * This class is often used in other html_object classes.
 */
{
    private $btn_text;
    private $btn_name;

    function __construct($class_name = NULL, $id_name = NULL)
    {
        $this->btn_text = "";
        $this->btn_name = "f_btn_submit";
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    function set_text($btn_text) {
        $this->btn_text = $btn_text;
    }

    function set_name($btn_name) {
        $this->btn_name = $btn_name;
    }

    protected function get_contents()
    {
        /**
         * Method that create form button
         * 
         * @return string
         */
        $contents = $this->add_hidden_data();
        $contents .= $this->get_input_row("submit", $this->btn_name, $this->btn_text);
        return $contents;
    }
}

abstract class Multichoice_Form extends Form
/**
 * Abstract class with universal methods used in later classes
 * that use multichoice concept.
 */
{
    protected function get_label_row($id, $label_text)
    /**
     * Method that returns html label row
     * 
     * @return string
     */
    {
        return "<label for=\"{$id}\">{$label_text}</label>";
    }
}

class Text_Form extends Multichoice_Form
/**
 * Class used to create text form.
 * Usually used to create new records.
 * Has submit button used to move data via post.
 */
{
    private $preset_data;
    private $btn_name;

    function __construct($preset_data=TRUE, $class_name = NULL, $id_name = NULL)
    /**
     * @param bool $preset_data defines whether text fields will be filled
     */
    {
        $this->preset_data = $preset_data;
        $this->btn_text = "Submit";
        $this->btn_name = "f_t_btn_submit";
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    function set_btn_text($btn_text)
    {
        /**
         * Sets text displayed in submit btn.
         */
        $this->btn_text = $btn_text;
    }

    function set_btn_name($btn_name)
    {
        /**
         * Sets name of the submit btn.
         */
        $this->btn_name = $btn_name;
    }

    protected function get_contents()
    {
        /**
         * Method that return html code that creates text form.
         * Has preset option which fills text fields with values
         * from provided data.
         * @return string
         */
        $contents = "";
        foreach ($this->data as $k => $v) {
            $table_name = $k;
            $value = $v;
            if (!$this->preset_data) {
                $table_name = $v;
                $value = NULL;
            }
            $contents .= $this->get_label_row($table_name, $table_name);
            $contents .= "<br>";
            $contents .= $this->get_input_row("text", $table_name, $value, $table_name, TRUE);
            $contents .= "<br>";
        }
        $btn = new Btn_Form($this->btn_name);
        $btn->set_text($this->btn_text);
        $btn->set_name($this->btn_name);
        $btn->set_data($this->data[1]);

        $contents .= $btn->get_html();
        return $contents;
    }
}

class Radio_Form extends Multichoice_Form
{
    protected function get_contents()
    {
        $contents = "";
        foreach ($this->data[0] as $k => $v) {
            $contents .= $this->get_input_row("text", $k, $v, $k, TRUE);
            $contents .= $this->get_label_row($k, $k);
            $contents .= "<br>";
        }
        $btn = new Btn_Form("f_r_btn_submit");
        $btn->set_text("Submit");
        $btn->set_name("f_r_btn_submit");
        $btn->set_data($this->data[1]);

        $contents .= $btn->get_html();
        return $contents;
    }
}

class Multichoice_Btn_Form extends Form
/**
 * Class used to create column of buttons 
 * that work same as radio form but without submit button.
 * Each btn can move data via post.
 */
{
    protected function get_contents()
    {
        /**
         * Creates string with html code that creates column of btns
         * 
         * @return string
         */
        $contents = "";
        foreach ($this->data as $key => $value) {
            $btn = new Btn_Form();
            $btn->set_text($key);
            $btn->set_name("f_m_btn_submit");
            $btn->set_data([$key=>$value]);  //
            $btn->set_link($this->link);

            $contents .= $btn->get_html();
        }
        return $contents;
    }
}

class Table extends Html_Object
/**
 * Class used to display 2d array conents to user.
 * In this project mainly used to display database table contents.
 * Can store row of btns which move esential data via post.
 */
{
    private $table_data;
    private $col_names;
    private $primary_keys;
    private $btn_link;
    private $btn_data;

    function set_table_data($table_data)
    {
        /**
         * Sets 2d array as data for table.
         * Each row contains key-value pair.
         * 
         * @param array $table_data
         */
        $this->table_data = $table_data;
    }

    function set_primary_keys($primary_keys)
    {
        /**
         * Primary keys in current table.
         * 
         * @param array $primary_keys
         */
        $this->primary_keys = $primary_keys;
    }

    function set_col_names($col_names)
    {
        /**
         * Sets col_names, transforms list of column names to set.
         * Set is used to verify which columns to display in O(1) time.
         * 
         * @param array $col_names list of colnames
         * @returns void
         */
        $this->col_names = array_flip($col_names);
    }

    function set_btn_link($link)
    {
        /**
         * Sets link for btn to move somewhere data via post.
         * 
         * @param string $link
         */
        $this->btn_link = $link;
    }
    
    function set_btn_data($data)
    {
        /**
         * Sets data for btn to move via post.
         * Usualy contains page number and table name
         * for user to be able to comeback to desired part
         * of the table.
         * 
         * @param array $data
         */
        $this->btn_data = $data;
    }
    
    function get_col_names($table_data)
    {
        /**
         * Used when col_names is not provided.
         * Takes keys from table data as column names.
         * 
         * @param array $table_data
         */
        return array_flip(array_keys($table_data[0]));
    }

    private function get_table_row($data_row, $type = "td")
    {
        /**
         * Method that returns html code that creates table row.
         * Works in two modes, one that creates row with data and one that
         * creates heading.
         * Returns string containing html code that creates row
         * 
         * @param array $data_row
         * @param string $type
         * @return string
         */
        if ($type == "td") { // data cell mode
            $inpt_class_name = "data_cell";
            $post_data = [];
        } else {
            $type = "th";  // heading mode
            $inpt_class_name = "col_name";
        }
        $cells = "";
        foreach ($data_row as $key => $value) {  // column row => value
            // if current column is meant to be displayed or in this instance method returns heading
            if (array_key_exists($key, $this->col_names) or $type = "th") {
                if ($value == FALSE) {  // jakaś magia phpa, true to 1 ale false to nic
                    $value = "0";
                }
                $cells .= "<{$type} class=\"{$inpt_class_name}\">{$value}</{$type}>";
            }
            // if method returns data cell and there should be btns
            if ($type == "td" and !is_null($this->btn_link)) {
                // collect data for btn to move via post
                // adds element if column name is in primary key set
                if (array_key_exists($key, $this->primary_keys)) {  // checks in O(1) time
                    $post_data[$key] = $value;
                }
            }
        }
        // if data cell mode and btn is meant to be displayed
        if ($type == "td" and !is_null($this->btn_link)) {
                foreach($this->btn_data as $k=>$v) {  // this should contain table_name and page_num
                    $post_data[$k] = $v;
                }
                $btn = new Btn_Form();
                $btn->set_text("X");
                $btn->set_name("f_btn_action");
                $btn->set_data($post_data);
                $btn->set_link($this->btn_link);

                $cells .= "<{$type} class=\"actions\">{$btn->get_html()}</{$type}>";
        }
        return $cells;
    }

    private function get_col_names_row()
    /**
     * Returns heading row.
     * 
     * @return string
     */
    {
        $cells = $this->get_table_row(array_keys($this->col_names), "th");
        return "<tr>{$cells}</tr>";
    }

    private function get_table_contents()
    /**
     * Returns html row of data.
     * 
     * @return string
     */
    {
        $contents = "";

        foreach ($this->table_data as $data_row) {
            $cells = $this->get_table_row($data_row);
            $contents .= "<tr>{$cells}</tr>";
        }
        return $contents;
    }

    protected function get_contents()
    /**
     * Returns html code for creating table.
     * 
     * @return string
     */
    {
        $table = $this->get_col_names_row();
        $table .= $this->get_table_contents();
        return "<table>{$table}</table>";
    }
}

class Pagination extends Html_Object
/**
 * Class used to create pagination, often used with Table class.
 */
{
    private $table_name;
    private $page_num;
    private $records_per_page;
    private $total_row_count;
    private $link;

    function __construct($class_name = "comb_pagination", $id_name = NULL)
    {
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    function set_table_name($table_name) {
        $this->table_name = $table_name;
    }

    function set_page_num($page_num) {
        $this->page_num = $page_num;
    }

    function set_records_per_page($records_per_page) {
        $this->records_per_page = $records_per_page;
    }

    function set_total_row_count($total_row_count) {
        $this->total_row_count = $total_row_count;
    }

    function set_link($link) {
        $this->link = $link;
    }

    protected function get_contents()
    {
        $post_data = [];
        if ($this->table_name) {
            $post_data["table_name"] = $this->table_name;
        }
        $pagination = "";
        if ($this->page_num > 0) {
            // Creates pagination that allows user to go left.
            $new_page_num = $this->page_num - 1;
            $post_data["page_num"] = $new_page_num;
            
            $pagi_btn = new Btn_Form();
            $pagi_btn->set_text("left");
            $pagi_btn->set_name("form_pagi_left_btn");
            $pagi_btn->set_data($post_data);
            $pagi_btn->set_link($this->link);

            $pagination .= $pagi_btn->get_html();
            // if there should exists right button.
          } if (($this->page_num+1)*$this->records_per_page<$this->total_row_count) {
            // Creates pagination that allows user to go right.
            $new_page_num = $this->page_num + 1;
            $post_data["page_num"] = $new_page_num;
            
            $pagi_btn = new Btn_Form();
            $pagi_btn->set_text("right");
            $pagi_btn->set_name("form_pagi_right_btn");
            $pagi_btn->set_data($post_data);
            $pagi_btn->set_link($this->link);

            $pagination .= $pagi_btn->get_html();
          }
        return $pagination;
    }
}

class Text_Field extends Html_Object
/**
 * Class that creates text field.
 */
{
    private $text;

    function __construct($text, $class_name = NULL, $id_name = NULL)
    {
        $this->text = $text;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    function get_contents()
    {
        return "<p>{$this->text}</p>";
    }
}

?>