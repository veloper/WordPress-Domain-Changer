<?php
class PhpFile {

    public $path        = null;
    public $contents    = null;

    public function __construct( $path ) {
        $this->path = $path;
    }

    public function getPath() {
        return $this->path;
    }

    public function load() {
        if ( $this->exists() ) {
            $this->contents = file_get_contents( $this->getPath() );
        }
    }

    public function getContents() {
        if ( !$this->contents ) $this->load();
        return $this->contents;
    }

    public function exists() {
        return file_exists( $this->getPath() );
    }

    /**
     * Gets a constant's value
     *
     * @param string  - The constant's name
     * @return mixed; the value, or false if not found.
     */
    public function getConstant( $name ) {
        preg_match( "!define\('" . $name . "',[^']*'(.+?)'\);!", $this->getContents(), $matches );
        return ( isset( $matches[1] ) ) ? $matches[1] : false;
    }

    /**
     * Gets variable's value
     *
     * @param string  - The variables's name (without the dollar sign)
     * @return mixed; the value, or false if not found.
     */
    public function getVariable( $name ) {
        preg_match( "!\\\$" . $name . "[^=]*=[^']*'(.+?)';!", $this->getContents(), $matches );
        return ( isset( $matches[1] ) ) ? $matches[1] : false;
    }

    public static function readFromRelativePath( $relative_path ) {
        $search_paths = explode( PATH_SEPARATOR, get_include_path() );
        $parts        = explode( DIRECTORY_SEPARATOR, dirname( __FILE__ ) );
        while ( count( $parts ) > 0 ) {
            $search_paths[] = implode( DIRECTORY_SEPARATOR, $parts );
            array_pop( $parts );
        }
        $search_paths = array_filter( $search_paths, 'strlen' );

        $file = null;
        foreach ( $search_paths as $path ) {
            $full_path = $path . DIRECTORY_SEPARATOR .  $relative_path;
            if ( file_exists( $full_path ) ) {
                $file = realpath( $full_path );
                break;
            }
        }
        return $file ? new PhpFile( $file ) : false;
    }
}
