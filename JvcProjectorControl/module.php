<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/BaseIPSModule.php';

include "SingleJvcProjectorConnection.php";

class JvcProjectorControl extends BaseIPSModule
{
    var $moduleName = "JvcProjectorControl";

    const PROPERTY_HOST = "Host";
    const PROPERTY_PORT = "Port";
    const PROPERTY_UPDATEINTERVAL = "UpdateInterval";

    const VARIABLE_Model = "Model";
    const VARIABLE_Power = "Power";
    const VARIABLE_PowerStatus = "PowerStatus";
    const VARIABLE_Input = "Input";
    const VARIABLE_SourceStatus = "SourceStatus";
    const VARIABLE_LampHours = "LampHours";
    const VARIABLE_Signal = "Signal";
    const VARIABLE_Version = "Version";
    const VARIABLE_MACAddress = "MACAddress";
    const VARIABLE_ColorModel = "ColorModel";
    const VARIABLE_ColorSpace = "ColorSpace";
    const VARIABLE_HDRMode = "HDRMode";

    const INPUT_Switching = 99;

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
        SetValueInteger($this->GetIDForIdent(self::VARIABLE_PowerStatus), JvcProjectorConnection::POWERSTATUS_Unknown);
        SetValueInteger($this->GetIDForIdent(self::VARIABLE_Input), JvcProjectorConnection::INPUT_Unknown);
        SetValueInteger($this->GetIDForIdent(self::VARIABLE_SourceStatus), JvcProjectorConnection::SOURCESTATUS_Unknown);

        $port = $this->ReadPropertyInteger(self::PROPERTY_PORT);

        try
        {
            $jvcProjectorConnection = $this->Connect();

            try
            {
                $this->UpdateVariables($jvcProjectorConnection, true);

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
    }

    private function RegisterVariableProfiles()
    {
        if (!IPS_VariableProfileExists("JvcProjectorControl.PowerStatus"))
        {
            IPS_CreateVariableProfile("JvcProjectorControl.PowerStatus", 1);

            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerStatus", JvcProjectorConnection::POWERSTATUS_Unknown, $this->Translate("Unknown"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerStatus", JvcProjectorConnection::POWERSTATUS_Standby, $this->Translate("Standby"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerStatus", JvcProjectorConnection::POWERSTATUS_Starting, $this->Translate("Starting"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerStatus", JvcProjectorConnection::POWERSTATUS_PoweredOn, $this->Translate("Powered On"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerStatus", JvcProjectorConnection::POWERSTATUS_Cooldown, $this->Translate("Cooling down"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.PowerStatus", JvcProjectorConnection::POWERSTATUS_Emergency, $this->Translate("Emergency"), "", -1);
        }        

        if (!IPS_VariableProfileExists("JvcProjectorControl.Input"))
        {
            IPS_CreateVariableProfile("JvcProjectorControl.Input", 1);

            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_Unknown, $this->Translate("Unknown"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_HDMI1, "HDMI #1", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_HDMI2, "HDMI #2", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_Component, "Component", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_PC, "PC", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_Video, "Video", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", JvcProjectorConnection::INPUT_SVideo, "S-Video", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.Input", self::INPUT_Switching, $this->Translate("Switching"), "", -1);
        }     
        

        if (!IPS_VariableProfileExists("JvcProjectorControl.SourceStatus"))
        {
            IPS_CreateVariableProfile("JvcProjectorControl.SourceStatus", 1);

            IPS_SetVariableProfileAssociation("JvcProjectorControl.SourceStatus", JvcProjectorConnection::SOURCESTATUS_Unknown, $this->Translate("Unknown"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.SourceStatus", JvcProjectorConnection::SOURCESTATUS_JVCLogo, $this->Translate("JVC Logo"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.SourceStatus", JvcProjectorConnection::SOURCESTATUS_NoValidSignal, $this->Translate("No valid signal"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjectorControl.SourceStatus", JvcProjectorConnection::SOURCESTATUS_Okay, $this->Translate("Okay"), "", -1);
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
        $this->RegisterVariableInteger(self::VARIABLE_PowerStatus, $this->Translate("Power Status"), "JvcProjectorControl.PowerStatus", 2);
        $this->RegisterVariableInteger(self::VARIABLE_Input, $this->Translate("Current input"), "JvcProjectorControl.Input", 3);
        $this->RegisterVariableInteger(self::VARIABLE_SourceStatus, $this->Translate("Source Status"), "JvcProjectorControl.SourceStatus", 4);
        $this->RegisterVariableString(self::VARIABLE_Signal, $this->Translate("Signal"), "", 5);
        $this->RegisterVariableString(self::VARIABLE_ColorSpace, $this->Translate("Color space"), "", 6);
        $this->RegisterVariableString(self::VARIABLE_ColorModel, $this->Translate("Color model"), "", 7);
        $this->RegisterVariableString(self::VARIABLE_HDRMode, $this->Translate("HDR mode"), "", 8);

        $this->RegisterVariableInteger(self::VARIABLE_LampHours, $this->Translate("Lamp hours"), "JvcProjectorControl.Duration.Hours", 10);

        $this->RegisterVariableString(self::VARIABLE_Model, $this->Translate("Model"), "", 20);
        $this->RegisterVariableString(self::VARIABLE_Version, $this->Translate("Firmware"), "", 21);
        $this->RegisterVariableString(self::VARIABLE_MACAddress, $this->Translate("MAC address"), "", 22);

        $this->EnableAction(self::VARIABLE_Power);
        $this->EnableAction(self::VARIABLE_Input);
    }

    private function Connect() : SingleJvcProjectorConnection
    {
        $host = $this->ReadPropertyString(self::PROPERTY_HOST);

        if ($host == "")
            throw new Exception("Host nicht gesetzt");

        $port = $this->ReadPropertyInteger(self::PROPERTY_PORT);

        $this->LogDebug("Verbindungsaufbau zu Projektor " . $host . " auf Port " . $port);

        $jvcProjectorConnection =  new SingleJvcProjectorConnection($host, $port);

        $jvcProjectorConnection->Connect();

        return $jvcProjectorConnection;
    }

    public function RequestAction($ident, $value)
    {
        $this->LogDebug("RequestAction(" . $ident . ", " . $value . ") aufgerufen");

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
            $this->LogError("Fehler beim Ausführen von GetProjectorStatus Kommando: " . $e->getMessage());
            return false;
        }
    }

    public function PowerOn()
    {
        try
        {
            $jvcProjectorConnection = $this->Connect();

            $powerStatus = $jvcProjectorConnection->GetPowerStatus();
            if ($powerStatus == JvcProjectorConnection::POWERSTATUS_Standby)
            {
                $this->Log("Schalte Gerät ein");

                $jvcProjectorConnection->PowerOn();

                $this->UpdatePowerStatus($jvcProjectorConnection);
            }
            else if ($powerStatus == JvcProjectorConnection::POWERSTATUS_PoweredOn)
                $this->LogWarning("PowerOn Kommando ignoriert, Gerät ist bereits eingeschaltet");
            else if ($powerStatus == JvcProjectorConnection::POWERSTATUS_Starting)
                $this->LogWarning("PowerOn Kommando ignoriert, Gerät startet bereits");
            else
            {
                $this->LogError("PowerOn Kommando bei Status [" . $jvcProjectorConnection->TranslatePowerStatus($powerStatus) . "] ungültig");
                return false;
            }

            return true;
        }
        catch (Exception $e) 
        {
            $this->LogError("Fehler beim Ausführen von PowerOn Kommando: " . $e->getMessage());
            return false;
        }
    }

    public function PowerOff()
    {
        try
        {
            $jvcProjectorConnection = $this->Connect();

            $powerStatus = $jvcProjectorConnection->GetPowerStatus();
            if ($powerStatus == JvcProjectorConnection::POWERSTATUS_PoweredOn)
            {
                $this->Log("Schalte Gerät aus");

                $jvcProjectorConnection->PowerOff();

                $this->UpdatePowerStatus($jvcProjectorConnection);
            }
            else if ($powerStatus == JvcProjectorConnection::POWERSTATUS_Standby)
                $this->LogWarning("PowerOff Kommando ignoriert, Gerät ist bereits ausgeschaltet");
            else if ($powerStatus == JvcProjectorConnection::POWERSTATUS_Cooldown)
                $this->LogWarning("PowerOff Kommando ignoriert, Gerät fährt bereits herunter");
            else
            {
                $this->LogError("PowerOff Kommando bei Status [" . $jvcProjectorConnection->TranslatePowerStatus($powerStatus) . "] ungültig");
                return false;
            }

            return true;
        }
        catch (Exception $e) 
        {
            $this->LogError("Fehler beim Ausführen von PowerOff Kommando: " . $e->getMessage());
            return false;
        }
    }

    public function SwitchInput(int $input)
    {
        if ($input == self::INPUT_Switching)
        {
            $this->LogError("Ungültiger Eingang ausgewählt");
            return;
        }
        
        try
        {
            $jvcProjectorConnection = $this->Connect();

            if (!$this->CheckPower($jvcProjectorConnection, "SwitchInput"))
                return false;

            $currentInput = $jvcProjectorConnection->GetCurrentInput();
            if ($currentInput != $input)
            {
                $this->Log("Schalte Eingang um auf [" . $jvcProjectorConnection-> TranslateInput($input) . "]");
                
                SetValueInteger($this->GetIDForIdent(self::VARIABLE_Input), self::INPUT_Switching);

                $jvcProjectorConnection->SwitchInput($input);  

                $this->UpdateVariables($jvcProjectorConnection);
            }
            else
                $this->LogWarning("SwitchInput Kommando ignoriert, aktueller Eingang ist bereits [" . $jvcProjectorConnection->TranslateInput($input) . "]");

            return true;
        }
        catch (Exception $e) 
        {
            $this->LogError("Fehler beim Ausführen von SwitchInput Kommando: " . $e->getMessage());
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
            $this->LogError("Fehler beim Ausführen von SetLampPower Kommando: " . $e->getMessage());
            return false;
        }        
    }    

    private function CheckPower(JvcProjectorConnection $jvcProjectorConnection, string $function) : bool
    {
        try
        {
            $powerStatus = $jvcProjectorConnection->GetPowerStatus();

            if ($powerStatus == JvcProjectorConnection::POWERSTATUS_PoweredOn)
                return true;

            $this->LogError("Funktion " . $function . " kann nur ausgeführt werden, wenn das Gerät eingeschaltet ist.");
            return false;
        }
        catch (Exception $e) 
        {
            throw new Exception("Fehler beim Ausführen von GetPowerStatus Kommando: " . $e->getMessage());
        }            
    }

    private function UpdateVariables(JvcProjectorConnection $jvcProjectorConnection, bool $initialRun = false)
    {
        // Only read beamer model on initial run
        if ($initialRun)
        {
            $this->LogDebug("Get Projector Model and MAC address");

            try
            {
                $this->UpdateStringValueIfChanged(self::VARIABLE_Model, $jvcProjectorConnection->GetModel());
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von Model: " . $e->getMessage());
                $this->UpdateStringValueIfChanged(self::VARIABLE_Model, $this->Translate("Unknown"));
            }

            try
            {
                $this->UpdateStringValueIfChanged(self::VARIABLE_MACAddress, $jvcProjectorConnection->GetMACAddress());
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von MAC Adresse: " . $e->getMessage());
                $this->UpdateStringValueIfChanged(self::VARIABLE_MACAddress, $this->Translate("Unknown"));
            }
        }

        try
        {
            $this->LogDebug("Update Power Status");

            $powerStatus = $this->UpdatePowerStatus($jvcProjectorConnection);
        }
        catch (Exception $e)
        {
            $this->LogError("Fehler beim Ermitteln von PowerStatus: " . $e->getMessage());
            $this->UpdateIntegerValueIfChanged(self::VARIABLE_PowerStatus, JvcProjectorConnection::POWERSTATUS_Unknown);
        }

        $currentInput = JvcProjectorConnection::INPUT_Unknown;
        $sourceStatus = JvcProjectorConnection::SOURCESTATUS_Unknown;
        $signal = $softwareVersion = $this->Translate("Unknown");

        if ($powerStatus == JvcProjectorConnection::POWERSTATUS_PoweredOn)
        {
            try
            {
                $this->LogDebug("Update Current Input");

                $currentInput = $jvcProjectorConnection->GetCurrentInput();
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von Current Input: " . $e->getMessage());
            }
    
            try
            {
                $this->LogDebug("Update Source Status");

                $sourceStatus = $jvcProjectorConnection->GetSourceStatus();
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von Source Status: " . $e->getMessage());
            }
    
            try
            {
                $this->LogDebug("Update Signal");
                
                $signal = $jvcProjectorConnection->GetSignal();
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von Signal: " . $e->getMessage());
            }

            try
            {
                $this->LogDebug("Update Lamp Hours");

                $this->UpdateIntegerValueIfChanged(self::VARIABLE_LampHours, $jvcProjectorConnection->GetLampHours());
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von Lamp Hours: " . $e->getMessage());
            }
    
            try
            {
                $this->LogDebug("Update Software Version");
            
                $softwareVersion = $jvcProjectorConnection->GetVersion();
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von Software Version: " . $e->getMessage());
            }
        }

        $this->UpdateIntegerValueIfChanged(self::VARIABLE_Input, $currentInput);
        $this->UpdateIntegerValueIfChanged(self::VARIABLE_SourceStatus, $sourceStatus);
        $this->UpdateStringValueIfChanged(self::VARIABLE_Signal, $signal);
        $this->UpdateStringValueIfChanged(self::VARIABLE_Version, $softwareVersion);

        $colorSpace = $colorModel = $hdrMode = $this->Translate("Unknown");

        if ($sourceStatus == JvcProjectorConnection::SOURCESTATUS_Okay)
        {
            try
            {
                $this->LogDebug("Update Color Space");

                $colorSpace = $jvcProjectorConnection->GetColorSpace();
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von Color Space: " . $e->getMessage());
            }            

            try
            {
                $this->LogDebug("Update Color Model");

                $colorModel = $jvcProjectorConnection->GetColorModel();
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von Color Model: " . $e->getMessage());
            }  
            
            try
            {
                $this->LogDebug("Update HDR Mode");

                $hdrMode = $jvcProjectorConnection->GetHDRMode();
            }
            catch (Exception $e)
            {
                $this->LogError("Fehler beim Ermitteln von HDR Mode: " . $e->getMessage());
            }
        }

        $this->UpdateStringValueIfChanged(self::VARIABLE_ColorSpace, $colorSpace);
        $this->UpdateStringValueIfChanged(self::VARIABLE_ColorModel, $colorModel);
        $this->UpdateStringValueIfChanged(self::VARIABLE_HDRMode, $hdrMode);

    }

    private function UpdatePowerStatus(JvcProjectorConnection $jvcProjectorConnection) : int
    {
        $powerStatus = $jvcProjectorConnection->GetPowerStatus();
        if ($this->UpdateIntegerValueIfChanged(self::VARIABLE_PowerStatus, $powerStatus))
            SetValueBoolean($this->GetIDForIdent(self::VARIABLE_Power), $powerStatus == JvcProjectorConnection::POWERSTATUS_PoweredOn);

        return $powerStatus;
    }

    // Return true if the value was changed
    private function UpdateIntegerValueIfChanged(string $varIdent, int $newValue) : bool
    {
        if ($newValue === false)
            return false;

        $oldValue = GetValueInteger($this->GetIDForIdent($varIdent));

        if ($oldValue != $newValue)
        {
            $this->LogDebug("Setze " . $varIdent . " von " . $oldValue . " auf " . $newValue);

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
            $this->LogDebug("Setze " . $varIdent . " von " . $oldValue . " auf " . $newValue);

            SetValueString($this->GetIDForIdent($varIdent), $newValue);
            return true;
        }

        return false;
    }
}

?>
