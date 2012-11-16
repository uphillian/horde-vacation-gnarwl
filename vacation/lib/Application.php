<?php
/**
 * Vacation application interface.
 *
 * This file brings in all of the dependencies that every Vacation script will
 * need, and sets up objects that all scripts use.
 *
 * Copyright 2002-2012 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.horde.org/licenses/gpl.php.
 *
 * @author  Eric Rostetter <eric.rostetter@physics.utexas.edu>
 * @author  Ben Klang <ben@alkaloid.net>
 * @package Vacation
 */

if (!defined('VACATION_BASE')) {
    define('VACATION_BASE', __DIR__. '/..');
}

if (!defined('HORDE_BASE')) {
    /* If horde does not live directly under the app directory, the HORDE_BASE
     * constant should be defined in config/horde.local.php. */
    if (file_exists(VACATION_BASE. '/config/horde.local.php')) {
        include VACATION_BASE . '/config/horde.local.php';
    } else {
        define('HORDE_BASE', VACATION_BASE . '/..');
    }
}

/* Load the Horde Framework core (needed to autoload
 * Horde_Registry_Application::). */
require_once HORDE_BASE . '/lib/core.php';

class Vacation_Application extends Horde_Registry_Application {
    /**
     * The version of passwd as shown in the admin view
     */
    public $version = 'H5 (5.0-git)';
}