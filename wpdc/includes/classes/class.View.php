<?php
class View {
    protected $path = null;
    protected $data = array();

    public function __construct($path) {
        $this->path = $path;
    }

    public function htmlSafe($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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
