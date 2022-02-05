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

    function get_schema_name()
    {
        /**
         * Method that returns schema_name of the database.
         * 
         * @return string
         */
        return $this->schema_name;
    }

    function run_query($query)
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

    function set_from_statement($table_names)
    {
        /**
         * Method that sets array of table names with which statement will
         * be performed.
         * 
         * @param array $table_names
         */
        $this->table_names = $table_names;
    }

    function set_where_statement($condition)
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
    protected $is_distinct;
    protected $page_num;
    protected $records_per_page;
    protected $statement_values;

    function __construct($is_distinct=FALSE, $page_num=NULL, $records_per_page=NULL)
    {
        $this->is_distinct = $is_distinct;
        $this->page_num = $page_num;
        $this->records_per_page = $records_per_page;
    }

    function set_select_statement($statement_values)
    {
        /**
         * Method that sets array of col_names to select.
         */
        $this->statement_values = $statement_values;
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
        if ($this->is_distinct) {
            $query = "SELECT DISTINCT {$this->get_statement_values_str()}
                  FROM {$this->get_table_names_str()}";
        } else {
            $query = "SELECT {$this->get_statement_values_str()}
                    FROM {$this->get_table_names_str()}";
        }

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
    protected $return_id;
    protected $insert_values;
    protected $statement_values;

    function __construct($return_id=FALSE)
    {
        $this->return_id = $return_id;   
    }

    function set_select_statement($statement_values)
    {
        /**
         * Method that sets array of col_names to insert to.
         */
        $this->statement_values = $statement_values;
    }

    function set_insert_statement($insert_values)
    {
        /**
         * Method that sets array with column name values to insert.
         */
        $this->insert_values = $insert_values;
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
        $query = "INSERT INTO {$this->get_table_names_str()}";
        if (isset($this->statement_values)) {
            $query .= " ({$this->get_statement_values_str()})";
        }
        $query .= " VALUES {$this->get_insert_values_str()}";
        // This kinda stucks, because i had to change all names of id columns
        // To just id for exampel user_id to id or product_id to id and so on.
        if ($this->return_id) {
            $query .= " RETURNING id";
        }
        return $query;
    }
}

class Psql_Query_Update extends Psql_Query
/**
 * Class for creating update psql queries
 */
{
    protected $update_values;

    function set_update_statement($update_values)
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

class Query_Runner
/**
 * Class used to assemble and run queries on database.
 */
{
    private $loader;
    private $prep;

    function __construct($loader, $preparer)
    {
        $this->loader = $loader;
        $this->prep = $preparer;
    }

    function give_preparer()
    {
        /**
         * Method used to pass dependency.
         */
        return $this->prep;
    }

    function run_query($query_str)
    {
        /**
         * Method used to run custom queries.
         */
        return $this->loader->run_query($query_str);   
    }

    function get_table_names()
    {
        /**
         * Method used to return all table names in database.
         * 
         */
        $query = new Psql_Query_Select();
        $query->set_preparer($this->prep);
        
        $query->set_select_statement(["table_name"]);
        $query->set_from_statement(["information_schema.tables"]);
        $query->set_where_statement([
            "table_schema"=>$this->loader->get_schema_name(),
            "table_type"=>"BASE TABLE"]);
        
        $data = $this->loader->run_query($query->get_query());

        $table_names = [];
        for ($i=0; $i<count($data); $i++) {
            array_push($table_names, $data[$i]["table_name"]);
        }
        return $table_names;
    }

    function get_table_row_amount($table_name)
    {
        /**
         * Method used to return amount of rows in a given table
         * 
         * @return integer|bool
         */
        $query = new Psql_Query_Select();
        $query->set_preparer($this->prep);

        $query->set_select_statement(["count(*)"]);
        $query->set_from_statement([$table_name]);

        $data = $this->loader->run_query($query->get_query());
        return $data[0]["count"];
    }

    function get_column_names($table_name)
    {
        /**
         * Method that returns column names in a given table.
         * 
         * @return array|bool
         */
        $query = new Psql_Query_Select();
        $query->set_preparer($this->prep);

        $query->set_select_statement(["column_name"]);
        $query->set_from_statement(["INFORMATION_SCHEMA.COLUMNS"]);
        $query->set_where_statement([
            "table_name"=>$table_name,
            "table_schema"=>$this->loader->get_schema_name()]);

        $data = $this->loader->run_query($query->get_query());

        $column_names = [];
        for ($i=0;$i<count($data);$i++) {
            array_push($column_names, $data[$i]["column_name"]);
        }
        return $column_names;
    }

    function get_primary_key_names()
    {
        /**
         * Method that returns key(table_name)=>value(list of primary key names)
         * 
         * @return array|bool
         */
        $query = new Psql_Query_Select();
        $query->set_preparer($this->prep);

        $query->set_select_statement([
            "conrelid::regclass AS table_name",
            "conname AS primary_key", 
            "pg_get_constraintdef(oid)"]);
        $query->set_from_statement(["pg_constraint"]);
        $query->set_where_statement([
            "contype"=>"p",
            "connamespace"=>[$this->loader->get_schema_name(), "cast", "::regnamespace"]]);

        $data = $this->loader->run_query($query->get_query());
        $out = [];
        // output is a bit complicated, that's why regex is used.
        foreach ($data as $row) {
            $val = $row["pg_get_constraintdef"];
            preg_match("/\((.*)\)/", $val, $matches);
            $pk_list = explode(", ", $matches[1]);

            // if return set instead of list
            // $pk_set = [];
            // foreach ($pk_list as $el) {
            //     $pk_set[$el] = TRUE;
            // }
            $out[$row["table_name"]] = $pk_list;
        }
        return $out;
    }

    function get_table_contents($column_names, $table_names, $condition_arr=NULL,
                                $is_distinct=FALSE, $page_num=NULL, $records_per_page=NULL)
    {
        /**
         * Method used to run select query.
         * 
         * @param array $column_names list of columns which contents will be returned
         * @param array $table_names list of tables from which rows will be selected
         * @param array $condition_arr key(column_name)=>value(column_value) array
         * @param bool $is_distinct determines whether distnict values will be returned
         * @param integer $page_num data for pagination
         * @param integer $records_per_page data for pagination
         * @return array|bool
         */
        $query = new Psql_Query_Select($is_distinct, $page_num, $records_per_page);
        $query->set_preparer($this->prep);

        $query->set_select_statement($column_names);
        $query->set_from_statement($table_names);
        if (!is_null($condition_arr)) {
            $query->set_where_statement($condition_arr);
        }
        $data = $this->loader->run_query($query->get_query());
        return $data;
    }

    function insert_table_row($table_names, $values, $column_names=NULL, $return_id=FALSE)
    {
        /**
         * Method used to run psql insert query.
         * 
         * @param array $table_names
         * @param array $values
         * @param array $column_names
         * @return bool|integer
         */
        $query = new Psql_Query_Insert($return_id);
        $query->set_preparer($this->prep);

        $query->set_from_statement($table_names);
        $query->set_insert_statement($values);
        if (!is_null($column_names)) {
            $query->set_select_statement($column_names);
        }
        $data = $this->loader->run_query($query->get_query());
        if (is_array($data)) { // Only when query has returning clause
            if (array_key_exists("id", $data[0])) {
                return $data[0]["id"];
            }
            return TRUE;
        }
        return $data; // FALSE from run_query
    }

    function update_table_row($update_values, $table_names, $condition_arr)
    {
        /**
         * Method used to run psql update query.
         * 
         * @param array $update_values
         * @param array $table_names
         * @param array $condition_arr
         * @return bool
         */
        $query = new Psql_Query_Update();
        $query->set_preparer($this->prep);

        $query->set_update_statement($update_values);
        $query->set_from_statement($table_names);
        $query->set_where_statement($condition_arr);
        $data = $this->loader->run_query($query->get_query());
        return $data;
    }

    function delete_table_row($table_names, $condition_arr)
    {
        /**
         * Method used to run psql delete query.
         * 
         * @param array $table_names
         * @param array $condition_arr
         * @return bool
         */
        $query = new Psql_Query_Delete();
        $query->set_preparer($this->prep);

        $query->set_from_statement($table_names);
        $query->set_where_statement($condition_arr);
        $data = $this->loader->run_query($query->get_query());
        return $data;
    }
}

class Login_handler
/**
 * Class used to handle logging in.
 * Users login via this class.
 * Their login status is sustained via this class with cookies.
 * Admin status and user_id is gathered here.
 */
{
    private $user_login_data;
    private $cookie_data;
    private $is_logged;
    private $is_admin;
    private $user_id;

    function __construct($runner)
    {
        $this->run = $runner;
        $this->prep = $runner->give_preparer();
    }

    function set_login($login_data, $is_cookie=FALSE)
    {
        /**
         * Each time we want to get information about given login data,
         * we use this method.
         */
        if ($is_cookie) {
            $this->cookie_data = $login_data;
        } else {
            $this->user_login_data = $login_data;
        }

        $login_status = $this->check_login_attempt();
        $this->is_logged = $login_status[0];
        $this->is_admin = $login_status[1];
    }

    function is_logged()
    {
        /**
         * @return bool
         */
        if (isset($this->is_logged)) {
            return $this->is_logged;
        }
        return FALSE;
    }

    function is_admin()
    {
        /**
         * @return bool
         */
        if (isset($this->is_admin)) {
            return $this->is_admin;
        }
        return FALSE;
    }

    function get_user_id()
    {
        if (isset($this->user_id)) {
            return $this->user_id;
        }
        return FALSE;
    }

    function logout()
    {
        /**
         * Method uesd to logout, deletes cookie.
         * 
         * @return void
         */
        $this->is_logged = FALSE;
        $this->is_admin = FALSE;
        $this->delete_cookie();
    }

    private function get_token_data_by_mail($mail)
    {
        /**
         * Method that returns data from token table via mail parameter.
         * data = [user_id, cookie_token]
         * 
         * @param string $mail
         * @return array
         */

        $query = new Psql_Query_Select();
        $query->set_preparer($this->prep);

        $query->set_select_statement(["tokens.id", "cookie_token"]);
        $query->set_from_statement(["tokens", "user_ids"]);
        $query->set_where_statement(["user_ids.id"=>["tokens.id", "symbolic"]]);

        $sub_query = new Psql_Query_Select();
        $sub_query->set_preparer($this->prep);

        $sub_query->set_select_statement(["id"]);
        $sub_query->set_from_statement(["users"]);
        $sub_query->set_where_statement(["mail"=>$mail]);
        
        $final_query = "WITH user_ids as ({$sub_query->get_query(FALSE)})
                  {$query->get_query()}";
        return $this->run->run_query($final_query)[0];
    }

    private function check_admin_status($user_id)
    {
        /**
         * Method that checks whether user has admin privileges.
         * 
         * @return bool
         */
        $results = $this->run->get_table_contents(["*"], ["staff"], ["id"=>$user_id]);
        if (($results)) {
            return TRUE;
        }
        return FALSE;
    }

    function create_cookie()
    {
        /**
         * Method that creates cookie for user.
         */
        $mail = $this->user_login_data["mail"];
        $token = $this->get_token_data_by_mail($mail)["cookie_token"];
        setcookie("mail", $mail, time() + 60*60); // 1h
        setcookie("cookie_token", $token, time() + 60*60); // 1h   
    }

    private function delete_cookie()
    {
        /**
         * Method that deletes user cookie.
         */
        unset($_COOKIE['mail']);
        unset($_COOKIE['cookie_token']);
        setcookie('mail', null, -1, '/');
        setcookie('cookie_token', null, -1, '/');
    }

    private function check_login_attempt()
    {
        /**
         * Method used to verify login attempt.
         * Works in two ways:
         * 1) login by mail and password - initial
         * 2) relogin by cookie: mail and token
         * 
         * @return array
         */
        $err_mess = "Invalid login information.<br>Try again.";
        // login with password which will be compared with hashed password in db
        if (isset($this->user_login_data)) {
            // check whether data is in acceptable format
            if ($this->prep->check_user_input($this->user_login_data, "/^[\w\s.@]+$/")) {
                $password = $this->user_login_data["password"];
                // get user_id and hashed password by mail
                $results = $this->run->get_table_contents(["*"], ["users"], ["mail"=>$this->user_login_data["mail"]])[0];

                $db_password = $results["password"];
                $is_correct = password_verify($password, $db_password);
                // second statment is used when password in db in not hashed(test purpose)
                if ($is_correct or $password == $db_password) {
                    $this->user_id = $results["id"];
                    return [TRUE, $this->check_admin_status($this->user_id)];
                }
            } else {
                $err_mess = "Unallowed input.<br>Try again.";
            }
        } elseif (isset($this->cookie_data)) { // login with token
            $token_data = $this->get_token_data_by_mail($this->cookie_data["mail"]);
            if ($this->cookie_data["cookie_token"] == $token_data["cookie_token"]) {
                $this->user_id = $token_data["id"];
                return [TRUE, $this->check_admin_status($this->user_id)]; // data[0] is user_id
            }
        }
        $err_mess = new Text_Field($err_mess, "err_mess");
        $err_mess->create();
        return [FALSE, FALSE];
    }
}

class Shop_Handler
/**
 * Class that is collection of methods useful for shop.
 */
{
    private $run;
    private $prep;
    private $user_id;

    function __construct($runner)
    {
        $this->run = $runner;
        $this->prep = $runner->give_preparer();
    }

    function set_user_id($user_id)
    {
        $this->user_id = $user_id;
    }

    function register_user($register_data_array)
    {
        /**
         * Method that run query that registers user.
         * 
         * @param array $register_data_array key(col_name)=>value data with all required data to register
         * 
         * @return bool
         */
        if ($this->prep->check_user_input($register_data_array, "/^[\w\s.@]+$/")) {
            $register_data_array["password"] = password_hash($register_data_array["password"], PASSWORD_DEFAULT);
            $user_id = $this->run->insert_table_row(["users"], array_values($register_data_array), array_keys($register_data_array), TRUE);
            // is user got registered, creates token that will be stored in cookie to sustain login
            if (is_integer($user_id)) {
                $token = $this->prep->get_random_token(8);
                return $this->run->insert_table_row(["tokens"], [$user_id, $token]);
            }
        }
        $err_mess = new Text_Field("Unallowed input.<br>Try again.", "err_mess");
        $err_mess->create();
        return FALSE;
    }

    function get_cart_contents($count=FALSE, $page_num=NULL, $records_per_page=NULL)
    {
        /**
         * Method used to get product info for users cart.
         * 
         * @param bool $count if true amount of rows will be returned
         * @param int $page_num
         * @param int $records_per_page
         * @return int|array
         */
        $sub_query = new Psql_Query_Select();
        $sub_query->set_preparer($this->prep);

        $sub_query->set_select_statement(["id"]);
        $sub_query->set_from_statement(["cart"]);
        $sub_query->set_where_statement(["user_id"=>$this->user_id]);

        $query = new Psql_Query_Select(FALSE, $page_num, $records_per_page);
        $query->set_preparer($this->prep);
        if ($count) {
            $col_names = ["count(*)"];
        } else {
            $col_names = ["prod_ids.id", "product_name", "price", "category_name"];
        }
        $query->set_select_statement($col_names);
        $query->set_from_statement(["product_groups", "products", "prod_ids"]);
        $query->set_where_statement(
            ["prod_ids.id"=>["products.id", "symbolic"],
             "products.group_id"=>["product_groups.id", "symbolic"]]);
        $final_query = "WITH prod_ids as ({$sub_query->get_query(FALSE)})
                        {$query->get_query()}";
        if ($count) {
            return $this->run->run_query($final_query)[0]["count"];
        }
        return $this->run->run_query($final_query);
    }

    function add_to_cart($product_id)
    {
        /**
         * Method used to add product to users cart.
         * 
         * @param integer $product_id
         */

        // insert data into database.
        $this->run->insert_table_row(["cart"], [$product_id, $this->user_id]);
        // change availability status
        $this->run->update_table_row(["is_available"=>FALSE], ["products"], ["id"=>$product_id]);
    }

    function delete_from_cart($product_id)
    {
        /**
         * Method used to delete product from users cart.
         * 
         * @param integer $product_id
         */

        // delete row with product id from cart
        $this->run->delete_table_row(["cart"], ["id"=>$product_id]);
        // modify availability
        $this->run->update_table_row(["is_available"=>TRUE], ["products"], ["id"=>$product_id]);
    }

    function create_new_order()
    {
        /**
         * Method that creates new order for user.
         * Reassigns products from cart to specific order id.
         * 
         * @param int $user_id
         */

        // create new order
        $order_id = $this->run->insert_table_row(
            ["orders"], [$this->user_id, date("Y-m-d H:i:s")],
            ["user_id", "order_placed_date"], true);
        // get ids of products in users cart
        $cart_contents = $this->run->get_table_contents(
            ["id"], ["cart"], ["user_id"=>$this->user_id]);
        // assign each product in cart to order and delete it from the cart
        foreach ($cart_contents as $product_row) {
            $this->run->update_table_row(["order_id"=>$order_id], ["products"],["id"=>$product_row["id"]]);
            $this->run->delete_table_row(["cart"], ["id"=>$product_row["id"]]);
        }
    }

    function get_order_product_info($order_id, $count=FALSE, $page_num=NULL, $records_per_page=NULL)
    {
        /**
         * Method used to get info about products that are in specific order.
         */

        $sub_query = new Psql_Query_Select();
        $sub_query->set_preparer($this->prep);

        $sub_query->set_select_statement(["id", "group_id"]);
        $sub_query->set_from_statement(["products"]);
        $sub_query->set_where_statement(["order_id"=>$order_id]);

        $query = new Psql_Query_Select(FALSE, $page_num, $records_per_page);
        $query->set_preparer($this->prep);
        if ($count) {
            $col_names = ["count(*)"];
        } else {
            $col_names = ["prod_ids.id", "product_name", "category_name", "price"];
        }
        $query->set_select_statement($col_names);
        $query->set_from_statement(["product_groups", "prod_ids"]);
        $query->set_where_statement(["prod_ids.group_id"=>["product_groups.id", "symbolic"]]);

        $final_query = "WITH prod_ids as ({$sub_query->get_query(FALSE)})
                        {$query->get_query()}";
        return $this->run->run_query($final_query);
    }
}

class Crud_Handler
/**
 * Class with methods useful for crud.
 */
{   
    private $post_handl;
    private $run;
    private $prep;

    function __construct($post_handler, $runner, $preparer)
    {
        $this->post_handl = $post_handler;
        $this->run = $runner;
        $this->prep = $preparer;
    }

    function handle_crud_action()
    {
        /**
         * Method used to handle crud operation.
         * 
         * @return void
         */
        $action_arr = $this->post_handl->handle_form();
        $action = $action_arr[0];
        $user_input_arr = $action_arr[1];  // data provided by user, usually in text form.
        $hidden_data_arr = $action_arr[2];  // primary keys used in creating where statement.
        if ($this->prep->check_user_input($user_input_arr, "/^[\w\s.@]+$/")) {
            $table_name = $this->post_handl->get_post_arg("table_name");
            if ($action == "update") {
                $this->run->update_table_row($user_input_arr, [$table_name], $hidden_data_arr);
            } elseif ($action == "delete") {
                $this->run->delete_table_row([$table_name], $user_input_arr);
            } elseif ($action == "insert") {
                $this->run->insert_table_row([$table_name], $user_input_arr);
            }
        } else {
            $err_mess = new Text_Field("Unallowed input.<br>Try again.", "err_mess");
            $err_mess->create();
        }
    }
}

class Post_Data_Handler
/**
 * Class used to fetch and process post data.
 */
{
    private $post_data;
    private $predef_par_amount;
    private $id;

    function __construct($post_data)
    {
        $this->post_data = $post_data;
        $this->predef_par_amount = 1;
        $this->id = $this->get_identifier();
        if (isset($post_data["page_num"])) {
            $this->predef_par_amount += 1;
        }
        if (isset($post_data["table_name"])) {
            $this->predef_par_amount += 1;
        }
        if (isset($post_data["mode"])) {
            $this->predef_par_amount += 1;
        }
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
        if (isset($this->post_data["ident"])) {
            // adds 2 because of key-value pair with id string and identified data amount
            $this->predef_par_amount += 2;
            return $this->post_data["ident"];
        }
        return NULL;
    }

    function get_colective_data()
    {
        /**
         * Method that returns key-value array made purely of data which is not 
         * identidied data nor predef.
         * This is usually text form|multichoice btns form| radio form data.
         * 
         * @return array
         */
        $colective_data_amount = count($this->post_data) - $this->predef_par_amount;
        if (isset($this->id)) {
            $colective_data_amount -= $this->get_identified_data_amount();
        }
        return array_slice($this->post_data, 0, $colective_data_amount);
    }

    function get_identified_data()
    {
        /**
         * Method that return key-value array made of purely identified data from post.
         * This is usually hidden data.
         * 
         * @return array
         */
        if (!is_null($this->id)) {
            $colective_data_amount = count($this->post_data) - $this->predef_par_amount - $this->get_identified_data_amount();;
            $identified_data = array_slice($this->post_data, $colective_data_amount, $this->get_identified_data_amount());
            $unidentified_data = [];
            foreach ($identified_data as $k=>$v) {
                $unidentified_data[explode("-", $k)[1]] = $v;
            }
            return $unidentified_data;
        }
        return FALSE;
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
        return [
            $this->post_data["mode"], 
            $this->get_colective_data(), 
            $this->get_identified_data()];
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
        $new_data["ident"] = $id;
        $new_data["{$id}-amount"] = count($data);
        return $new_data;
    }

    private static function get_value_str($value)
    {
        if (is_string($value)) {
            return "'{$value}'";
        }
        elseif (is_integer($value)) {
            return "{$value}";
        } elseif (is_bool($value)) {
            if ($value) {
                return "TRUE";
            }
            return "FALSE";
        }
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
            if (is_array($value)) {
                if ($value[1] == "cast") {
                    $value_str = $this::get_value_str($value[0]);
                    $type_cast = $value[2];
                    $value_str .= $type_cast;
                } elseif ($value[1] == "symbolic") {
                    $value_str = $value[0];
                }
            } else {
                $value_str = $this::get_value_str($value);
            }

            if ($i == 0) {
                if ($mode == "pk") {
                    $cond .= "$key = $value_str";
                } elseif ($mode == "up") {
                    $cond .= "$key = $value_str";
                } elseif ($mode == "in") {
                    $cond .= "($value_str";
                } elseif ($mode == "n") {
                    $cond .= "{$value}";
                }
            } else {
                if ($mode == "pk") {
                    $cond .= " and $key = $value_str";
                } elseif ($mode == "up") {
                    $cond .= ", $key = $value_str";
                } elseif ($mode == "in") {
                    $cond .= ", $value_str";
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

    function get_query_output_col_to_list($query_out)
    {
        /**
         * Takes single row from query and transforms it to a list
         * 
         * @param array $query_out query data array
         * @param string $col_name
         * @return array
         */
        $row_name = array_key_first($query_out[0]); 
        $list = [];
        foreach ($query_out as $row) {
            array_push($list, $row[$row_name]);
        }
        return $list;
    }

    private static function is_valid($user_input, $pattern)
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
            if (!$this::is_valid($inpt, $pattern)) {
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

    function set_class_name($class_name)
    {
        $this->class_name = $class_name;
    }

    function set_id_name($id_name)
    {
        $this->id_name = $id_name;
    }

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
    protected $hidden_data;
    protected $link;
    protected $class_name;
    protected $id_name;

    function __construct($hidden_data = NULL, $link= NULL, $class_name = NULL, $id_name = NULL)
    {
        $this->hidden_data = $hidden_data;
        $this->link = $link;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    function set_hidden_data($hidden_data) {
        $this->hidden_data = $hidden_data;
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
        if (!is_null($this->link)) {
            $html_code = $this->submerge_in_form($html_code, $this->link);
        }
        if (!is_null($this->class_name) or !is_null($this->id_name)) {
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
        foreach ($this->hidden_data as $name=>$value) {
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
        if (!isset($this->btn_text)) {
            $this->btn_text = "";
        }
        if (!isset($this->btn_name)) {
            $this->btn_name = "f_btn_submit";
        }
        $contents = "";
        if (isset($this->hidden_data)) {
            $contents .= $this->add_hidden_data();
        }
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
    protected $btn_text;
    protected $btn_name;

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
    private $text_form_data;
    private $preset_data;

    function __construct($text_form_data, $link, $preset_data=FALSE, $class_name = NULL, $id_name = NULL)
    /**
     * @param bool $preset_data defines whether text fields will be filled
     */
    {
        $this->text_form_data = $text_form_data;
        $this->link = $link;
        $this->preset_data = $preset_data;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
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
        foreach ($this->text_form_data as $k => $v) {
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

        if (!isset($this->btn_text)) {
            $this->btn_text = "Submit";
        }
        if (!isset($this->btn_name)) {
            $this->btn_name = "f_t_btn_submit";
        }
        $btn = new Btn_Form();
        $btn->set_text($this->btn_text);
        $btn->set_name($this->btn_name);
        $btn->set_class_name($this->btn_name);
        if (isset($this->hidden_data)) {
            $btn->set_hidden_data($this->hidden_data);
        }
        $contents .= $btn->get_html();
        return $contents;
    }
}

class Radio_Form extends Multichoice_Form
{
    private $radio_form_data;
    private $context;

    function __construct($radio_form_data, $context, $link, $class_name = NULL, $id_name = NULL)
    /**
     * @param bool $preset_data defines whether text fields will be filled
     */
    {
        $this->radio_form_data = $radio_form_data;
        $this->context = $context;
        $this->link = $link;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    protected function get_contents()
    {
        $contents = "";
        foreach ($this->radio_form_data as $v) {
            $contents .= $this->get_input_row("text", $v, $this->context, $v, TRUE);
            $contents .= $this->get_label_row($v, $v);
            $contents .= "<br>";
        }
        $btn = new Btn_Form();
        $btn->set_text($this->btn_text);
        $btn->set_name($this->btn_name);
        if (isset($this->hidden_data)) {
            $btn->set_hidden_data($this->hidden_data);
        }
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
    private $btns_data;
    private $context;

    function __construct($btns_data, $context, $link, $class_name = NULL, $id_name = NULL)
    /**
     * @param bool $preset_data defines whether text fields will be filled
     */
    {
        $this->btns_data = $btns_data;
        $this->context = $context;
        $this->link = $link;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    protected function get_contents()
    {
        /**
         * Creates string with html code that creates column of btns
         * 
         * @return string
         */
        $contents = "";
        foreach ($this->btns_data as $value) {
            $btn = new Btn_Form([$this->context=>$value], $this->link);
            $btn->set_text($value);
            $btn->set_name("f_m_btn_submit");

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

    function __construct($table_data, $col_names=NULL)
    {
        $this->table_data = $table_data;
        if (!is_null($col_names)) {
            $this->col_names = array_flip($col_names);
        }
        if (!isset($this->col_names)) {
            $this->col_names = $this->get_col_names();
        }
    }

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
        $this->primary_keys = array_flip($primary_keys);
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
    
    function get_col_names()
    {
        /**
         * Used when col_names is not provided.
         * Takes keys from table data as column names.
         * 
         * @param array $table_data
         */
        return array_flip(array_keys($this->table_data[0]));
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
            if (array_key_exists($key, $this->col_names) or $type == "th") {
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
                $btn = new Btn_Form($post_data, $this->btn_link);
                $btn->set_text("X");
                $btn->set_name("f_btn_action");

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
    private $btn_data;
    private $link;

    function __construct($page_num, $records_per_page, $total_row_count, $link, $class_name = "comb_pagination", $id_name = NULL)
    {
        $this->page_num = $page_num;
        $this->records_per_page = $records_per_page;
        $this->total_row_count = $total_row_count;
        $this->link = $link;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    function set_table_name($table_name)
    {
        $this->table_name = $table_name;
    }

    function set_page_num($page_num)
    {
        $this->page_num = $page_num;
    }

    function set_records_per_page($records_per_page)
    {
        $this->records_per_page = $records_per_page;
    }

    function set_total_row_count($total_row_count)
    {
        $this->total_row_count = $total_row_count;
    }

    function set_btn_data($btn_data)
    {
        $this->btn_data = $btn_data;
    }

    function set_link($link)
    {
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
            if (isset($this->btn_data)) {
                foreach ($this->btn_data as $k=>$v) {
                    $post_data[$k] = $v;
                }
            }
            $pagi_btn = new Btn_Form($post_data, $this->link);
            $pagi_btn->set_text("left");
            $pagi_btn->set_name("form_pagi_left_btn");

            $pagination .= $pagi_btn->get_html();
            // if there should exists right button.
          } if (($this->page_num+1)*$this->records_per_page<$this->total_row_count) {
            // Creates pagination that allows user to go right.
            $new_page_num = $this->page_num + 1;
            $post_data["page_num"] = $new_page_num;
            if (isset($this->btn_data)) {
                foreach ($this->btn_data as $k=>$v) {
                    $post_data[$k] = $v;
                }
            }
            $pagi_btn = new Btn_Form($post_data, $this->link);
            $pagi_btn->set_text("right");
            $pagi_btn->set_name("form_pagi_right_btn");

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