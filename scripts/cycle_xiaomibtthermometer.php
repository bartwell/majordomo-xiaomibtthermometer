<?php
chdir(dirname(__FILE__) . '/../');
include_once("./config.php");
include_once("./lib/loader.php");
include_once("./lib/threads.php");
set_time_limit(0);
// connecting to database
$db = new mysql(DB_HOST, '', DB_USER, DB_PASSWORD, DB_NAME);
include_once("./load_settings.php");
include_once(DIR_MODULES . "control_modules/control_modules.class.php");
$ctl = new control_modules();
include_once(DIR_MODULES . 'xiaomibtthermometer/xiaomibtthermometer.class.php');
$xiaomibtthermometer_module = new xiaomibtthermometer();
$xiaomibtthermometer_module->getConfig();
//$tmp = SQLSelectOne("SELECT ID FROM xiaomibtthermometer_devices LIMIT 1");
//var_dump($tmp);
$bts_cmd_le = 'sudo timeout -s INT 10s hcitool lescan | grep ":"';
$bts_reset = 'sudo hciconfig hci0 down; sudo hciconfig hci0 up';

//if (!$tmp['ID'])
//   exit; // no devices added -- no need to run this cycle
echo date("H:i:s") . " running " . basename(__FILE__) . PHP_EOL;
$latest_check = 0;
$checkEvery = 60; // poll every 5 seconds
while (1) {
    setGlobal((str_replace('.php', '', basename(__FILE__))) . 'Run', time(), 1);
    if ((time() - $latest_check) > $checkEvery) {
        $latest_check = time();
        echo date('Y-m-d H:i:s') . ' Polling data...';

        // Start
        if (getGlobal('xiaomibtthermometer_refresh_devices') == '1') {
            setGlobal('xiaomibtthermometer_refresh_devices', '0');
            //reset bluetooth
            if (time() - $reset_time > $reset_perion) {
                echo date('Y/m/d H:i:s') . ' Reset bluetooth' . PHP_EOL;
                exec($bts_reset);
                $reset_time = time();
            }

            $bt_scan_arr = array();
            $str = exec($bts_cmd, $bt_scan_arr);
            $str = exec($bts_cmd_le, $bt_scan_arr);
            $lines = array();
            $btScanArrayLength = count($bt_scan_arr);

            for ($i = 0; $i < $btScanArrayLength; $i++) {
                if (!$bt_scan_arr[$i]) {
                    continue;
                }
                //echo $bt_scan_arr[$i].PHP_EOL;
                $bt_scan = trim($bt_scan_arr[$i]);
                //echo $bt_scan.PHP_EOL;
                $btaddr = substr($bt_scan, 0, 17);
                $btname = trim(substr($bt_scan, 17));
                $lines[] = $i . "\t" . $btname . "\t" . $btaddr;
            }
            $data = implode("\n", $lines);
            $last_scan = time();

            if ($data) {
                $data = str_replace(chr(0), '', $data);
                $data = str_replace("\r", '', $data);
                $lines = explode("\n", $data);
                $total = count($lines);

                for ($i = 0; $i < $total; $i++) {
                    $fields = explode("\t", $lines[$i]);
                    $title = trim($fields[1]);
                    $mac = trim($fields[2]);
                    $user = array();

                    if ($mac != '' && $title == 'LYWSD03MMC') {
                        if (!$bt_devices[$mac]) {
                            //&& !$first_run
                            //new device found
                            echo date('Y/m/d H:i:s') . ' Device found: ' . $mac . PHP_EOL;

                            $sqlQuery = "SELECT * 
                               FROM xiaomibtthermometer_devices 
                               WHERE MAC LIKE '" . $mac . "'";

                            $rec = SQLSelectOne($sqlQuery);

                            //if (!$rec['ID'] && $title != '(unknown)')
                            if (!$rec['ID']) {
                                $rec['MAC'] = strtolower($mac);

                                $rec['TITLE'] = 'Термометр ' . $rec['MAC'];

                                $new = 1;

                                $rec['ID'] = SQLInsert('xiaomibtthermometer_devices', $rec);
                            } else {
                                $new = 0;

                                if ($rec['USER_ID']) {
                                    $sqlQuery = "SELECT * 
                                     FROM users 
                                     WHERE ID = '" . $rec['USER_ID'] . "'";

                                    $user = SQLSelectOne($sqlQuery);
                                }
                                SQLUpdate('xiaomibtthermometer_devices', $rec);
                            }
                        } /*else {
                    $sqlQuery = "SELECT * 
                               FROM xiaomibtthermometer_devices 
                               WHERE MAC = '" . $mac . "'";

                    $rec = SQLSelectOne($sqlQuery);

                    if ($title != '' && $title != '(unknown)') {
                        $rec['TITLE']
                            = 'Термометр ' . $rec['MAC'];
                    }

                    if ($rec['ID']) {
                        SQLUpdate('xiaomibtthermometer_devices', $rec);
                    }
                }*/
                        $bt_devices[$mac] = $last_scan;
                    }
                }
            }
        }

        $res = SQLSelect("SELECT * FROM xiaomibtthermometer_devices");
        foreach ($res as $rec) {
            $str = exec('timeout 30 gatttool -b ' . $rec['MAC'] . ' --char-write-req --handle=\'0x0038\' --value=\'0100\' --listen', $data_arr);
            $data = implode("\n", $data_arr);
            preg_match('/0x0036 value: ([0-9a-f]{2}) ([0-9a-f]{2}) ([0-9a-f]{2}) ([0-9a-f]{2}) ([0-9a-f]{2})/m', $data, $match);
            if (sizeof($match) > 2) {
                $rec['HUMIDITY'] = hexdec($match[3]);
                $rec['TEMPERATURE'] = hexdec($match[2] . $match[1]) / 100;
                $rec['UPDATED'] = date('Y-m-d H:i:s');
                SQLUpdate('xiaomibtthermometer_devices', $rec);
                if ($rec['LINKED_OBJECT_TEMPERATURE'] && $rec['LINKED_PROPERTY_TEMPERATURE']) {
                    setGlobal($rec['LINKED_OBJECT_TEMPERATURE'] . '.' . $rec['LINKED_PROPERTY_TEMPERATURE'], $rec['TEMPERATURE']);
                }
                if ($rec['LINKED_OBJECT_HUMIDITY'] && $rec['LINKED_PROPERTY_HUMIDITY']) {
                    setGlobal($rec['LINKED_OBJECT_HUMIDITY'] . '.' . $rec['LINKED_PROPERTY_HUMIDITY'], $rec['HUMIDITY']);
                }
            }
        }
    }
    // End

    if (file_exists('./reboot') || IsSet($_GET['onetime'])) {
        $db->Disconnect();
        exit;
    }
    sleep(1);
}
DebMes("Unexpected close of cycle: " . basename(__FILE__));
