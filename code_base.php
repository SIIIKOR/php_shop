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
        $this->db_name = "mydatabase";
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
                echo "<div class=\"err_mess\">
                        <p>
                        {$e->getMessage()}<br>
                        Try again.
                        </p>
                     </div>";
            }
        }
        $conn = NULL;
        return $data;
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
            $val = explode(", ", $matches[1]);
            $out[$row["table_name"]] = $val;
        }
        return $out;
    }

    function get_db_contents_curr_page($table_name, $page_num, $records_per_page)
    {
        // Method that returns data for given page for given row amount per page.
        $offset = 0 + ($page_num) * $records_per_page;
        $query = "SELECT *
                  FROM {$table_name}
                  LIMIT {$records_per_page}
                  OFFSET {$offset}";
        $data = $this->run_query($query);
        return $data;
    }

    function get_table_row($table_name, $condition)
    {
        // returns row from given table based on condition.
        $query = "SELECT *
                  FROM $table_name
                  WHERE {$condition};";
        $data = $this->run_query($query);
        return $data;
    }

    function update_table_row($table_name, $condition, $values)
    {
        $query = "UPDATE {$table_name}
                  SET {$values}
                  WHERE {$condition};";
        $data = $this->run_query($query);
        return $data;
    }

    function delete_table_row($table_name, $condition)
    {
        $query = "DELETE FROM $table_name
            WHERE {$condition};";
        $data = $this->run_query($query);
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

    function __construct($post_data)
    {
        $this->post_data = $post_data;
    }

    function get_table_name()
    {
        return $this->post_data["table_name"];
    }

    function get_page_num()
    {
        return $this->post_data["page_num"];
    }

    function get_primary_key_amount()
    {
        return $this->post_data["pk_amount"];
    }

    function get_identifier() {
        return $this->post_data["identifier"];
    }

    private function get_primary_keys_start($predef_par_amount)
    {
        $pk_amount = $this->get_primary_key_amount();
        return count($this->post_data) - ($pk_amount + $predef_par_amount) - 1;
    }

    function get_primary_keys($predef_par_amount)
    {
        $pk_start = $this->get_primary_keys_start($predef_par_amount);
        return array_slice($this->post_data, $pk_start, -$predef_par_amount - 1);
    }

    function get_user_input($predef_par_amount)
    {
        $pk_start = $this->get_primary_keys_start($predef_par_amount);
        return array_slice($this->post_data, 0, $pk_start);
    }

    private function is_valid($user_input)
    {
        // Method that checks user input whether it is safe for database.
        $pattern = "/^[\w\s]+$/";
        return preg_match($pattern, $user_input);
    }

    function check_user_input($user_input_arr)
    {
        $is_valid = TRUE;
        foreach (array_values($user_input_arr) as $inpt) {
            if (!$this->is_valid($inpt)) {
                $is_valid = FALSE;
                break;
            }
        }
        return $is_valid;
    }
}

abstract class Html_Object
{
    protected $html_code;

    abstract protected function get_contents();

    protected function submerge_in_div($contents, $class_name, $id_name)
    {
        /*
            Method that submerges contents in html div with given class name
            and id.
        */
        $html_code = "<div class=\"{$class_name}\"";
        if ($id_name) {
            $html_code .= " id=\"{$id_name}\">";
        } else {
            $html_code .= ">";
        }
        $html_code .= "{$contents}</div>";
        return $html_code;
    }

    protected function get_html()
    {
        // Method that returns html code.
        return $this->html_code;
    }

    function create()
    {
        // Method that creates html object.
        print($this->html_code);
    }
}

abstract class Form extends Html_Object
{
    protected function sumberge_in_form($contents, $link, $method = "post")
    {
        /*
            Method that submerges contents in form code block.
            Defines where form will be send and what method will be used.
         */
        return "<form action=\"{$link}\" method=\"{$method}\">{$contents}</form>";
    }

    protected function get_input_row($type, $name, $value = NULL, $id = NULL, $req = FALSE)
    {
        /*
            Method that create html input object.
         */
        $input = "<input type=\"{$type}\"";
        if ($id) {
            $input .= " id=\"{$id}\"";
        }
        $input .= " name=\"{$name}\"";
        if ($value) {
            $input .= " value=\"{$value}\"";
        }
        if ($req) {
            $input .= " required";
        }
        $input .= ">";
        return $input;
    }

    protected function add_hidden_data($data, $identifier = NULL)
    {
        $inputs = "";
        $key_names = array_keys($data);
        $pk_amount = $data["pk_amount"];
        for ($i = 0; $i < count($data); $i++) {
            $name = $key_names[$i];
            if ($identifier and $i < $pk_amount) {
                $name = "{$identifier}_{$key_names[$i]}";
            }
            $inputs .= $this->get_input_row("hidden", $name, $data[$key_names[$i]]);
        }
        if ($identifier) {
            $inputs .= $this->get_input_row("hidden", "identifier", $identifier);
        }
        return $inputs;
    }
}

class Btn_Form extends Form
{
    private function construct_logic($btn_text, $btn_name, $link, $data,
                                     $class_name, $id_name)
    {
        $this->html_code = $this->get_contents($btn_text, $btn_name, $data);
        if ($link) {
            $this->html_code = $this->sumberge_in_form($this->html_code, $link);
        }
        if ($class_name or $id_name) {
            $this->html_code = $this->submerge_in_div($this->html_code, $class_name, $id_name);
        }
    }

    function __construct($btn_text = NULL, $btn_name = "f_btn_submit",
                         $link = NULL, $data = NULL, 
                         $class_name = NULL, $id_name = NULL)
    {
        if ($btn_text) {
            $this->construct_logic($btn_text, $btn_name, $link, $data,
                                   $class_name, $id_name);
        }
    }

    function construct_from_array($params_array)
    {
        $btn_text = $params_array[0];
        $btn_name = $params_array[1];
        $link = $params_array[2];
        $data = $params_array[3];
        $class_name = $params_array[4];
        $id_name = $params_array[5];
        $this->construct_logic($btn_text, $btn_name, $link, $data,
                                   $class_name, $id_name);
    }

    protected function get_contents()
    {
        $arg_list = func_get_args();
        $btn_text = $arg_list[0];
        $btn_name = $arg_list[1];
        $data = $arg_list[2];
        if ($data) {
            $contents = $this->add_hidden_data($data);
        }
        $contents .= $this->get_input_row("submit", $btn_name, $btn_text);
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

    function __construct($link, $data, $btn_text = "Submit",
                         $class_name = NULL, $id_name = NULL)
    {
        $contents = $this->get_contents($data, $btn_text);
        $this->html_code = $this->sumberge_in_form($contents, $link);
        if ($class_name or $id_name) {
            $this->html_code = $this->submerge_in_div($this->html_code, $class_name, $id_name);
        }
    }

    protected function get_contents()
    {
        $arg_list = func_get_args();
        $data = $arg_list[0];
        $btn_text = $arg_list[1];

        $contents = "";
        foreach ($data as $k => $v) {
            $contents .= $this->get_label_row($k, $k);
            $contents .= "<br>";
            $contents .= $this->get_input_row("text", $k, $v, $k, TRUE);
            $contents .= "<br>";
        }
        $contents .= $this->add_hidden_data($data, "pk");
        $btn = new Btn_Form($btn_text, "f_t_btn_submit", NULL, NULL, "f_t_btn_submit");
        $contents .= $btn->get_html();
        return $contents;
    }
}

class Radio_Form extends Multichoice_Form
{
    function __construct($link, $data, $btn_text = "Submit", $class_name = NULL, $id_name = NULL)
    {
        $contents = $this->get_contents($data, $btn_text);
        $this->html_code = $this->sumberge_in_form($contents, $link);
        if ($class_name or $id_name) {
            $this->html_code = $this->submerge_in_div($this->html_code, $class_name, $id_name);
        }
    }

    protected function get_contents()
    {
        $arg_list = func_get_args();
        $data = $arg_list[0];
        $btn_text = $arg_list[1];

        $contents = "";
        foreach ($data as $k => $v) {
            $contents .= $this->get_input_row("text", $k, $v, $k, TRUE);
            $contents .= $this->get_label_row($k, $k);
            $contents .= "<br>";
        }
        $contents .= $this->add_hidden_data($data, "pk");
        $btn = new Btn_Form($btn_text, "f_r_btn_submit", NULL, NULL, "f_r_btn_submit");
        $contents .= $btn->get_html();
        return $contents;
    }
}

class Multichoice_Btn_Form extends Form
{
    function __construct($table_names, $link, $class_name, $id_name = NULL)
    {
        $contents = $this->get_contents($table_names, $link);
        $this->html_code = $this->sumberge_in_form($contents, $link);
        if ($class_name or $id_name) {
            $this->html_code = $this->submerge_in_div($this->html_code, $class_name, $id_name);
        }
    }

    protected function get_contents()
    {
        $arg_list = func_get_args();
        $table_names = $arg_list[0];
        $link = $arg_list[1];

        $contents = "";
        foreach ($table_names as $table_name) {
            $data = [];
            $data["table_name"] = $table_name;
            $form_btn = new Btn_Form($table_name, "f_m_btn_submit", $link, $data);
            $contents .= $form_btn->get_html();
        }
        return $contents;
    }
}

class Table extends Html_Object
{
    function __construct($data, $col_names, $additional_data = NULL,
                         $class_name = "table", $id_name = NULL)
    {
        $contents = $this->get_contents($data, $col_names, $additional_data);
        $this->html_code = $this->submerge_in_div($contents, $class_name, $id_name);
    }

    private function get_table_row($data, $additional_data = NULL, $type = "td")
    {
        $class_name = "data_cell";
        if (!$type == "td") {
            $type = "th";
            $class_name = "col_name";
        }
        $cells = "";
        foreach (array_values($data) as $value) {
            $cells .= "<{$type} class=\"{$class_name}\">{$value}</{$type}>";
        }
        foreach (array_values($additional_data) as $addi_value) {
            $form_btn = new Btn_Form();
            $form_btn->construct_from_array($addi_value);
            $cells .= "<{$type} class=\"{$class_name}\">{$form_btn->get_html()}</{$type}>";
        }
        return $cells;
    }

    private function get_col_names_row($col_names, $additional_data)
    {
        $cells = $this->get_table_row($col_names, array_keys($additional_data), "th");
        return "<tr>{$cells}</tr>";
    }

    private function get_table_contents($data, $additional_data)
    {
        $contents = "";
        foreach ($data as $data_row) {
            $cells = $this->get_table_row($data_row, array_values($additional_data));
            $contents .= "<tr>{$cells}</tr>";
        }
    }

    protected function get_contents()
    {
        $arg_list = func_get_args();
        $data = $arg_list[0];
        $col_names = $arg_list[1];
        $additional_data = $arg_list[2];

        $table = $this->get_col_names_row($col_names, $additional_data);
        $table .= $this->get_table_contents($data, $additional_data);
        return $table;
    }
}

class Pagination extends Html_Object
{
    function __construct($table_name, $page_num, $records_per_page, $total_row_count, 
                         $link, $class_name = "comb_pagination", $id_name = NULL)
    {
        $this->html_code = $this->get_contents($table_name, $page_num, $records_per_page, $total_row_count, $link);
        if ($class_name or $id_name) {
            $this->html_code = $this->submerge_in_div($this->html_code, $class_name, $id_name);
        }
    }

    protected function get_contents()
    {
        $arg_list = func_get_args();
        $table_name = $arg_list[0];
        $page_num = $arg_list[1];
        $records_per_page = $arg_list[2];
        $total_row_count = $arg_list[3];
        $link = $arg_list[4];

        $data = [];
        $data["table_name"] = $table_name;

        $pagination = "";
        if ($page_num > 0) {
            // Creates pagination that allows user to go left.
            $new_page_num = $page_num - 1;
            $data["page_num"] = $new_page_num;
            $pagi_btn = new Btn_Form("left", "f_p_l_btn", $link, $data);
            $pagination .= $pagi_btn->get_html();
            // if there should exists right button.
          } if (($page_num+1)*$records_per_page<$total_row_count) {
              // Creates pagination that allows user to go right.
            $new_page_num = $page_num + 1;
            $data["page_num"] = $new_page_num;
            $pagi_btn = new Btn_Form("left", "f_p_l_btn", $link, $data);
            $pagination .= $pagi_btn->get_html();
          }
        return $pagination;
    }
}