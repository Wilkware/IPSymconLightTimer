<?php

declare(strict_types=1);

// Generell funktions
require_once __DIR__ . '/../libs/_traits.php';

// CLASS Light Timer
class LightTimer extends IPSModule
{
    use ProfileHelper;
    use EventHelper;
    use DebugHelper;

    // Timing constant
    const TIMING_ON = 'On';
    const TIMING_OFF = 'Off';
    const TIMING_START = 'TimingStart';
    const TIMING_END = 'TimingEnd';
    const TIMING_WEEKLYON = 'WeeklySchedulOn';
    const TIMING_WEEKLYOFF = 'WeeklySchedulOff';
    const TIMING_SEPERATOR = 'None';

    // Schedule constant
    const SCHEDULE_ON = 1;
    const SCHEDULE_OFF = 2;
    const SCHEDULE_WEEKLY = ['Check', 'Timer', 'Delete', 'Copy'];
    const SCHEDULE_DAYS = ['Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa', 'So'];

    // Location Control
    const LOCATION_GUID = '{45E97A63-F870-408A-B259-2933F7EABF74}';

    /**
     * Create.
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // Timming
        $this->RegisterPropertyString('TimingStart', 'Sunrise');
        $this->RegisterPropertyString('TimingEnd', 'Sunset');
        // Device
        $this->RegisterPropertyInteger('DeviceVariable', 0);
        $this->RegisterPropertyInteger('DeviceScript', 0);
        // Settings
        $this->RegisterPropertyBoolean('SettingsBool', false);
        $this->RegisterPropertyBoolean('SettingsTime', false);
        $this->RegisterPropertyBoolean('SettingsSwitch', false);
        // Schedule
        foreach (self::SCHEDULE_DAYS as $day) {
            $this->RegisterPropertyBoolean(self::TIMING_START . 'Check' . $day, true);
            $this->RegisterPropertyBoolean(self::TIMING_END . 'Check' . $day, true);
            $this->RegisterPropertyString(self::TIMING_START . 'Time' . $day, '{"hour":6,"minute":0,"second":0}');
            $this->RegisterPropertyString(self::TIMING_END . 'Time' . $day, '{"hour":18,"minute":0,"second":0}');
        }
        // Attribute
        $this->RegisterAttributeInteger('ConditionalStart', 0);
        $this->RegisterAttributeInteger('ConditionalEnd', 0);
        $this->RegisterAttributeInteger('ConditionalTime', 0);
        // Timer
        $this->RegisterTimer('ScheduleTimerOn', 0, 'LTM_Schedule(' . $this->InstanceID . ',' . self::SCHEDULE_ON . ');');
        $this->RegisterTimer('ScheduleTimerOff', 0, 'LTM_Schedule(' . $this->InstanceID . ',' . self::SCHEDULE_OFF . ');');
    }

    /**
     * Destroy.
     */
    public function Destroy()
    {
        parent::Destroy();
    }

    /**
     * Configuration Form.
     *
     * @return JSON configuration string.
     */
    public function GetConfigurationForm()
    {
        // Get Form
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);
        // read setup
        $start = $this->ReadPropertyString('TimingStart');
        $end = $this->ReadPropertyString('TimingEnd');
        // activate/deactivate times
        for ($d = 1; $d <= 7; $d++) {
            for ($i = 0; $i <= 8; $i++) {
                if ($form['elements'][2]['items'][$d]['items'][$i]['type'] != 'Label') {
                    if ($this->StartsWith($form['elements'][2]['items'][$d]['items'][$i]['name'], self::TIMING_START)) {
                        $form['elements'][2]['items'][$d]['items'][$i]['enabled'] = ($start == self::TIMING_WEEKLYON);
                    }
                    if ($this->StartsWith($form['elements'][2]['items'][$d]['items'][$i]['name'], self::TIMING_END)) {
                        $form['elements'][2]['items'][$d]['items'][$i]['enabled'] = ($end == self::TIMING_WEEKLYOFF);
                    }
                }
            }
        }
        // return form
        return json_encode($form);
    }

    /**
     * Apply Configuration Changes.
     */
    public function ApplyChanges()
    {
        if ($this->ReadPropertyInteger('DeviceVariable') > 0) {
            $this->UnregisterMessage($this->ReadPropertyInteger('DeviceVariable'), VM_UPDATE);
        }
        if ($this->ReadAttributeInteger('ConditionalStart') > 0) {
            $this->UnregisterMessage($this->ReadAttributeInteger('ConditionalStart'), VM_UPDATE);
        }
        if ($this->ReadAttributeInteger('ConditionalEnd') > 0) {
            $this->UnregisterMessage($this->ReadAttributeInteger('ConditionalEnd'), VM_UPDATE);
        }
        //Never delete this line!
        parent::ApplyChanges();
        //Create our trigger
        if (IPS_VariableExists($this->ReadPropertyInteger('DeviceVariable'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('DeviceVariable'), VM_UPDATE);
        }
        // Safty Check Seperators
        $start = $this->ReadPropertyString('TimingStart');
        if ($start == self::TIMING_SEPERATOR) {
            $this->SetStatus(201);
            return;
        }
        $end = $this->ReadPropertyString('TimingEnd');
        if ($end == self::TIMING_SEPERATOR) {
            $this->SetStatus(202);
            return;
        }
        // Check Start <> End
        if (($start != self::TIMING_OFF) && ($end != self::TIMING_OFF)) {
            if ($start == $end) {
                $this->SetStatus(203);
                return;
            }
        }
        // Get Start ID
        if ($start == self::TIMING_OFF) {
            $cs = -1;
        } elseif ($start == self::TIMING_WEEKLYON) {
            $cs = 0;
        } else {
            $cs = $this->GetLocationID($start);
        }
        // Get End ID
        if ($end == self::TIMING_OFF) {
            $ce = -1;
        } elseif ($end == self::TIMING_WEEKLYOFF) {
            $ce = 0;
        } else {
            $ce = $this->GetLocationID($end);
        }
        // Write
        $this->WriteAttributeInteger('ConditionalStart', $cs);
        $this->SendDebug(__FUNCTION__, $start . ' = ' . $cs);
        $this->WriteAttributeInteger('ConditionalEnd', $ce);
        $this->SendDebug(__FUNCTION__, $end . ' = ' . $ce);
        // Register Start
        if ($cs > 0) {
            $this->RegisterMessage($cs, VM_UPDATE);
        }
        // Register End
        if ($ce > 0) {
            $this->RegisterMessage($ce, VM_UPDATE);
        }
        // Off before On check
        $ct = 0;
        if ($this->ReadPropertyBoolean('SettingsTime')) {
            $ct = 1;
        }
        $this->WriteAttributeInteger('ConditionalTime', $ct);
        $this->SendDebug(__FUNCTION__, 'ConditionalTime = ' . $ct);
        // Aditionally Switch
        $switch = $this->ReadPropertyBoolean('SettingsSwitch');
        $this->MaintainVariable('switch_proxy', $this->Translate('Switch'), VARIABLETYPE_BOOLEAN, '~Switch', 0, $switch);
        if ($switch) {
            $this->EnableAction('switch_proxy');
        }
        // Set next Timer
        $this->CalculateTimer();
        // All okay
        $this->SetStatus(102);
    }

    /**
     * Interne Funktion des SDK.
     * data[0] = neuer Wert
     * data[1] = wurde Wert geändert?
     * data[2] = alter Wert
     * data[3] = Timestamp.
     */
    public function MessageSink($timeStamp, $senderID, $message, $data)
    {
        // $this->SendDebug(__FUNCTION__, 'SenderId: '. $senderID . 'Data: ' . print_r($data, true), 0);
        switch ($message) {
            case VM_UPDATE:
                // Safety Check
                $varID = $this->ReadPropertyInteger('DeviceVariable');
                $startID = $this->ReadAttributeInteger('ConditionalStart');
                $endID = $this->ReadAttributeInteger('ConditionalEnd');
                if (($senderID != $varID) || ($senderID != $startID) || ($senderID != $endID)) {
                    if (($senderID == $varID) && ($data[1] == true)) {
                        $this->SendDebug(__FUNCTION__, $senderID . ': device variable changed');
                        $this->SwitchState($data[0]);
                    } elseif ($data[1] == true) {
                        $this->SendDebug(__FUNCTION__, $senderID . ': conditional start changed');
                        $this->Schedule($senderID);
                    }
                } else {
                    $this->SendDebug(__FUNCTION__, $senderID . ' unknown!');
                }
            break;
        }
    }

    /**
     * RequestAction.
     *
     *  @param string $ident Ident.
     *  @param string $value Value.
     */
    public function RequestAction($ident, $value)
    {
        // Debug output
        $this->SendDebug(__FUNCTION__, $ident . ' => ' . $value);
        switch ($ident) {
            case 'switch_proxy':
                if ($this->SwitchDevice($value)) {
                    $this->SetValueBoolean($ident, $value);
                }
                break;
            default:
                eval('$this->' . $ident . '(\'' . $value . '\');');
        }
        return true;
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:.
     *
     * @param integer $vaue Action value (OFF=1, ON=2)
     */
    public function Schedule(int $value)
    {
        $this->SendDebug(__FUNCTION__, 'Value: ' . $value);
        // Mode?
        $cs = $this->ReadAttributeInteger('ConditionalStart');
        $ce = $this->ReadAttributeInteger('ConditionalEnd');
        $ct = $this->ReadAttributeInteger('ConditionalTime');
        $this->SendDebug(__FUNCTION__, 'Conditional is :' . $cs . ', ' . $ce . ', ' . $ct);

        // Start is OFF
        if (($cs == -1) && ($value == self::SCHEDULE_ON)) {
            $this->SendDebug(__FUNCTION__, 'Start Trigger is off');
            return;
        }
        // End is OFF
        if (($ce == -1) && ($value == self::SCHEDULE_OFF)) {
            $this->SendDebug(__FUNCTION__, 'End Trigger is off');
            return;
        }

        // Start is Timer
        if (($cs == 0) && ($value == self::SCHEDULE_ON)) {
            $this->SendDebug(__FUNCTION__, 'Start timer switch');
            // OFF before ON?
            if ($ct == 2) {
                $this->SendDebug(__FUNCTION__, 'OFF before ON!!!');
                $this->WriteAttributeInteger('ConditionalTime', 1);
            }
            else {
                // Everything okay - switch ON
                if ($this->SwitchDevice(true)) {
                    $this->SwitchState(true);
                }
            }
        }
        // End is Timer
        if (($ce == 0) && ($value == self::SCHEDULE_OFF)) {
            $this->SendDebug(__FUNCTION__, 'End timer switch');
            if ($this->SwitchDevice(false)) {
                $this->SwitchState(false);
            }
            // Check is ON behind OFF
            if(($ct == 1) && ($cs > 0)) {
                $mid = mktime(24,0,0);
                $int = GetValue($cs);
                $this->SendDebug(__FUNCTION__, 'Check is ON behind OFF: ' . $mid . ' , ' . $int);
                if($int < $mid) {
                    $this->SendDebug(__FUNCTION__, 'ON is behind OFF: ' . $int);
                    $this->WriteAttributeInteger('ConditionalTime', 2);
                }
            }
        }

        // Start conditional switching
        if ($cs == $value) {
            $this->SendDebug(__FUNCTION__, 'Start conditional-Switch: ' . $value);
            // OFF before ON?
            if ($ct == 2) {
                $this->SendDebug(__FUNCTION__, 'OFF before ON!!!');
                $this->WriteAttributeInteger('ConditionalTime', 1);
            }
            else {
                // Everything okay - switch ON
                if ($this->SwitchDevice(true)) {
                    $this->SwitchState(true);
                }
            }
        }

        // End conditional switching
        if ($ce == $value) {
            $this->SendDebug(__FUNCTION__, 'End conditional-Switch: ' . $value);
            if ($this->SwitchDevice(false)) {
                $this->SwitchState(false);
            }
            // Check is ON behind OFF
            if(($ct == 1) && ($cs == 0)) {
                $mid = mktime(24,0,0);
                $int = time() + ($this->GetTimerInterval('ScheduleTimerOn') / 1000);
                $this->SendDebug(__FUNCTION__, 'Check is ON behind OFF: ' . $mid . ' , ' . $int);
                if($int < $mid) {
                    $this->SendDebug(__FUNCTION__, 'ON is behind OFF: ' . $int);
                    $this->WriteAttributeInteger('ConditionalTime', 2);
                }
            }
        }
        $this->CalculateTimer();
    }

    /**
     * SwitchState
     *
     *  @param boolean $state ON/OFF.
     */
    private function SwitchState($state)
    {
        $this->SendDebug(__FUNCTION__, 'New Value: ' . var_export($state, true));
        // Check shadow Variable
        if ($this->ReadPropertyBoolean('SettingsSwitch')) {
            $this->SetValueBoolean('switch_proxy', boolval($state));
        }
    }

    /**
     * Switch Variable/Script
     *
     *  @param boolean $state ON/OFF.
     */
    private function SwitchDevice($state)
    {
        $ret = true;
        $this->SendDebug(__FUNCTION__, 'New State: ' . var_export($state, true));
        // Check Script
        $ds = $this->ReadPropertyInteger('DeviceScript');
        if ($ds != 0) {
            if (IPS_ScriptExists($ds)) {
                $rs = IPS_RunScriptEx($ds, ['State' => $state]);
                $this->SendDebug(__FUNCTION__, 'RundScript: ' . $rs);
            } else {
                $this->SendDebug(__FUNCTION__, 'Script #' . $ds . ' doesnt exist!');
            }
        }
        // Check Variable
        $dv = $this->ReadPropertyInteger('DeviceVariable');
        if ($dv != 0) {
            $bv = $this->ReadPropertyBoolean('SettingsBool');
            if ($bv) {
                $ret = @SetValueBoolean($dv, boolval($state));
            } else {
                $ret = @RequestAction($dv, boolval($state));
            }
            if ($ret === false) {
                $this->SendDebug(__FUNCTION__, 'Device could not be switched (UNREACH)!');
                return false;
            }
        }
        return $ret;
    }

    /**
     * Returns the status variablen ID of the Location Control by given ident.
     *
     * @param string   $ident Ident of the Location Control Variable
     * @return integer Variablen ID
     */
    private function GetLocationID($ident)
    {
        $LCs = IPS_GetInstanceListByModuleID(self::LOCATION_GUID);
        if (isset($LCs[0])) {
            $id = @IPS_GetObjectIDByIdent($ident, $LCs[0]);
            if ($id != false) {
                return $id;
            }
        }
        $this->SendDebug(__FUNCTION__, 'No Location Control found!');
        return 0;
    }

    /**
     * Activate or deactivate weekly schedule elements.
     *
     * @param string   $ident Ident of the trigger
     * @return bool    True for activate
     */
    private function WeeklySchedule($ident, $active)
    {
        $this->SendDebug(__FUNCTION__, $ident . ': ' . var_export($active, true));
        foreach (self::SCHEDULE_DAYS as $day) {
            $this->UpdateFormField($ident . 'Check' . $day, 'enabled', $active);
            $this->UpdateFormField($ident . 'Time' . $day, 'enabled', $active);
            $this->UpdateFormField($ident . 'Delete' . $day, 'enabled', $active);
            if ($day != 'So') {
                $this->UpdateFormField($ident . 'Copy' . $day, 'enabled', $active);
            }
        }
    }

    /**
     * Calculate the next Timer
     *
     */
    private function CalculateTimer()
    {
        // read setup
        $start = $this->ReadPropertyString('TimingStart');
        $end = $this->ReadPropertyString('TimingEnd');
        $this->SendDebug(__FUNCTION__, 'Start: ' . $start . ' End: ' . $end);
        // Disable Timer
        $this->SetTimerInterval('ScheduleTimerOn', 0);
        $this->SetTimerInterval('ScheduleTimerOff', 0);
        // New Timer
        $day = date('N', time()) - 1;
        $now = time();
        $add = 0;
        if ($start == self::TIMING_WEEKLYON) {
            $active = false;
            for ($i = $day; $i <= 6; $i++) {
                $active = $this->ReadPropertyBoolean('TimingStartCheck' . self::SCHEDULE_DAYS[$i]);
                if ($active) {
                    $time = $this->ReadPropertyString('TimingStartTime' . self::SCHEDULE_DAYS[$i]);
                    $time = json_decode($time, true);
                    $next = mktime($time['hour'], $time['minute'], $time['second']) + ($add * 86400);
                    if ($next > $now) {
                        $diff = $next - $now;
                        $interval = $diff * 1000;
                        $this->SetTimerInterval('ScheduleTimerOn', $interval);
                        break;
                    } else {
                        $active = false;
                    }
                }
                $add++;
            }
            // if no day behind active, thern look before
            if (!$active) {
                for ($i = 0; $i < $day; $i++) {
                    $active = $this->ReadPropertyBoolean('TimingStartCheck' . self::SCHEDULE_DAYS[$i]);
                    if ($active) {
                        $time = $this->ReadPropertyString('TimingStartTime' . self::SCHEDULE_DAYS[$i]);
                        $time = json_decode($time, true);
                        $next = mktime($time['hour'], $time['minute'], $time['second']) + ($add * 86400);
                        if ($next > $now) {
                            $diff = $next - $now;
                            $interval = $diff * 1000;
                            $this->SetTimerInterval('ScheduleTimerOn', $interval);
                            break;
                        } else {
                            $active = false;
                        }
                    }
                    $add++;
                }
            }
        }
        $add = 0;
        if ($end == self::TIMING_WEEKLYOFF) {
            $active = false;
            for ($i = $day; $i <= 6; $i++) {
                $active = $this->ReadPropertyBoolean('TimingEndCheck' . self::SCHEDULE_DAYS[$i]);
                if ($active) {
                    $time = $this->ReadPropertyString('TimingEndTime' . self::SCHEDULE_DAYS[$i]);
                    $time = json_decode($time, true);
                    $next = mktime($time['hour'], $time['minute'], $time['second']) + ($add * 86400);
                    if ($next > $now) {
                        $diff = $next - $now;
                        $interval = $diff * 1000;
                        $this->SetTimerInterval('ScheduleTimerOff', $interval);
                        break;
                    } else {
                        $active = false;
                    }
                }
                $add++;
            }
            // if no day behind active, thern look before
            if (!$active) {
                for ($i = 0; $i < $day; $i++) {
                    $active = $this->ReadPropertyBoolean('TimingEndCheck' . self::SCHEDULE_DAYS[$i]);
                    if ($active) {
                        $time = $this->ReadPropertyString('TimingEndTime' . self::SCHEDULE_DAYS[$i]);
                        $time = json_decode($time, true);
                        $next = mktime($time['hour'], $time['minute'], $time['second']) + ($add * 86400);
                        if ($next > $now) {
                            $diff = $next - $now;
                            $interval = $diff * 1000;
                            $this->SetTimerInterval('ScheduleTimerOff', $interval);
                            break;
                        } else {
                            $active = false;
                        }
                    }
                    $add++;
                }
            }
        }
    }

    /**
     * User has select an new start trigger.
     *
     * @param string $id select ID.
     */
    private function OnTimingStart($id)
    {
        $this->SendDebug(__FUNCTION__, 'Ident: ' . $id);
        if ($id == self::TIMING_SEPERATOR) {
            $this->UpdateFormField(self::TIMING_START, 'value', self::TIMING_OFF);
        }
        $this->WeeklySchedule(self::TIMING_START, ($id == self::TIMING_WEEKLYON));
    }

    /**
     * User has select an new end trigger.
     *
     * @param string $id select ID.
     */
    private function OnTimingEnd($id)
    {
        $this->SendDebug(__FUNCTION__, 'Ident: ' . $id);
        if ($id == self::TIMING_SEPERATOR) {
            $this->UpdateFormField(self::TIMING_END, 'value', self::TIMING_OFF);
        }
        $this->WeeklySchedule(self::TIMING_END, ($id == self::TIMING_WEEKLYOFF));
    }

    /**
     * User has clickt on delete button.
     *
     * @param string $id button ID.
     */
    private function OnStartDelete($id)
    {
        $this->SendDebug(__FUNCTION__, 'Ident: ' . $id);
        $this->UpdateFormField($id, 'value', '{"hour":6,"minute":0,"second":0}');
    }

    /**
     * User has clickt on delete button.
     *
     * @param string $id button ID.
     */
    private function OnEndDelete($id)
    {
        $this->SendDebug(__FUNCTION__, 'Ident: ' . $id);
        $this->UpdateFormField($id, 'value', '{"hour":18,"minute":0,"second":0}');
    }

    /**
     * User has clickt on copy button.
     *
     * @param string $value copy value.
     */
    private function OnStartCopy($value)
    {
        $this->SendDebug(__FUNCTION__, 'Value: ' . $value);
        $day = substr($value, 0, 2);
        $time = substr($value, 2);
        $this->SendDebug(__FUNCTION__, 'Day: ' . $day . ' Time: ' . $time);
        $this->UpdateFormField(self::TIMING_START . 'Time' . $day, 'value', $time);
    }

    /**
     * User has clickt on copy button.
     *
     * @param string $value copy value..
     */
    private function OnEndCopy($value)
    {
        $this->SendDebug(__FUNCTION__, 'Value: ' . $value);
        $day = substr($value, 0, 2);
        $time = substr($value, 2);
        $this->SendDebug(__FUNCTION__, 'Day: ' . $day . ' Time: ' . $time);
        $this->UpdateFormField(self::TIMING_END . 'Time' . $day, 'value', $time);
    }

    /**
     * Update a boolean value.
     *
     * @param string $ident Ident of the boolean variable
     * @param bool   $value Value of the boolean variable
     */
    private function SetValueBoolean(string $ident, bool $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueBoolean($id, $value);
    }

    /**
     * Update a string value.
     *
     * @param string $ident Ident of the string variable
     * @param string $value Value of the string variable
     */
    private function SetValueString(string $ident, string $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueString($id, $value);
    }

    /**
     * Update a integer value.
     *
     * @param string $ident Ident of the integer variable
     * @param int    $value Value of the integer variable
     */
    private function SetValueInteger(string $ident, int $value)
    {
        $id = $this->GetIDForIdent($ident);
        SetValueInteger($id, $value);
    }

    /**
     * Checks if a string starts with a given substring
     *
     * @param string $haystack The string to search in.
     * @param int    $needle The substring to search for in the haystack.
     */
    private function StartsWith(string $haystack, string $needle)
    {
        return (string) $needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}