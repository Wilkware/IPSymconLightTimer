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

    // Schedule constant
    const SCHEDULE_NAME = 'Zeitplan';
    const SCHEDULE_IDENT = 'weekly_schedule';
    const SCHEDULE_SWITCH = [
        1 => ['Off', 0xFF0000, "LTM_Schedule(\$_IPS['TARGET'], \$_IPS['ACTION']);"],
        2 => ['On', 0x00FF00, "LTM_Schedule(\$_IPS['TARGET'], \$_IPS['ACTION']);"],
    ];

    // Location Control
    const LOCATION_GUID = '{45E97A63-F870-408A-B259-2933F7EABF74}';

    // Profil MODE
    private $assoMODE = [
        [0, 'Off', '', 0xFF0000],
        [1, 'Morning (semi-automatic)', '', 0xFFFF00],
        [2, 'Evening (semi-automatic)', '', 0xFFFF00],
        [3, 'Early & Evening (fully-automatic)', '', 0x00FF00],
    ];

    // Profil START
    private $assoSTART = [
        [0, 'Sunrise', '', 0x800080],
        [1, 'Civil twilight', '', 0x800080],
        [2, 'Nautic twilight', '', 0x800080],
        [3, 'Astronomic twilight', '', 0x800080],
    ];

    // Profil END
    private $assoEND = [
        [0, 'Sunset', '', 0x800080],
        [1, 'Civil twilight', '', 0x800080],
        [2, 'Nautic twilight', '', 0x800080],
        [3, 'Astronomic twilight', '', 0x800080],
    ];

    /**
     * Create.
     */
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        // Variablen Profile einrichten
        //$this->RegisterProfile(VARIABLETYPE_INTEGER, 'LTM.Mode', 'Gear', '', '', 0, 0, 0, 0, $this->assoMODE);
        //$this->RegisterProfile(VARIABLETYPE_INTEGER, 'LTM.Start', 'Sun', '', '', 0, 0, 0, 0, $this->assoSTART);
        //$this->RegisterProfile(VARIABLETYPE_INTEGER, 'LTM.End', 'Moon', '', '', 0, 0, 0, 0, $this->assoEND);
        // Timming
        $this->RegisterPropertyInteger('TimingAutomatic', 4);
        $this->RegisterPropertyString('TimingStart', 'Sunrise');
        $this->RegisterPropertyString('TimingEnd', 'Sunset');
        $this->RegisterPropertyInteger('TimingSchedule', 0);
        // Device
        $this->RegisterPropertyInteger('DeviceVariable', 0);
        $this->RegisterPropertyInteger('DeviceScript', 0);
        // Settings
        $this->RegisterPropertyBoolean('SettingsBool', false);
        $this->RegisterPropertyBoolean('SettingsSwitch', false);
        // Attribute
        $this->RegisterAttributeInteger('ConditionalStart', 0);
        $this->RegisterAttributeInteger('ConditionalEnd', 0);
        // Variablen erzeugen
        //$this->RegisterVariableInteger('automatic_mode', $this->Translate('Automatic'), 'LTM.Mode', 0);
        //$this->RegisterVariableInteger('conditional_start', $this->Translate('Conditional start'), 'LTM.Start', 0);
        //$this->RegisterVariableInteger('conditional_end', $this->Translate('Conditional end'), 'LTM.End', 0);
        // Actions
        //$this->EnableAction('automatic_mode');
        //$this->EnableAction('conditional_start');
        //$this->EnableAction('conditional_end');
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
        if ($this->ReadPropertyInteger('DeviceVariable') != 0) {
            $this->UnregisterMessage($this->ReadPropertyInteger('DeviceVariable'), VM_UPDATE);
        }
        if ($this->ReadAttributeInteger('ConditionalStart') != 0) {
            $this->UnregisterMessage($this->ReadAttributeInteger('ConditionalStart'), VM_UPDATE);
        }
        if ($this->ReadAttributeInteger('ConditionalEnd') != 0) {
            $this->UnregisterMessage($this->ReadAttributeInteger('ConditionalEnd'), VM_UPDATE);
        }
        //Never delete this line!
        parent::ApplyChanges();
        //Create our trigger
        if (IPS_VariableExists($this->ReadPropertyInteger('DeviceVariable'))) {
            $this->RegisterMessage($this->ReadPropertyInteger('DeviceVariable'), VM_UPDATE);
        }
        // Conditional
        $am = $this->ReadPropertyInteger('TimingAutomatic');
        $cs = 0;
        $ce = 0;
        // true == (1 oder 4)
        if ($am | 5) {
            $start = $this->ReadPropertyString('TimingStart');
            $cs = $this->GetLocationID($start);
        }
        // true == (2 oder 4)
        if ($am | 6) {
            $end = $this->ReadPropertyString('TimingEnd');
            $cs = $this->GetLocationID($end);
        }
        // Write
        $this->WriteAttributeInteger('ConditionalStart', $cs);
        $this->WriteAttributeInteger('ConditionalEnd', $ce);
        // Start
        if ($cs != 0) {
            $this->RegisterMessage($cs, VM_UPDATE);
        }
        // End
        if ($ce != 0) {
            $this->RegisterMessage($ce, VM_UPDATE);
        }
        // Aditionally Switch
        $switch = $this->ReadPropertyBoolean('SettingsSwitch');
        $this->MaintainVariable('switch_proxy', $this->Translate('Switch'), VARIABLETYPE_BOOLEAN, '~Switch', 0, $switch);
        if ($switch) {
            $this->EnableAction('switch_proxy');
        }
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
                if (($senderID != $varID) || ($senderID != $startID) || ($senderID != $endID) ) {
                    if (($senderID == $varID) && ($data[1] == true)) {
                        $this->SwitchState($data[0]);
                    }
                    elseif (($senderID == $startID) && ($data[1] == true)) {
                        $this->SendDebug(__FUNCTION__, $senderID . ': conditional start changed');
                        $this->Schedule(12);
                    }
                    elseif (($senderID == $endID) && ($data[1] == true)) {
                        $this->SendDebug(__FUNCTION__, $senderID . ': conditional end changed');
                        $this->Schedule(11);
                    }
                }
                else {
                    $this->SendDebug(__FUNCTION__, $senderID . ' unknown!');
                }
            break;
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
                $this->SendDebug(__FUNCTION__, 'RundScript: ' .$rs);
            } else {
                $this->SendDebug(__FUNCTION__, 'Script #' . $ds . ' doesnt exist!');
            }
        }
        // Check Variable
        $dv = $this->ReadPropertyInteger('DeviceVariable');
        if ($dv != 0) {
            $bv =  $this->ReadPropertyBoolean('SettingsBool');
            if ($bv) {
                $ret = @SetValueBoolean($dv, boolval($state)); 
            }
            else {
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
                if($this->SwitchDevice($value)) {
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
        if($eid !== false) {
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
        $mode = $this->ReadPropertyInteger('TimingAutomatic');
        $this->SendDebug(__FUNCTION__, 'Mode: ' . $mode);
        switch($mode) {
            case 0: // Time only
                if (($value == 1) || ($value == 2)) {
                    $this->SendDebug(__FUNCTION__, '0-Switch: ' . var_export($value == 2, true));
                    if($this->SwitchDevice($value == 2)) {
                        $this->SwitchState($value == 2);
                    }
                }
                break;
            case 1: // Morning (Conditionla) / Evening (Time)
                if (($value == 1) || ($value == 12)) {
                    $this->SendDebug(__FUNCTION__, '1-Switch: ' . var_export($value == 2, true));
                    if($this->SwitchDevice($value == 12)) {
                        $this->SwitchState($value == 12);
                    }
                }
                break;
            case 2: // Morning (Time) / Evening (Conditionla)
                if (($value == 2) || ($value == 11)) {
                    $this->SendDebug(__FUNCTION__, '2-Switch: ' . var_export($value == 2, true));
                    if($this->SwitchDevice($value == 2)) {
                        $this->SwitchState($value == 2);
                    }
                }
                break;
            case 4: // Morning (Conditionla) / Evening (Conditionla)
                if (($value == 11) || ($value == 12)) {
                    $this->SendDebug(__FUNCTION__, '4-Switch: ' . var_export($value == 2, true));
                    if($this->SwitchDevice($value == 12)) {
                        $this->SwitchState($value == 12);
                    }
                }
                break;
            default:
                $this->SendDebug(__FUNCTION__, 'Mode: ' . $mode . ' unknowned!');
                break;
        }
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
            return IPS_GetObjectIDByIdent($ident, $LCs[0]);
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