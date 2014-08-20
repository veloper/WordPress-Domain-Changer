<?php

class LocalWebServer {

  protected $host    = null;
  protected $port    = null;
  protected $docroot = null;
  protected $router  = null;

  public function __construct( $options = array() ) {
    $this->host    = array_key_exists( "host", $options )    ? $options["host"]      : '0.0.0.0';
    $this->port    = array_key_exists( "port", $options )    ? $options["port"]      : 8000;
    $this->docroot = array_key_exists( "docroot", $options ) ? $options["docroot"]   : null;
    $this->router  = array_key_exists( "router", $options )  ? $options["router"]    : null;
  }

  public function rootUrl() {
    return "http://{$this->host}:{$this->port}";
  }

  public function pathUrl( $path ) {
    return $this->rootUrl() . '/' . trim( $path, '/' );
  }

  // Credit: http://stackoverflow.com/a/6609181/493702
  function request( $method, $path, $data = array() ) {
    $options = array(
      'http' => array(
        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
        'method'  => strtoupper( $method ),
        'content' => http_build_query( $data )
      )
    );
    $context = stream_context_create( $options );
    return file_get_contents( $this->pathUrl( $path ), false, $context );
  }


  public function start() {
    if ( $this->isRunning() ) return false;

    shell_exec( $this->cmd() . " &> /dev/null &" );

    $timeout = time() + 5;
    while ( time() < $timeout ) {
      if ( $this->pid() > 0 ) return true;
      sleep( 0.25 );
    }

    return false;
  }

  public function isRunning() {
    return $this->pid() > 0;
  }

  public function stop() {
    if ( !$this->isRunning() ) return false;
    shell_exec( "kill -9 " . $this->pid() );
  }

  public function restart() {
    $this->stop();
    $this->start();
  }


  public function cmd() {
    $cmd = array();
    $cmd[] = "php";
    $cmd[] = "-c php.ini";
    $cmd[] = "-d error_reporting=0";
    $cmd[] = "-d display_errors=0";
    $cmd[] = "-S " . $this->host . ":" . $this->port;
    if ( $this->docroot )
      $cmd[] = "-t " . escapeshellarg( $this->docroot );
    if ( $this->router )
      $cmd[] = escapeshellarg( $this->router );

    return implode( " ", $cmd );
  }

  public function pid() {
    return (int) current( explode( " ", (string)current( preg_grep( "/php.+\-S\s+". preg_quote( $this->host ) . ":" . preg_quote( $this->port )."/", explode( "\n", `ps` ) ) ) ) );
  }

}
