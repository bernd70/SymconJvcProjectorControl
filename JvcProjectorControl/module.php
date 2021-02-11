<?php

declare(strict_types=1);

include "SingleJvcProjectorConnection.php";

class JvcProjectorControl extends IPSModule
{
    var $moduleName = "JvcProjectorControl";

	const LOGLEVEL_INFO = 0;
	const LOGLEVEL_WARNING = 1;
	const LOGLEVEL_ERROR = 2;
    const LOGLEVEL_DEBUG = 9;
        
    const PROPERTY_HOST = "Host";
    const PROPERTY_PORT = "Port";
    const PROPERTY_UPDATEINTERVAL = "UpdateInterval";
    const PROPERTY_LOGLEVEL = "LogLevel";

    const VARIABLE_Model = "Model";
    const VARIABLE_Power = "Power";
    const VARIABLE_PowerState = "PowerState";
    const VARIABLE_Input = "Input";
    const VARIABLE_SourceStatus = "SourceState";
    const VARIABLE_LampHours = "LampHours";
    const VARIABLE_Signal = "Signal";
    const VARIABLE_Version = "Version";
    const VARIABLE_MACAddress = "MACAddress";
    const VARIABLE_ColorModel = "ColorModel";
    const VARIABLE_ColorSpace = "ColorSpace";
    const VARIABLE_HDRMode = "HDRMode";

    const STRING_Unknown = "Unbekannt";

    var $projectorModel;

    public function Create()
    {
        // Diese Zeile nicht löschen
        parent::Create();

        $this->RegisterProperties();
        $this->RegisterVariableProfiles();
        $this->RegisterVariables();

        $this->RegisterTimer('Update', 0, 'JvcProjectorControl_GetProjectorStatus($_IPS[\'TARGET\'], 0);');        
    }

    public function ApplyChanges()
    {
        // Diese Zeile nicht löschen
        parent::ApplyChanges();

        // Invalidate all variables so they will be updated by the first GetProjectorStatus() call
        SetValueString($this->GetIDForIdent(self::VARIABLE_Model), "");
        SetValueInteger($this->GetIDForIdent(self::VARIABLE_PowerState), JvcProjectorConnection::POWERSTATE_Unknown);
        SetValueInteger($this->GetIDForIdent(self::VARIABLE_Input), JvcProjectorConnection::INPUT_Unknown);
        SetValueInteger($this->GetIDForIdent(self::VARIABLE_SourceStatus), JvcProjectorConnection::SOURCESTATUS_Unknown);

        $port = $this->ReadPropertyInteger(self::PROPERTY_PORT);

        try
        {
            $jvcProjectorConnection = $this->Connect();

            try
            {
                $this->UpdateVariables($jvcProjectorConnection);

                $this->SetTimerInterval('Update', $this->ReadPropertyInteger(self::PROPERTY_UPDATEINTERVAL) * 1000);
            }
            catch (Exception $e)
            {
                $this->SetStatus(202);
            }
        }
        catch (Exception $e)
        {
            $this->SetStatus(201);
        }
    }

    private function RegisterProperties()
    {
        $this->RegisterPropertyString(self::PROPERTY_HOST, "");
        $this->RegisterPropertyInteger(self::PROPERTY_PORT, 20554);
        $this->RegisterPropertyInteger(self::PROPERTY_UPDATEINTERVAL, 10);
        $this->RegisterPropertyInteger(self::PROPERTY_LOGLEVEL, self::LOGLEVEL_DEBUG);
    }

    private function RegisterVariableProfiles()
    {
        if (!IPS_VariableProfileExists("JvcProjectorControl.PowerState"))
        {
            IPS_CreateVariableProfile("JvcProjectorControl.PowerState", 1);

            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerState", JvcProjectorConnection::POWERSTATE_Unknown, self::STRING_Unknown, "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerState", JvcProjectorConnection::POWERSTATE_Standby, "Standby", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerState", JvcProjectorConnection::POWERSTATE_Starting, "Hochfahren", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerState", JvcProjectorConnection::POWERSTATE_PoweredOn, "Eingeschaltet", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerState", JvcProjectorConnection::POWERSTATE_Cooldown, "Abkühlen", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerState", JvcProjectorConnection::POWERSTATE_Emergency, "Notfall", "", -1);
        }        

        if (!IPS_VariableProfileExists("JvcProjectorControl.Input"))
        {
            IPS_CreateVariableProfile("JvcProjectorControl.Input", 1);

            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_Unknown, self::STRING_Unknown, "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_HDMI1, "HDMI #1", "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_HDMI2, "HDMI #2", "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_Component, "Component", "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_PC, "PC", "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_Video, "Video", "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_SVideo, "S-Video", "", 0);
        }     
        

        if (!IPS_VariableProfileExists("JvcProjectorControl.SourceStatus"))
        {
            IPS_CreateVariableProfile("JvcProjectorControl.SourceStatus", 1);

            IPS_SetVariableProfileAssociation("JvcProjectorControl.SourceStatus", JvcProjectorConnection::SOURCESTATUS_Unknown, self::STRING_Unknown, "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.SourceStatus", JvcProjectorConnection::SOURCESTATUS_JVCLogo, "JVC Logo", "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.SourceStatus", JvcProjectorConnection::SOURCESTATUS_NoValidSignal, "Kein gültiges Signal", "", 0);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.SourceStatus", JvcProjectorConnection::SOURCESTATUS_Okay, "Okay", "", 0);
        }         


        if (!IPS_VariableProfileExists("JvcProjectorControl.Duration.Hours"))
        {
            IPS_CreateVariableProfile("JvcProjectorControl.Duration.Hours", 1);

            IPS_SetVariableProfileText("JvcProjectorControl.Duration.Hours", "", " Stunden");
            IPS_SetVariableProfileIcon("JvcProjectorControl.Duration.Hours", "Clock");
        }            
    }

    private function RegisterVariables()
    {
        $this->RegisterVariableBoolean(self::VARIABLE_Power, "Power", "~Switch", 1);
        $this->RegisterVariableInteger(self::VARIABLE_PowerState, "Power Status", "JvcProjectorControl.PowerState", 2);
        $this->RegisterVariableInteger(self::VARIABLE_Input, "Aktueller Eingang", "JvcProjectorControl.Input", 3);
        $this->RegisterVariableInteger(self::VARIABLE_SourceStatus, "Status Quelle", "JvcProjectorControl.SourceStatus", 4);
        $this->RegisterVariableString(self::VARIABLE_Signal, "Signal", "", 5);
        $this->RegisterVariableString(self::VARIABLE_ColorSpace, "Farbraum", "", 6);
        $this->RegisterVariableString(self::VARIABLE_ColorModel, "Farbmodell", "", 7);
        $this->RegisterVariableString(self::VARIABLE_HDRMode, "HDR Modus", "", 8);

        $this->RegisterVariableInteger(self::VARIABLE_LampHours, "Lampenstunden", "JvcProjectorControl.Duration.Hours", 10);

        $this->RegisterVariableString(self::VARIABLE_Model, "Modell", "", 20);
        $this->RegisterVariableString(self::VARIABLE_Version, "Firmware", "", 21);
        $this->RegisterVariableString(self::VARIABLE_MACAddress, "MAC Addresse", "", 22);

        $this->EnableAction(self::VARIABLE_Power);
        $this->EnableAction(self::VARIABLE_Input);
    }

    private function Connect() : SingleJvcProjectorConnection
    {
        $host = $this->ReadPropertyString(self::PROPERTY_HOST);

        if ($host == "")
            throw new Exception("Host nicht gesetzt");

        $port = $this->ReadPropertyInteger(self::PROPERTY_PORT);

        $this->Log("Verbindungsaufbau zu Projektor " . $host . " auf Port " . $port, self::LOGLEVEL_DEBUG);

        $jvcProjectorConnection =  new SingleJvcProjectorConnection($host, $port);

        $jvcProjectorConnection->Connect();

        return $jvcProjectorConnection;
    }

    public function RequestAction($ident, $value)
    {
        $this->Log("RequestAction(" . $ident . ", " . $value . ") aufgerufen", self::LOGLEVEL_DEBUG);

        switch ($ident)
        {
            case self::VARIABLE_Power:
                if ($value == true)
                    $this->PowerOn();
                else
                    $this->PowerOff();
                break;

            case self::VARIABLE_Input:
                $this->SwitchInput($value);
                break;
            }
    }    

    public function GetProjectorStatus()
    {
        try
        {
            $jvcProjectorConnection = $this->Connect();

            $this->UpdateVariables($jvcProjectorConnection);

            return true;
        }
        catch (Exception $e) 
        {
            $this->Log("Fehler beim Ausführen von GetProjectorStatus Kommando: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            return false;
        }
    }

    public function PowerOn()
    {
        try
        {
            $jvcProjectorConnection = $this->Connect();

            $powerState = $jvcProjectorConnection->GetPowerState();
            if ($powerState == JvcProjectorConnection::POWERSTATE_Standby)
            {
                $this->Log("Schalte Gerät ein", self::LOGLEVEL_INFO);

                $jvcProjectorConnection->PowerOn();
                $jvcProjectorConnection->GetPowerState();
            }
            else if ($powerState == JvcProjectorConnection::POWERSTATE_PoweredOn)
                $this->Log("PowerOn Kommando ignoriert, Gerät ist bereits eingeschaltet", self::LOGLEVEL_WARNING);
            else if ($powerState == JvcProjectorConnection::POWERSTATE_Starting)
                $this->Log("PowerOn Kommando ignoriert, Gerät startet bereits", self::LOGLEVEL_WARNING);
            else
            {
                $this->Log("PowerOn Kommando bei Status [" . $jvcProjectorConnection->TranslatePowerState($powerState) . "] ungültig", self::LOGLEVEL_ERROR);
                return false;
            }

            return true;
        }
        catch (Exception $e) 
        {
            $this->Log("Fehler beim Ausführen von PowerOn Kommando: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            return false;
        }
    }

    public function PowerOff()
    {
        try
        {
            $jvcProjectorConnection = $this->Connect();

            $powerState = $jvcProjectorConnection->GetPowerState();
            if ($powerState == JvcProjectorConnection::POWERSTATE_PoweredOn)
            {
                $this->Log("Schalte Gerät aus", self::LOGLEVEL_INFO);
                $jvcProjectorConnection->PowerOff();
                $jvcProjectorConnection->GetPowerState();
            }
            else if ($powerState == JvcProjectorConnection::POWERSTATE_Standby)
                $this->Log("PowerOff Kommando ignoriert, Gerät ist bereits ausgeschaltet", self::LOGLEVEL_WARNING);
            else if ($powerState == JvcProjectorConnection::POWERSTATE_Cooldown)
                $this->Log("PowerOff Kommando ignoriert, Gerät fährt bereits herunter", self::LOGLEVEL_WARNING);
            else
            {
                $this->Log("PowerOff Kommando bei Status [" . $jvcProjectorConnection->TranslatePowerState($powerState) . "] ungültig", self::LOGLEVEL_ERROR);            
                return false;
            }

            return true;
        }
        catch (Exception $e) 
        {
            $this->Log("Fehler beim Ausführen von PowerOff Kommando: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            return false;
        }
    }

    public function SwitchInput(int $input)
    {
        try
        {
            $jvcProjectorConnection = $this->Connect();

            if (!$this->CheckPower($jvcProjectorConnection, "SwitchInput"))
                return false;

            $currentInput = $jvcProjectorConnection->GetCurrentInput();
            if ($currentInput != $input)
            {
                $this->Log("Schalte Eingang um auf [" . $jvcProjectorConnection-> TranslateInput($input) . "]", self::LOGLEVEL_INFO);

                $jvcProjectorConnection->SwitchInput($input);  

                $this->UpdateVariables($jvcProjectorConnection);
            }
            else
                $this->Log("SwitchInput Kommando ignoriert, aktueller Eingang ist bereits [" . $jvcProjectorConnection->TranslateInput($input) . "]", self::LOGLEVEL_WARNING);

            return true;
        }
        catch (Exception $e) 
        {
            $this->Log("Fehler beim Ausführen von SwitchInput Kommando: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            return false;
        }        
    }

    public function SetLampPower(bool $high)
    {
        try
        {
            $jvcProjectorConnection = $this->Connect();

            if (!$this->CheckPower($jvcProjectorConnection, "SetLampPower"))
                return false;

            $jvcProjectorConnection->SetLampPower($high);

            return true;
        }
        catch (Exception $e) 
        {
            $this->Log("Fehler beim Ausführen von SetLampPower Kommando: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            return false;
        }        
    }    

    private function CheckPower(JvcProjectorConnection $jvcProjectorConnection, string $function) : bool
    {
        try
        {
            $powerState = $jvcProjectorConnection->GetPowerState();

            if ($powerState == JvcProjectorConnection::POWERSTATE_PoweredOn)
                return true;

            $this->Log("Funktion " . $function . " kann nur ausgeführt werden, wenn das Gerät eingeschaltet ist.", self::LOGLEVEL_ERROR);
            return false;
        }
        catch (Exception $e) 
        {
            throw new Exception("Fehler beim Ausführen von GetPowerState Kommando: " . $e->getMessage());
        }            
    }

    private function UpdateVariables(JvcProjectorConnection $jvcProjectorConnection)
    {
        // Only read beamer model once
        if (!isset($this->projectorModel))
        {
            try
            {
                $this->Log("Get Projector Model", self::LOGLEVEL_DEBUG);

                $this->projectorModel = $jvcProjectorConnection->GetModel();
                $this->UpdateStringValueIfChanged(self::VARIABLE_Model, $this->projectorModel);
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von Model: " . $e->getMessage(), self::LOGLEVEL_ERROR);
                $this->UpdateStringValueIfChanged(self::VARIABLE_Model, self::STRING_Unknown);
            }

            try
            {
                $this->Log("Get MAC address", self::LOGLEVEL_DEBUG);

                $this->UpdateStringValueIfChanged(self::VARIABLE_MACAddress, $jvcProjectorConnection->GetMACAddress());
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von MAC Adresse: " . $e->getMessage(), self::LOGLEVEL_ERROR);
                $this->UpdateStringValueIfChanged(self::VARIABLE_MACAddress, self::STRING_Unknown);
            }
        }

        try
        {
            $this->Log("Update Power State", self::LOGLEVEL_DEBUG);

            $powerState = $jvcProjectorConnection->GetPowerState();
            if ($this->UpdateIntegerValueIfChanged(self::VARIABLE_PowerState, $powerState))
                SetValueBoolean($this->GetIDForIdent(self::VARIABLE_Power), $powerState == JvcProjectorConnection::POWERSTATE_PoweredOn);
        }
        catch (Exception $e)
        {
            $this->Log("Fehler beim Ermitteln von PowerState: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            $this->UpdateIntegerValueIfChanged(self::VARIABLE_PowerState, JvcProjectorConnection::POWERSTATE_Unknown);
        }

        $currentInput = JvcProjectorConnection::INPUT_Unknown;
        $sourceStatus = JvcProjectorConnection::SOURCESTATUS_Unknown;
        $signal = self::STRING_Unknown;
        $softwareVersion = self::STRING_Unknown;

        if ($powerState == JvcProjectorConnection::POWERSTATE_PoweredOn)
        {
            try
            {
                $this->Log("Update Current Input", self::LOGLEVEL_DEBUG);

                $currentInput = $jvcProjectorConnection->GetCurrentInput();
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von Current Input: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            }
    
            try
            {
                $this->Log("Update Source Status", self::LOGLEVEL_DEBUG);

                $sourceStatus = $jvcProjectorConnection->GetSourceStatus();
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von Source Status: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            }
    
            try
            {
                $this->Log("Update Signal", self::LOGLEVEL_DEBUG);
                
                $signal = $jvcProjectorConnection->GetSignal();
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von Signal: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            }

            try
            {
                $this->Log("Update Lamp Hours", self::LOGLEVEL_DEBUG);

                $this->UpdateIntegerValueIfChanged(self::VARIABLE_LampHours, $jvcProjectorConnection->GetLampHours());
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von Lamp Hours: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            }
    
            try
            {
                $this->Log("Update Software Version", self::LOGLEVEL_DEBUG);
            
                $softwareVersion = $jvcProjectorConnection->GetVersion();
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von Software Version: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            }
        }

        $this->UpdateIntegerValueIfChanged(self::VARIABLE_Input, $currentInput);
        $this->UpdateIntegerValueIfChanged(self::VARIABLE_SourceStatus, $sourceStatus);
        $this->UpdateStringValueIfChanged(self::VARIABLE_Signal, $signal);
        $this->UpdateStringValueIfChanged(self::VARIABLE_Version, $softwareVersion);

        $colorSpace = self::STRING_Unknown;
        $colorModel = self::STRING_Unknown;
        $hdrMode = self::STRING_Unknown;

        if ($sourceStatus == JvcProjectorConnection::SOURCESTATUS_Okay)
        {
            try
            {
                $this->Log("Update Color Space", self::LOGLEVEL_DEBUG);

                $colorSpace = $jvcProjectorConnection->GetColorSpace();
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von Color Space: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            }            

            try
            {
                $this->Log("Update Color Model", self::LOGLEVEL_DEBUG);

                $colorModel = $jvcProjectorConnection->GetColorModel();
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von Color Model: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            }  
            
            try
            {
                $this->Log("Update HDR Mode", self::LOGLEVEL_DEBUG);

                $hdrMode = $jvcProjectorConnection->GetHDRMode();
            }
            catch (Exception $e)
            {
                $this->Log("Fehler beim Ermitteln von HDR Mode: " . $e->getMessage(), self::LOGLEVEL_ERROR);
            }
        }

        $this->UpdateStringValueIfChanged(self::VARIABLE_ColorSpace, $colorSpace);
        $this->UpdateStringValueIfChanged(self::VARIABLE_ColorModel, $colorModel);
        $this->UpdateStringValueIfChanged(self::VARIABLE_HDRMode, $hdrMode);

    }

    // Return true if the value was changed
    private function UpdateIntegerValueIfChanged(string $varIdent, int $newValue) : bool
    {
        if ($newValue === false)
            return false;

        $oldValue = GetValueInteger($this->GetIDForIdent($varIdent));

        if ($oldValue != $newValue)
        {
            $this->Log("Setze " . $varIdent . " von " . $oldValue . " auf " . $newValue, self::LOGLEVEL_DEBUG);

            SetValueInteger($this->GetIDForIdent($varIdent), $newValue);
            return true;
        }

        return false;
    }

    private function UpdateStringValueIfChanged(string $varIdent, string $newValue) : bool
    {
        if ($newValue === false)
            return false;

        $oldValue = GetValueString($this->GetIDForIdent($varIdent));

        if ($oldValue != $newValue)
        {
            $this->Log("Setze " . $varIdent . " von " . $oldValue . " auf " . $newValue, self::LOGLEVEL_DEBUG);

            SetValueString($this->GetIDForIdent($varIdent), $newValue);
            return true;
        }

        return false;
    }    

    private function Log(string $logMessage, int $logLevel)
    {
        if ($this->ReadPropertyInteger(self::PROPERTY_LOGLEVEL) < $logLevel)
            return;
    
        switch($logLevel)
        {
            case self::LOGLEVEL_INFO:
            case self::LOGLEVEL_WARNING:
            case self::LOGLEVEL_ERROR:
                IPS_LogMessage($this->moduleName, $logMessage);
                break;
    
            case self::LOGLEVEL_DEBUG:
                IPS_LogMessage($this->moduleName, "[DBG] " . $logMessage);
                break;
        }
    }
}

?>
