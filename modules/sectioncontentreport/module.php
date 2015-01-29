<?php
/**
 * File containing the sectioncontentreport module configuration file, module.php
 *
 * @copyright Copyright (C) 1999 - 2015 Brookins Consulting. All rights reserved.
 * @copyright Copyright (C) 2013 - 2015 Think Creative. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or later)
 * @version 0.0.2
 * @package ezpsectioncontentreport
*/

// Define module name
$Module = array('name' => 'Section Content Report');

// Define module view and parameters
$ViewList = array();

// Define 'export' module view parameters
$ViewList['export'] = array( 'script' => 'export.php',
                             'functions' => array( 'export' ),
                             'default_navigation_part' => 'ezpsectioncontentreportnavigationpart',
                             'post_actions' => array( 'Download' ),
                             'params' => array( 'SectionID' ) );

// Define function parameters
$FunctionList = array();

// Define function 'export' parameters
$FunctionList['export'] = array();

?>