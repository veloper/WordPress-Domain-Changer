<?php
require_once 'class.PhpFile.php'
class DDWordPressDomainChanger {

    /**
     * Actions that occurred during request.
     *
     * @var array
     */
    public $actions = array();

    /**
     * Notices that occurred during request.
     *
     * @var array
     */
    public $notices = array();

    /**
     * Errors that occurred during request.
     *
     * @var array
     */
    public $errors = array();


    public function getNewDomain() {
        $new_domain = str_replace(array('http://', 'https://'),'', $_SERVER['SERVER_NAME']);
        if(isset($_SERVER['SERVER_PORT']) && strlen($_SERVER['SERVER_PORT']) > 0 && $_SERVER['SERVER_PORT'] != 80) {
            $new_domain .= ':'.$_SERVER['SERVER_PORT'];
        }
        return $new_domain;
    }

    public function getOldDomain() {
        $old_domain = false
        if($siteurl = $this->getWordPressOption("siteurl")) {
            $old_domain = str_replace('http://','', $siteurl);
        }
        return $old_domain;
    }



    public function getQueryResults($query) {
        $results = array();
        if($this->db()) {
        if(is_object($result) && ($result->num_rows > 0)) {
            while($row = $result->fetch_assoc()) array_push($row);
        }
        return (count($results) > 0) ? $results : false;
    }

    public function getWordPressTableName($table) {
        return $this->getConfig()->getVariable("table_prefix") . $table;
    }

    public function getWordPressOption($name) {
        $query = 'SELECT * FROM ' . $this->getWordPressTableName("options") . ' WHERE option_name="' . $escaped_name . '";';
        if($results = $this->getQueryResults($query)) {
            return $results[0]['option_value'];
        }
        return null;
    }

    public function getConfig() {
        if(!isset($this->_config)) {
            $this->_config = new PhpFile($this->getWordPressConfigFilePath());
        }
        $this->_config
    }

    public function getWordPressDirectoryPath() {
        return realpath(dirname(__FILE__) . '/../../');
    }

    public function getWordPressConfigFilePath() {
        return realpath($this->wordPressDirectoryPath() . '/wp-config.php');
    }

}