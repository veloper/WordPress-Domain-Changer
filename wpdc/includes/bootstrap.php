<?php
// == Environment ==============================================================

error_reporting( E_ALL );
set_time_limit( 60 );
mb_internal_encoding( 'UTF-8' );

// == Constants ==============================================================

define( "WPDC_SESSION_TTL", 60 * 10 );
define( "WPDC_ROOT_DIR", realpath( dirname( __FILE__ ) . '/../../' ) );
define( "WP_ROOT_DIR", realpath( WPDC_ROOT_DIR . '/../../' ) );


// == Requires ==============================================================

require_once dirname( __FILE__ ) . '/../config.php';

require_once dirname( __FILE__ ) . '/classes/class.PhpFile.php';
require_once dirname( __FILE__ ) . '/classes/class.PhpSerializedString.php';

require_once dirname( __FILE__ ) . '/classes/class.Database.php';
require_once dirname( __FILE__ ) . '/classes/class.DatabaseTable.php';
require_once dirname( __FILE__ ) . '/classes/class.DatabaseTableRecord.php';
require_once dirname( __FILE__ ) . '/classes/class.WordPressDatabase.php';
require_once dirname( __FILE__ ) . '/classes/class.Alteration.php';

require_once dirname( __FILE__ ) . '/classes/class.View.php';
require_once dirname( __FILE__ ) . '/classes/class.BaseController.php';
require_once dirname( __FILE__ ) . '/classes/class.Controller.php';
