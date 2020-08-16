<?php
/**
 * Xiaomi BT Thermometer
 * @package project
 * @author Wizard <sergejey@gmail.com>
 * @copyright http://majordomo.smartliving.ru/ (c)
 * @version 0.1 (wizard, 21:08:02 [Aug 12, 2020])
 */
//
//
class xiaomibtthermometer extends module
{
    /**
     * xiaomibtthermometer
     *
     * Module class constructor
     *
     * @access private
     */
    function __construct()
    {
        $this->name = "xiaomibtthermometer";
        $this->title = "Xiaomi BT Thermometer";
        $this->module_category = "<#LANG_SECTION_DEVICES#>";
        $this->checkInstalled();
    }

    /**
     * saveParams
     *
     * Saving module parameters
     *
     * @access public
     */
    function saveParams($data = 1)
    {
        $p = array();
        if (IsSet($this->id)) {
            $p["id"] = $this->id;
        }
        if (IsSet($this->view_mode)) {
            $p["view_mode"] = $this->view_mode;
        }
        if (IsSet($this->edit_mode)) {
            $p["edit_mode"] = $this->edit_mode;
        }
        if (IsSet($this->tab)) {
            $p["tab"] = $this->tab;
        }
        return parent::saveParams($p);
    }

    /**
     * getParams
     *
     * Getting module parameters from query string
     *
     * @access public
     */
    function getParams()
    {
        global $id;
        global $mode;
        global $view_mode;
        global $edit_mode;
        global $tab;
        if (isset($id)) {
            $this->id = $id;
        }
        if (isset($mode)) {
            $this->mode = $mode;
        }
        if (isset($view_mode)) {
            $this->view_mode = $view_mode;
        }
        if (isset($edit_mode)) {
            $this->edit_mode = $edit_mode;
        }
        if (isset($tab)) {
            $this->tab = $tab;
        }
    }

    /**
     * Run
     *
     * Description
     *
     * @access public
     */
    function run()
    {
        global $session;
        $out = array();
        if ($this->action == 'admin') {
            $this->admin($out);
        } else {
            $this->usual($out);
        }
        if (IsSet($this->owner->action)) {
            $out['PARENT_ACTION'] = $this->owner->action;
        }
        if (IsSet($this->owner->name)) {
            $out['PARENT_NAME'] = $this->owner->name;
        }
        $out['VIEW_MODE'] = $this->view_mode;
        $out['EDIT_MODE'] = $this->edit_mode;
        $out['MODE'] = $this->mode;
        $out['ACTION'] = $this->action;
        $out['TAB'] = $this->tab;
        $this->data = $out;
        $p = new parser(DIR_TEMPLATES . $this->name . "/" . $this->name . ".html", $this->data, $this);
        $this->result = $p->result;
    }

    /**
     * BackEnd
     *
     * Module backend
     *
     * @access public
     */
    function admin(&$out)
    {
        if (isset($this->data_source) && !$_GET['data_source'] && !$_POST['data_source']) {
            $out['SET_DATASOURCE'] = 1;
        }
        if ($this->data_source == 'xiaomibtthermometer_devices' || $this->data_source == '') {
            if ($this->view_mode == '' || $this->view_mode == 'search_xiaomibtthermometer_devices') {
                $this->search_xiaomibtthermometer_devices($out);
            }
            if ($this->view_mode == '' || $this->view_mode == 'refresh_xiaomibtthermometer_devices') {
                $this->refresh_xiaomibtthermometer_devices($out);
            }
            if ($this->view_mode == 'edit_xiaomibtthermometer_devices') {
                $this->edit_xiaomibtthermometer_devices($out, $this->id);
            }
            if ($this->view_mode == 'delete_xiaomibtthermometer_devices') {
                $this->delete_xiaomibtthermometer_devices($this->id);
                $this->redirect("?");
            }
        }
    }

    /**
     * FrontEnd
     *
     * Module frontend
     *
     * @access public
     */
    function usual(&$out)
    {
        $this->admin($out);
    }

    /**
     * xiaomibtthermometer_devices search
     *
     * @access public
     */
    function search_xiaomibtthermometer_devices(&$out)
    {
        require(DIR_MODULES . $this->name . '/xiaomibtthermometer_devices_search.inc.php');
    }

    function refresh_xiaomibtthermometer_devices(&$out)
    {
        setGlobal('xiaomibtthermometer_refresh_devices', '1');
    }

    /**
     * xiaomibtthermometer_devices edit/add
     *
     * @access public
     */
    function edit_xiaomibtthermometer_devices(&$out, $id)
    {
        require(DIR_MODULES . $this->name . '/xiaomibtthermometer_devices_edit.inc.php');
    }

    /**
     * xiaomibtthermometer_devices delete record
     *
     * @access public
     */
    function delete_xiaomibtthermometer_devices($id)
    {
        $rec = SQLSelectOne("SELECT * FROM xiaomibtthermometer_devices WHERE ID='$id'");
        // some action for related tables
        SQLExec("DELETE FROM xiaomibtthermometer_devices WHERE ID='" . $rec['ID'] . "'");
    }

    /**
     * Install
     *
     * Module installation routine
     *
     * @access private
     */
    function install($data = '')
    {
        parent::install();
        setGlobal('xiaomibtthermometer_refresh_devices', '1');
    }

    /**
     * Uninstall
     *
     * Module uninstall routine
     *
     * @access public
     */
    function uninstall()
    {
        SQLExec('DROP TABLE IF EXISTS xiaomibtthermometer_devices');
        parent::uninstall();
    }

    /**
     * dbInstall
     *
     * Database installation routine
     *
     * @access private
     */
    function dbInstall($data)
    {
        /*
        xiaomibtthermometer_devices -
        */
        $data = <<<EOD
 xiaomibtthermometer_devices: ID int(10) unsigned NOT NULL auto_increment
 xiaomibtthermometer_devices: TITLE varchar(100) NOT NULL DEFAULT ''
 xiaomibtthermometer_devices: MAC varchar(255) NOT NULL DEFAULT ''
 xiaomibtthermometer_devices: TEMPERATURE varchar(255) NOT NULL DEFAULT ''
 xiaomibtthermometer_devices: HUMIDITY varchar(255) NOT NULL DEFAULT ''
 xiaomibtthermometer_devices: LINKED_OBJECT_TEMPERATURE varchar(100) NOT NULL DEFAULT ''
 xiaomibtthermometer_devices: LINKED_PROPERTY_TEMPERATURE varchar(100) NOT NULL DEFAULT ''
 xiaomibtthermometer_devices: LINKED_OBJECT_HUMIDITY varchar(100) NOT NULL DEFAULT ''
 xiaomibtthermometer_devices: LINKED_PROPERTY_HUMIDITY varchar(100) NOT NULL DEFAULT ''
 xiaomibtthermometer_devices: UPDATED datetime
EOD;
        parent::dbInstall($data);
    }
// --------------------------------------------------------------------
}
/*
*
* TW9kdWxlIGNyZWF0ZWQgQXVnIDEyLCAyMDIwIHVzaW5nIFNlcmdlIEouIHdpemFyZCAoQWN0aXZlVW5pdCBJbmMgd3d3LmFjdGl2ZXVuaXQuY29tKQ==
*
*/
