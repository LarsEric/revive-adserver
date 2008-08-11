<?php
/*
+---------------------------------------------------------------------------+
| OpenX v${RELEASE_MAJOR_MINOR}                                                                |
| =======${RELEASE_MAJOR_MINOR_DOUBLE_UNDERLINE}                                                                |
|                                                                           |
| Copyright (c) 2003-2008 OpenX Limited                                     |
| For contact details, see: http://www.openx.org/                           |
|                                                                           |
| This program is free software; you can redistribute it and/or modify      |
| it under the terms of the GNU General Public License as published by      |
| the Free Software Foundation; either version 2 of the License, or         |
| (at your option) any later version.                                       |
|                                                                           |
| This program is distributed in the hope that it will be useful,           |
| but WITHOUT ANY WARRANTY; without even the implied warranty of            |
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the             |
| GNU General Public License for more details.                              |
|                                                                           |
| You should have received a copy of the GNU General Public License         |
| along with this program; if not, write to the Free Software               |
| Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA |
+---------------------------------------------------------------------------+
/**
 * OpenX Schema Management Utility
 *
 * @author     Monique Szpak <monique.szpak@openx.org>
 *
 * $Id$
 *
 */

require_once 'oxSchema-common.php';

phpAds_PageHeader("devtools-schema",'','../../');

define('OX_CORE', '/etc/');
define('OX_PLUG', $pluginPath.'%s/etc/');

if (array_key_exists('btn_changeset_archive', $_POST))
{
    header('Location: archive.php');
    exit;
}

require_once 'lib/oxSchema.inc.php';
if (array_key_exists('clear_cookies', $_POST))
{
    setcookie('schemaPath', '');
    setcookie('schemaFile', '');
}
else if ( array_key_exists('xml_file', $_REQUEST) && (!empty($_REQUEST['xml_file'])) )
{
    $schemaPath = dirname($_REQUEST['xml_file']);
    if (!empty($schemaPath))
    {
        $schemaPath.= DIRECTORY_SEPARATOR;
    }
    $schemaFile = basename($_REQUEST['xml_file']);
    if ($schemaFile==$_COOKIE['schemaFile'])
    {
        $schemaPath = $_COOKIE['schemaPath'];
    }
    $_POST['table_edit'] = '';
}
else if ( array_key_exists('schemaFile', $_COOKIE) && (!empty($_COOKIE['schemaFile'])))
{
    $schemaPath = $_COOKIE['schemaPath'];
    $schemaFile = $_COOKIE['schemaFile'];
}
if (empty($schemaPath) || empty($schemaFile))
{
    $schemaPath = '/etc/';
    $schemaFile = 'tables_core.xml';
}
setcookie('schemaPath', $schemaPath);
setcookie('schemaFile', $schemaFile);

global $oaSchema;
$oaSchema = & new Openads_Schema_Manager($schemaFile, '', $schemaPath);

if (is_array(($aErrs = $oaSchema->checkPermissions())))
{
    setcookie('schemaFile', '');
    setcookie('schemaPath', '');
    $errorMessage =
        join("<br />\n", $aErrs['errors']) . "<br /><br ><hr /><br />\n" .
        'To fix, please execute the following commands:' . "<br /><br >\n" .
        join("<br />\n", $aErrs['fixes']);
    die($errorMessage);
}

require_once 'lib/oxXajax.inc.php';

if (array_key_exists('btn_copy_final', $_POST))
{
    $oaSchema->createTransitional();
}
else if (array_key_exists('btn_schema_new', $_POST))
{
    $oaSchema->createNew($_POST['new_schema_name']);
}
else if (array_key_exists('btn_delete_trans', $_POST))
{
    $oaSchema->deleteTransitional();
}
else if (array_key_exists('btn_compare_schemas', $_POST))
{
    setcookie('changesetFile', '');
    if ($oaSchema->createChangeset($oaSchema->changes_trans, $_POST['comments']))
    {
        header('Content-Type: application/xhtml+xml; charset=ISO-8859-1');
        readfile($oaSchema->changes_trans);
        exit();
    }
}
else if (array_key_exists('btn_changeset_delete', $_POST))
{
    $oaSchema->deleteChangesTrans();
}
else if (array_key_exists('btn_commit_final', $_POST))
{
    $oaSchema->commitFinal($_POST['comments'], $_POST['version']);
}
else if (array_key_exists('btn_generate_dbo_final', $_POST))
{
    $oaSchema->setWorkingFiles();
    $oaSchema->parseWorkingDefinitionFile();
    $oaSchema->_generateDataObjects($oaSchema->changes_final,$oaSchema->_getBasename());
}
else if (array_key_exists('btn_generate_dbo_trans', $_POST))
{
    $oaSchema->setWorkingFiles();
    $oaSchema->parseWorkingDefinitionFile();
    $oaSchema->_generateDataObjects($oaSchema->changes_trans,$oaSchema->_getBasename());
}


$oaSchema->setWorkingFiles();


if (array_key_exists('btn_field_save', $_POST))
{
    $table = $_POST['table_edit'];
    $field_name_old = $_POST['field_name'];
    $field_name_new = $_POST['fld_new_name'];
    $field_type_old = $_POST['field_type'];
    $field_type_new = $_POST['fld_new_type'];
    $oaSchema->fieldSave($table, $field_name_old, $field_name_new, $field_type_old, $field_type_new);
}
else if (array_key_exists('btn_field_add', $_POST))
{
    $table = $_POST['table_edit'];
    $field_name = $_POST['field_add'];
    $dd_field_name = $_POST['sel_field_add'];
    $oaSchema->fieldAdd($table, $field_name, $dd_field_name);
}
else if (array_key_exists('btn_field_del', $_POST))
{
    $table = $_POST['table_edit'];
    $field = $_POST['field_name'];
    $oaSchema->fieldDelete($table, $field);
}
else if (array_key_exists('btn_index_del', $_POST))
{
    $table = $_POST['table_edit'];
    $index = $_POST['index_name'];
    $oaSchema->indexDelete($table, $index);
}
else if (array_key_exists('btn_index_add', $_POST))
{
    $table = $_POST['table_edit'];
    $index_name = $_POST['index_add'];
    $index_fields = $_POST['idx_fld_add'];
    $sort_desc = $_POST['idx_fld_desc'];
    $unique = $_POST['idx_unique'];
    $primary = $_POST['idx_primary'];
    $oaSchema->indexAdd($table, $index_name, $index_fields, $primary, $unique, $sort_desc);
}
else if (array_key_exists('btn_index_save', $_POST))
{
    $table = $_POST['table_edit'];
    $index_name = $_POST['index_name'];
    $index_no = $_POST['index_no'];
    $index_def = $_POST['idx'][$index_no];
    $oaSchema->indexSave($table, $index_name, $index_def);
}
else if (array_key_exists('btn_link_del', $_POST))
{
    $table = $_POST['table_edit'];
    $link_name = $_POST['link_name'];
    $oaSchema->linkDelete($table, $link_name);
}
else if (array_key_exists('btn_link_add', $_POST))
{
    $table = $_POST['table_edit'];
    $link_add = $_POST['link_add'];
    $link_add_target = $_POST['link_add_target'];
    $oaSchema->linkAdd($table, $link_add, $link_add_target);
}
else if (array_key_exists('btn_table_save', $_POST))
{
    $table = $_POST['table_edit'];
    $table_name_new = $_POST['tbl_new_name'];
    $ok = $oaSchema->tableSave($table, $table_name_new);
    if ($ok) {
        $table = $table_name_new;
    }
}
else if (array_key_exists('btn_table_edit', $_POST))
{
    $table = $_POST['btn_table_edit'];
}
else if (array_key_exists('btn_table_delete', $_POST))
{
    $table = $_POST['table_edit'];
    $oaSchema->tableDelete($table);
    unset($table);
}
else if (array_key_exists('btn_table_cancel', $_POST))
{
    $table = '';
}
else if (array_key_exists('btn_table_new', $_POST))
{
    if (array_key_exists('new_table_name', $_POST))
    {
        $table = $_POST['new_table_name'];
        $oaSchema->tableNew($table);
        unset($table);
    }
}
else if (array_key_exists('table_edit', $_POST))
{
    $table = $_POST['table_edit'];
}

if (!$table)
{
    $oTpl = new OA_Plugin_Template('oxSchema.html','oxSchema');
    $oTpl->assign('xajaxLibPath', '../../../../lib/xajax');
    $file = $oTpl->_tpl_vars['xajaxLibPath'].'/xajax_js/xajax.js';
    $tmp = file_exists($file);
    $oTpl->debugging = true;
/*    $oTpl->assign('xmlcontent', file_get_contents($oaSchema->working_file_schema));
    $oTpl->assign('xmlfile', $oaSchema->working_file_schema);*/

/*    $xmlfile = $oaSchema->working_file_schema;
    $xslfile = 'xsl/mdb2_schema.xsl';


    // Load the XML source
    $xml = new DOMDocument;
    $ok = $xml->load($xmlfile);

    $xsl = new DOMDocument;
    $ok = $xsl->load($xslfile);
    // Configure the transformer
    $proc = new XSLTProcessor;
    $ok = $proc->hasExsltSupport();
    $proc->importStyleSheet($xsl); // attach the xsl rules
    $i = $proc->transformToUri($xml, MAX_PATH.'/var/templates_compiled/schema.html');

    $xslt = file_get_contents(MAX_PATH.'/var/templates_compiled/schema.html');

    $oTpl->assign('xslt', $xslt);*/

    $oTpl->display();
    /*$tmp = getcwd();
    readfile($oaSchema->working_file_schema);*/

    phpAds_PageFooter();

/*    header('Content-Type: application/xhtml+xml; charset=ISO-8859-1');
    readfile($oaSchema->working_file_schema);
    exit();*/
}
else
{
    $oaSchema->parseWorkingDefinitionFile();
    $aDD_definition  = $oaSchema->aDD_definition;
    $aDB_definition  = $oaSchema->aDB_definition;
    $aTbl_definition = $oaSchema->aDB_definition['tables'][$table];
    $aLinks          = $oaSchema->readForeignKeys($table);
    $aTbl_links      = $aLinks[$table];
    $aLink_targets   = $oaSchema->getLinkTargets();

    include 'templates/schema_edit.html';
    exit();
}


?>