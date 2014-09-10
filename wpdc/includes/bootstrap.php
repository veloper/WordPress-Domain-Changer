<?php

error_reporting( E_ALL );

define( "WPDC_ROOT_DIR", realpath( dirname( __FILE__ ) . '/../../' ) );
define( "WP_ROOT_DIR", realpath( WPDC_ROOT_DIR . '/../../' ) );

require_once dirname( __FILE__ ) . '/../config.php';

require_once 'classes/class.PhpFile.php';
require_once 'classes/class.PhpSerializedString.php';

require_once 'classes/class.Database.php';
require_once 'classes/class.DatabaseTable.php';
require_once 'classes/class.DatabaseTableRecord.php';
require_once 'classes/class.WordPressDatabase.php';
require_once 'classes/class.Alteration.php';

require_once 'classes/class.View.php';
require_once 'classes/class.BaseController.php';
require_once 'classes/class.Controller.php';
