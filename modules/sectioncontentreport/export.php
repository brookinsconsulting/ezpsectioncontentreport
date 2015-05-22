<?php
/**
 * File containing the sectioncontentreport/export module view.
 *
 * @copyright Copyright (C) 1999 - 2015 Brookins Consulting. All rights reserved.
 * @copyright Copyright (C) 2013 - 2015 Think Creative. All rights reserved.
 * @license http://www.gnu.org/licenses/gpl-2.0.txt GNU General Public License v2 (or later)
 * @version 0.0.2
 * @package ezpsectioncontentreport
 */

set_time_limit( -1 );

/**
 * Default module parameters
 */
$module = $Params["Module"];
$sectionID = $Params["SectionID"];

/**
* Default class instances
*/

// Parse HTTP POST variables
$http = eZHTTPTool::instance();

// Access system variables
$sys = eZSys::instance();

// Init template behaviors
$tpl = eZTemplate::factory();

// Access ini variables
$ini = eZINI::instance();
$iniSectioncontentreport = eZINI::instance( 'ezpsectioncontentreport.ini' );

// Report file variables
$storageDirectory = eZSys::cacheDirectory();
$sectionContentCsvReportName = 'ezpsectioncontentreport';
$sectionContentCsvReportFileName = $sectionContentCsvReportName . '_section_id_';

/** Default variables **/
$siteNodeUrlPrefix = "http://";
$siteNodeUrlHostname = $ini->variable( 'SiteSettings', 'SiteURL' );

// Set fetch parameter ignoreVisibility to false to not fetch hidden nodes
$ignoreVisibility = $iniSectioncontentreport->variable( 'eZPSectionContentReportSettings', 'ExcludeHiddenNodes' ) == 'enabled' ? false : true;

if( !$ignoreVisiblity )
{
    // Force ini values to hide hidden nodes on the fly for this request
    $ini->setVariable( 'SiteAccessSettings', 'ShowHiddenNodes', 'false' );
}

$rootNodeID = 2;
$limit = 10000000;
$offset = 0;
$openedFPs = array();
$csvHeader = array( 'ContentObjectID', 'NodeID', 'ContentType', 'SectionID', 'Section Name', 'Visibility', 'Modified Date', 'Node Name', 'Node Url' );

/**
 * Handle section export action
 */
if ( $sectionID > 0 )
{
    $uniqueSectionContentCsvReportFileName = $sectionContentCsvReportFileName . $sectionID . '_-_' . date( "Y_m_d_-_H_i_s" ) . '.csv';
    $uniqueSectionContentCsvReportFileNameFullPath = $storageDirectory . '/' . $uniqueSectionContentCsvReportFileName;

    $subTreeParams = array( 'Limit' => $limit,
                            'Offset' => $offset,
                            'AttributeFilter' => array( array( 'section', '=', $sectionID )  ),
                            'SortBy' => array( 'id' => 'desc' ),
                            'Depth' => 10,
                            'MainNodeOnly' => true,
                            'IgnoreVisibility' => $ignoreVisibility );

    /** Fetch nodes stored in the given section **/
    $nodes = eZContentObjectTreeNode::subTreeByNodeID( $subTreeParams, $rootNodeID );
    $nodesCount = count( $nodes );

    /** Open report file for writting **/

    if ( !isset( $openedFPs[$uniqueSectionContentCsvReportFileName] ) )
    {
        $fileName = $uniqueSectionContentCsvReportFileNameFullPath;

        if ( !file_exists( $storageDirectory ) )
        {
            mkdir( $storageDirectory, 0775);
        }

        $tempFP = @fopen( $fileName, "w" );

        if ( $tempFP )
        {
            $openedFPs[$uniqueSectionContentCsvReportFileName] = $tempFP;
        }
    }

    /** Define report file pointer **/

    $fp = $openedFPs[$uniqueSectionContentCsvReportFileName];

    /** Write report csv header **/

    fputcsv( $fp, $csvHeader, ';' );

    /** Iterate over nodes **/

    while ( list( $key, $contentObjectTreeNode ) = each( $nodes ) )
    {
        $objectData = array();

        /** Fetch object details **/
        $contentObjectID = $contentObjectTreeNode->attribute( 'contentobject_id' );

        $object = eZContentObject::fetch( $contentObjectID );
        $objectName = $object->name();
        $objectMainNode = $object->mainNode();
        $objectModifiedDate = $object->attribute( 'modified' );
        $objectModifiedDateFormated = date( "m/d/Y H:i:s", $objectModifiedDate );
        $objectSectionID = $object->attribute( 'section_id' );
        $objectMainNodeSectionName = eZSection::fetch( $objectSectionID )->attribute( 'name' );

        if ( is_object( $objectMainNode ) )
        {
            $objectMainNodeID = $objectMainNode->attribute( 'node_id' );
            $objectClassName = $objectMainNode->attribute( 'class_name' );
            $objectMainNodePath = $siteNodeUrlPrefix . $siteNodeUrlHostname . '/' . $objectMainNode->attribute( 'url' );
            $objectMainNodeVisibility = $objectMainNode->attribute( 'is_hidden' );
            $objectMainNodeParentVisibility = $objectMainNode->attribute( 'is_invisible' );

            /** Build report for objects **/

            $objectData[] = $contentObjectID;

            $objectData[] = $objectMainNodeID;

            $objectData[] = $objectClassName;

            $objectData[] = $objectSectionID;

            $objectData[] = $objectMainNodeSectionName;

            if( $objectMainNodeVisibility == 1 )
            {
                $objectData[] = 'Hidden';
            }
            elseif( $objectMainNodeParentVisibility == 1 )
            {
                $objectData[] = 'Hidden By Parent';
            }
            else
            {
                $objectData[] = 'Visible';
            }

            $objectData[] = $objectModifiedDateFormated;

            $objectData[] = $objectName;

            $objectData[] = $objectMainNodePath;

            /** Test if report file is opened **/
            if ( $fp )
            {
                /** Write report datat to file **/
                fputcsv( $fp, $objectData, ';' );
            }
        }
    }

    /** Close report file **/

    while ( $fp = each( $openedFPs ) )
    {
        fclose( $fp['value'] );
    }

    /** Assign permissions to report file **/

    chmod( $fileName, 0777);

    /** Send report to browser for download **/

    eZFile::download( $fileName, true, $uniqueSectionContentCsvReportFileName );

    /** Remove temporary report cache file **/

    @unlink( $fileName );
}

$module->redirectTo( 'section/list' );

?>