<?php

class Db_Loader
{
    private $db_host;
    private $db_port;
    private $db_name;
    private $db_user_name;
    private $db_password;
    private $schema_name;

    function __construct()
    {
        $this->db_host = "localhost";
        $this->db_port = 5432;
        $this->db_name = "sklep_php";
        $this->db_user_name = "mateusz";
        $this->db_password = 9326;
        $this->schema_name = "public";
    }

    function set_db_login_data($db_host, $db_port, $db_name, $db_user_name, $db_password)
    {
        $this->db_host = $db_host;
        $this->db_port = $db_port;
        $this->db_name = $db_name;
        $this->db_user_name = $db_user_name;
        $this->db_password = $db_password;
    }

    function set_records_per_page($records_per_page)
    {
        // Sets how many records will be displayed per page.
        $this->records_per_page = $records_per_page;
    }

    function set_page_number($page_num)
    {
        // Sets current page.
        $this->page_num = $page_num;
    }

    private function get_db_string()
    {
        // Method that returns string required to establish connection with database.
        return "pgsql:host={$this->db_host};port={$this->db_port};dbname={$this->db_name};";
    }

    private function get_pdo_obj()
    {
        // Method that returns PDO object if connection is establishede else false.
        $conn = new PDO($this->get_db_string(), $this->db_user_name, $this->db_password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    }

    private function run_query($query)
    {
        // Method that runs query on a given table from given database.
        // Sql injection prone.
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
        if ($data) {
            return $data;
        }
        return TRUE;
    }

    function get_table_names()
    {
        // Method that returns table names input a given database.
        // Safe.
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
        // Method that returns row count input a given table.
        // Safe.
        $query = "SELECT count(*)
                  FROM {$table_name}";
        $data = $this->run_query($query);
        return array_values($data[0])[0];
    }

    function get_col_names($table_name)
    {
        // Method that returns row names input a given table.
        // Safe.
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
        // Method that returns table primary key names.
        // Safe.
        $query = "SELECT conrelid::regclass AS table_name,
                            conname AS primary_key,
                            pg_get_constraintdef(oid)
                  FROM   pg_constraint
                  WHERE  contype = 'p'
                  AND    connamespace = '{$this->schema_name}'::regnamespace
                  ORDER  BY conrelid::regclass::text, contype DESC;";
        $data = $this->run_query($query);
        $out = [];
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
        if ($condition) {
            $query .= " WHERE {$condition}";
        }
        if ($records_per_page) {
            $offset = 0 + ($page_num) * $records_per_page;
            $query .= " LIMIT {$records_per_page} OFFSET {$offset}";
        }
        $query .= ";";
        return $this->run_query($query);
    }

    function insert_table_row($table_name, $values) {
        $query = "INSERT INTO {$table_name}
                  VALUES {$values};";
        return $this->run_query($query);
    }

    function update_table_row($table_name, $condition, $values)
    {
        $query = "UPDATE {$table_name}
                  SET {$values}
                  WHERE {$condition};";
        return $this->run_query($query);
    }

    function delete_table_row($table_name, $condition)
    {
        $query = "DELETE FROM $table_name
                  WHERE {$condition};";
        return $this->run_query($query);
    }

    function perform_action($action, $table_name, $condition=NULL, 
                            $update_values=NULL, $insert_values=NULL) {
        if ($action == "update") {
            $this->update_table_row($table_name, $condition, $update_values);
        } elseif ($action == "delete") {
            $this->delete_table_row($table_name, $condition);
        } elseif ($action == "add") {
            $this->insert_table_row($table_name, $insert_values);
        }
    }

    function handle_crud_action($handler, $preparer, $table_name) {
        $action_arr = $handler->handle_form();
        $action = $action_arr[0];
        $input_data = $action_arr[1];
        $cond_values = $action_arr[2];
        if ($action) {
            if ($preparer->check_user_input($input_data)) {
                if ($action == "update") {
                    $condition = $preparer->get_query_params($cond_values, "pk");
                    $update_values = $preparer->get_query_params($input_data, "up");
                    $this->perform_action($action, $table_name, $condition, $update_values);
                } elseif ($action == "delete") {
                    $condition = $preparer->get_query_params($input_data, "pk");
                    $this->perform_action($action, $table_name, $condition);
                } else {  // insert
                    $insert_values = $preparer->get_query_params($input_data, "in");
                    $this->perform_action($action, $table_name, NULL, NULL, $insert_values);
                }
            } else {
                $err_mess = new Text_Field("Unallowed input.<br>Try again.", "err_mess");
                $err_mess->create();
            }
        }
    }

    function get_occupied_ids($id_name, $table_name) {
        $query = "SELECT {$id_name}
                  FROM $table_name;";
        return $this->run_query($query);
    }

    function get_unoccupied_id($id_name, $table_name) {
        $occupied_ids = $this->get_occupied_ids($id_name, $table_name);
        $ids_set = [];
        for ($i=0; $i<count($occupied_ids); $i++) {
            $ids_set[$occupied_ids[$i][$id_name]] = TRUE;
        }
        $found = FALSE;
        while (!$found) {
            $random_id = rand(1, 999999);
            if (!array_key_exists($random_id, $ids_set)) {
                $found = TRUE;
            }
        }
        return $random_id;
    }

    function register_user($register_data_array, $preparer) {
        if ($preparer->check_user_input($register_data_array, "/^[\w\s.@]+$/")) {
            $unoccupied_id = $this->get_unoccupied_id("user_id", "users");
            array_unshift($register_data_array, $unoccupied_id);
            $values = $preparer->get_query_params($register_data_array, "in");
            return $this->insert_table_row("users", $values);
        }
        $err_mess = new Text_Field("Unallowed input.<br>Try again.", "err_mess");
        $err_mess->create();
        return FALSE;
    }

    function check_login_attempt($login_data, $preparer) {
        $err_mess = "Unallowed input.<br>Try again.";
        if ($preparer->check_user_input($login_data, "/^[\w\s.@]+$/")) {
            $condition = $preparer->get_query_params($login_data, "pk");
            $results = $this->get_table_contents("users", $condition);
            if (is_array($results)) {
                // True if found and user id
                return [TRUE, $results[0]["user_id"]];
            }
            $err_mess = "Invalid login information.<br>Try again.";
        }
        $err_mess = new Text_Field($err_mess, "err_mess");
        $err_mess->create();
        return [FALSE, NULL];
    }

    function check_admin_status($user_id, $preparer) {
        $condition = $preparer->get_query_params($user_id, "pk");
        $results = $this->get_table_contents("staff", $condition);
        if (is_array($results)) {
            return TRUE;
        }
        return FALSE;
    }

    function check_login_status($cookie, $preparer) {
        if (isset($cookie["mail"])) {
            $login_data_out = $this->check_login_attempt($cookie, $preparer);
            $is_successful_login = $login_data_out[0];
            if ($is_successful_login) {
                $user_id = $login_data_out[1];
                $is_admin = $this->check_admin_status(["user_id" => $user_id], $preparer);
            }
        }
        return [$is_successful_login, $is_admin];
    }

    function get_cart_contents($user_id, $count=FALSE, $page_num=NULL, $records_per_page=NULL) {
        $selection = "prod_ids.product_id, product_name, price, category_name, description";
        if ($count) {
            $selection = "COUNT(*)";
        }
        $query = "WITH prod_ids as (
            SELECT product_id FROM cart WHERE user_id = {$user_id}
            )
            SELECT {$selection}
            FROM product_groups, prod_ids
            WHERE group_id = prod_ids.product_id";
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

class Data_Handler
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

    function get_post_arg($arg_name) {
        if (isset($this->post_data[$arg_name])) {
            return $this->post_data[$arg_name];
        }
        return FALSE;
    }

    function get_identified_data_amount()
    {
        return $this->post_data["{$this->id}-amount"];
    }

    function get_identifier() {
        if (isset($this->post_data["id"])) {
            $this->predef_par_amount += 2;
            return $this->post_data["id"];
        }
        return NULL;
    }

    function get_colective_data_end_index() {
        $cd_amount = $this->get_identified_data_amount();
        return count($this->post_data) -$this->predef_par_amount - $cd_amount -1;
    }

    function get_colective_data()
    {
        $cd_end_index = -$this->predef_par_amount -1;
        if ($this->id) {
            $cd_end_index = $this->get_colective_data_end_index($this->predef_par_amount, $this->id);
        }
        return array_slice($this->post_data, 0, $cd_end_index);
    }

    function get_identified_data()
    {
        $identified_data_start = -$this->predef_par_amount - $this->get_identified_data_amount()-1;
        $identified_data = array_slice($this->post_data, $identified_data_start, -$this->predef_par_amount-1);
        $unidentified_data = [];
        foreach ($identified_data as $k=>$v) {
            $unidentified_data[explode("-", $k)[1]] = $v;
        }
        return $unidentified_data;
    }

    function handle_form() {
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
{
    function tag_data($tag, $data)
    {
        return [$tag => $data];
    }

    function identify_data($data, $id)
    {
        $new_data = [];
        foreach ($data as $k=>$v) {
            $new_data["{$id}-{$k}"] = $v;
        }
        $new_data["id"] = $id;
        $new_data["{$id}-amount"] = count($data);
        return $new_data;
    }

    function get_query_params($val_arr, $mode = "pk") {
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
                }
            } else {
                if ($mode == "pk") {
                    $cond .= " and $key = '$value'";
                } elseif ($mode == "up") {
                    $cond .= ", $key = '$value'";
                } elseif ($mode == "in") {
                    $cond .= ", '$value'";
                }
            }
            $i++;
        }
        if ($mode == "in") {
            $cond .= ")";
        }
        return $cond;
    }

    function get_query_output_row_to_list($query_out, $row_name) {
        $list = [];
        foreach ($query_out as $row) {
            array_push($list, $row[$row_name]);
        }
        return $list;
    }

    private function is_valid($user_input, $pattern)
    {
        // Method that checks user input whether it is safe for database.
        return preg_match($pattern, $user_input);
    }

    function check_user_input($user_input_arr, $pattern="/^[\w\s]+$/")
    {
        $is_valid = TRUE;
        foreach (array_values($user_input_arr) as $inpt) {
            if (!$this->is_valid($inpt, $pattern)) {
                $is_valid = FALSE;
                break;
            }
        }
        return $is_valid;
    }
}

abstract class Html_Object
{
    protected $class_name;
    protected $id_name;

    abstract protected function get_contents();

    protected function submerge_in_div($contents)
    {
        /*
            Method that submerges contents in html div with given class name
            and id.
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
        // Method that returns html code.
        $html_code = $this->get_contents();
        if ($this->class_name or $this->id_name) {
            $html_code = $this->submerge_in_div($html_code);
        }
        return $html_code;
    }

    function create()
    {
        // Method that creates html object.
        print($this->get_html());
    }
}

abstract class Form extends Html_Object
{
    protected $link;
    protected $data;
    protected $class_name;
    protected $id_name;

    function __construct($data, $link, $class_name = Null, $id_name = NULL)
    {
        $this->data = $data;
        $this->link = $link;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    protected function modify_data($key, $value) {
        $this->data[$key] = $value;
    }

    protected function submerge_in_form($contents, $link, $method = "post")
    {
        /*
            Method that submerges contents in form code block.
            Defines where form will be send and what method will be used.
         */
        return "<form action=\"{$link}\" method=\"{$method}\">{$contents}</form>";
    }

    protected function submerge_logic($contents)
    {
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
        $inputs = "";
        foreach ($this->data as $name=>$value) {
            $inputs .= $this->get_input_row("hidden", $name, $value);
        }
        return $inputs;
    }

    function get_html() {
        $contents = $this->get_contents();
        $html_code = $this->submerge_logic($contents);
        return $html_code;
    }

    function create() {
        print($this->get_html());
    }
}

class Btn_Form extends Form
{
    private $btn_text;
    private $btn_name;

    function __construct($btn_text = NULL, $btn_name = "f_btn_submit",
                         $data = NULL, $link = NULL, 
                         $class_name = NULL, $id_name = NULL)
    {
        $this->btn_text = $btn_text;
        $this->btn_name = $btn_name;
        $this->data = $data;
        $this->link = $link;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    protected function get_contents()
    {
        $contents = $this->add_hidden_data();
        $contents .= $this->get_input_row("submit", $this->btn_name, $this->btn_text);
        return $contents;
    }
}

abstract class Multichoice_Form extends Form
{
    protected function get_label_row($id, $label_text)
    {
        return "<label for=\"{$id}\">{$label_text}</label>";
    }
}

class Text_Form extends Multichoice_Form
{
    private $preset_data;
    private $btn_name;

    function __construct($data, $link, $preset_data=TRUE, 
     $btn_name="f_t_btn_submit", $class_name = NULL, $id_name = NULL)
    {
        $this->data = $data;
        $this->link = $link;
        $this->preset_data = $preset_data;
        $this->btn_name = $btn_name;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    protected function get_contents()
    {
        $contents = "";
        foreach ($this->data[0] as $k => $v) {
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
        $btn = new Btn_Form("Submit", $this->btn_name, $this->data[1], NULL, $this->btn_name);
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
        $btn = new Btn_Form("Submit", "f_r_btn_submit", $this->data[1], NULL, "f_r_btn_submit");
        $contents .= $btn->get_html();
        return $contents;
    }
}

class Multichoice_Btn_Form extends Form
{
    protected function get_contents()
    {
        // Suported input type array[$data_name=>array[values...]]
        $contents = "";
        $key_name = array_keys($this->data)[0];
        foreach ($this->data[$key_name] as $table_name) {
            $data = [$key_name => $table_name];
            $form_btn = new Btn_Form($table_name, "f_m_btn_submit", $data, $this->link);
            $contents .= $form_btn->get_html();
        }
        return $contents;
    }
}

class Table extends Html_Object
{
    private $table_data;
    private $col_names;
    private $page_num;
    private $primary_keys;
    private $link;

    function __construct($table_data, $col_names, $primary_keys, $table_name, 
     $page_num, $link="update_table.php", $class_name = "table", $id_name = NULL)
    {
        $this->table_data = $table_data;
        if (is_null($col_names)) {
            $col_names = $this->get_col_names_from_data();
        }
        $this->col_names = array_flip($col_names);
        $this->page_num = $page_num;
        if ($table_name) {
            $this->table_name = $table_name;
            $this->primary_keys = $primary_keys[$table_name];
        } else {
            $this->primary_keys = $primary_keys;
        }
        $this->link = $link;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
        $this->btn_data = NULL;
    }

    function set_btn_data($data) {
        $this->btn_data = $data;
    }

    private function get_col_names_from_data() {
        return array_keys($this->table_data[0]);
    }

    private function get_table_row($data_row, $type = "td")
    /** Method that returns html code that creates table row.
     * @param array $data_row
     * @param string $type
     */
    {
        if ($type == "td") { // data cell mode
            $inpt_class_name = "data_cell";
        } else {
            $type = "th";  // heading mode
            $inpt_class_name = "col_name";
        }
        $cells = "";
        foreach ($data_row as $key => $value) {
            // if current key is ment to be displayed for the user
            if (array_key_exists($key, $this->col_names) or array_key_exists($value, $this->col_names)) {
                if ($value == FALSE) {  // jakaś magia phpa, true to 1 ale false to nic
                    $value = 0;
                }
                $cells .= "<{$type} class=\"{$inpt_class_name}\">{$value}</{$type}>";
            }
            if ($type == "td") {  // collecting data for btns
                if (array_key_exists($key, $this->primary_keys)) {
                    $post_data[$key] = $value;
                }
            }
        }
        if ($type == "td") {  // creating btn
            if ($this->table_name) {
                $post_data["table_name"] = $this->table_name;
            }
            $post_data["page_num"] = $this->page_num;
            if ($this->btn_data) {
                foreach($this->btn_data as $k=>$v) {
                    $post_data[$k] = $v;
                }
            }
            $form_btn = new Btn_Form("X", "f_btn_action", $post_data, $this->link);
            $cells .= "<{$type} class=\"actions\">{$form_btn->get_html()}</{$type}>";
        }
        return $cells;
    }

    private function get_col_names_row()
    /**
     * @return string html row with colnames.
     */
    {
        $cells = $this->get_table_row(array_keys($this->col_names), "th");
        return "<tr>{$cells}</tr>";
    }

    private function get_table_contents()
    {
        $contents = "";
        foreach ($this->table_data as $data_row) {
            $cells = $this->get_table_row($data_row);
            $contents .= "<tr>{$cells}</tr>";
        }
        return $contents;
    }

    protected function get_contents()
    {
        $table = $this->get_col_names_row();
        $table .= $this->get_table_contents();
        return "<table>{$table}</table>";
    }
}

class Pagination extends Html_Object
{
    private $table_name;
    private $page_num;
    private $records_per_page;
    private $total_row_count;
    private $link;

    function __construct($table_name, $page_num, $records_per_page, $total_row_count, 
                         $link, $class_name = "comb_pagination", $id_name = NULL)
    {
        $this->table_name = $table_name;
        $this->page_num = $page_num;
        $this->records_per_page = $records_per_page;
        $this->total_row_count = $total_row_count;
        $this->link = $link;
        $this->class_name = $class_name;
        $this->id_name = $id_name;
    }

    protected function get_contents()
    {
        $data = [];
        if ($this->table_name) {
            $data["table_name"] = $this->table_name;
        }

        $pagination = "";
        if ($this->page_num > 0) {
            // Creates pagination that allows user to go left.
            $new_page_num = $this->page_num - 1;
            $data["page_num"] = $new_page_num;
            $pagi_btn = new Btn_Form("left", "form_pagi_left_btn", $data, $this->link);
            $pagination .= $pagi_btn->get_html();
            // if there should exists right button.
          } if (($this->page_num+1)*$this->records_per_page<$this->total_row_count) {
              // Creates pagination that allows user to go right.
            $new_page_num = $this->page_num + 1;
            $data["page_num"] = $new_page_num;
            $pagi_btn = new Btn_Form("right", "form_pagi_right_btn", $data, $this->link);
            $pagination .= $pagi_btn->get_html();
          }
        return $pagination;
    }
}

class Text_Field extends Html_Object
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