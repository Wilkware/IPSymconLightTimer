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
    const TIMING_WEEKLYON  = 'WeeklySchedulOn';
    const TIMING_WEEKLYOFF = 'WeeklySchedulOff';
    const TIMING_SEPERATOR = 'None';

    // Schedule constant
    const SCHEDULE_OFF = 1;
    const SCHEDULE_ON = 2;
    const SCHEDULE_NAME = 'Zeitplan';
    const SCHEDULE_IDENT = 'weekly_schedule';
    const SCHEDULE_SWITCH = [
        self::SCHEDULE_OFF => ['Off', 0xFF0000, "LTM_Schedule(\$_IPS['TARGET'], \$_IPS['ACTION']);"],
        self::SCHEDULE_ON  => ['On', 0x00FF00, "LTM_Schedule(\$_IPS['TARGET'], \$_IPS['ACTION']);"],
    ];

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
        $this->RegisterPropertyInteger('TimingSchedule', 0);
        $this->RegisterPropertyString('TimingStart', 'Sunrise');
        $this->RegisterPropertyString('TimingEnd', 'Sunset');
        // Device
        $this->RegisterPropertyInteger('DeviceVariable', 0);
        $this->RegisterPropertyInteger('DeviceScript', 0);
        // Settings
        $this->RegisterPropertyBoolean('SettingsBool', false);
        $this->RegisterPropertyBoolean('SettingsSwitch', false);
        // Attribute
        $this->RegisterAttributeInteger('ConditionalStart', 0);
        $this->RegisterAttributeInteger('ConditionalEnd', 0);
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
        //$this->SendDebug(__FUNCTION__, $form);
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
        // Check Seperators
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
        }
        elseif ($start == self::TIMING_WEEKLYON) {
            $cs =  0;
        }
        else {
            $cs = $this->GetLocationID($start);
        }
        // Get End ID
        if ($end == self::TIMING_OFF) {
            $ce = -1;
        }
        elseif ($end == self::TIMING_WEEKLYOFF) {
            $ce = 0;
        }
        else {
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
        // Aditionally Switch
        $switch = $this->ReadPropertyBoolean('SettingsSwitch');
        $this->MaintainVariable('switch_proxy', $this->Translate('Switch'), VARIABLETYPE_BOOLEAN, '~Switch', 0, $switch);
        if ($switch) {
            $this->EnableAction('switch_proxy');
        }
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
                throw new Exception('Invalid Ident');
        }
        return true;
    }

    /**
     * This function will be available automatically after the module is imported with the module control.
     * Using the custom prefix this function will be callable from PHP and JSON-RPC through:
     *
     * TLA_CreateSchedule($id);
     *
     */
    public function CreateSchedule()
    {
        $eid = $this->CreateWeeklySchedule($this->InstanceID, self::SCHEDULE_NAME, self::SCHEDULE_IDENT, self::SCHEDULE_SWITCH, -1);
        if ($eid !== false) {
            $this->UpdateFormField('TimingSchedule', 'value', $eid);
        }
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

        // Start is OFF
        if(($cs == -1) && ($value == self::SCHEDULE_ON)) {
            $this->SendDebug(__FUNCTION__, 'Start Trigger is off');
            return;
        }
        // End is OFF
        if(($ce == -1) && ($value == self::SCHEDULE_OFF)) {
            $this->SendDebug(__FUNCTION__, 'End Trigger is off');
            return;
        }

        // Start is Timer
        if(($cs == 0) && ($value == self::SCHEDULE_ON)) {
            $this->SendDebug(__FUNCTION__, 'Start timer switch');
            if ($this->SwitchDevice(true)) {
                $this->SwitchState(true);
            }
        }
        // End is Timer
        if(($ce == 0) && ($value == self::SCHEDULE_OFF)) {
            $this->SendDebug(__FUNCTION__, 'End timer switch');
            if ($this->SwitchDevice(false)) {
                $this->SwitchState(false);
            }
        }

        // Start conditional switching
        if($cs == $value) {
            $this->SendDebug(__FUNCTION__, 'Start conditional-Switch: ' . $value);
            if ($this->SwitchDevice(true)) {
                $this->SwitchState(true);
            }
        }

        // End conditional switching
        if($ce == $value) {
            $this->SendDebug(__FUNCTION__, 'End conditional-Switch: ' . $value);
            if ($this->SwitchDevice(false)) {
                $this->SwitchState(false);
            }
        }
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
                $this->SendDebug(__FUNCTION__, 'Gerät konnte nicht geschalten werden (UNREACH)!');
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
            if($id != false) {
                return $id;
            }
        }
        $this->SendDebug(__FUNCTION__, 'No Location Control found!');
        return 0;
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
}