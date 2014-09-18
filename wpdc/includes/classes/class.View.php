<?php
class View {
    protected $path = null;
    protected $data = array();

    public function __construct($path) {
        $this->path = $path;
    }

    public function htmlEncode($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    public function htmlEncodeSql($sql)
    {
        $encodedSql = $this->htmlEncode($sql);

        $keywords = array("SELECT", "DELETE", "INSERT", "UPDATE", "FROM", "WHERE", "SET", "VALUES", "LIMIT", "REPLACE", "GROUP BY", "DESC", "ASC", "AND", "OR", "IN");
        foreach ($keywords as $keyword) $encodedSql = str_replace($keyword, "<span class='sql_keyword'>$keyword</span>", $encodedSql);

        $encodedSql = preg_replace('/([^\\\\])&quot;(.*?)([^\\\\])&quot;/', "$1<span class='sql_string'>&quot;$2$3&quot;</span>", $encodedSql);
        $encodedSql = preg_replace('/([^\\\\])`(.*?)([^\\\\])`/', "$1<span class='sql_backtick'>`$2$3`</span>", $encodedSql);

        return $encodedSql;
    }

    public function indicator($bool)
    {
        return $bool ? '<span class="success">&#10004;</span>' : '<span class="danger">&#10008;</span>';
    }

    public function highlight($needle, $haystack)
    {
        return str_ireplace($needle, "<span class='highlight'>$needle</span>", $haystack);
    }

    public function getFlashMessages()
    {
        $array = array();
        foreach ($this->data["flash"] as $type => $messages) {
            foreach ($messages as $message) {
                $array[] = array("type" => $type, "message" => $message);
            }
        }
        return $array;
    }

    public function render(Array $data) {
        $this->data = $data;
        extract($data);
        ob_start();
        include( $this->path );
        $string = ob_get_contents();
        ob_end_clean();
        return $string;
    }
}
