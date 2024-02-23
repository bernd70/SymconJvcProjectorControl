<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/BaseIPSModule.php';

include "SingleJvcProjectorConnection.php";

class JvcProjector extends BaseIPSModule
{
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

    private bool $initialRun = true;

    public function Create()
    {
        // Diese Zeile nicht löschen
        parent::Create();

        $this->RegisterProperties();
        $this->RegisterVariableProfiles();
        $this->RegisterVariables();

        $this->RegisterTimer('Update', 0, 'JvcProjector_UpdateProjectorStatus($_IPS[\'TARGET\']);');
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
                $this->UpdateVariables($jvcProjectorConnection);

                $this->SetTimerInterval('Update', $this->ReadPropertyInteger(self::PROPERTY_UPDATEINTERVAL) * 1000);

                $this->SetStatus(102);
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler bei der Kommunikation: " . $e->getMessage(), KL_ERROR);

                $this->SetStatus(202);
            }
        }
        catch (Exception $e)
        {
            $this->LogMessage("Fehler beim Verbindungsaufbau: " . $e->getMessage(), KL_ERROR);

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
        if (!IPS_VariableProfileExists("JvcProjector.PowerStatus"))
        {
            IPS_CreateVariableProfile("JvcProjector.PowerStatus", 1);

            IPS_SetVariableProfileAssociation("JvcProjector.PowerStatus", JvcProjectorConnection::POWERSTATUS_Unknown, $this->Translate("Unknown"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.PowerStatus", JvcProjectorConnection::POWERSTATUS_Standby, $this->Translate("Standby"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.PowerStatus", JvcProjectorConnection::POWERSTATUS_Starting, $this->Translate("Starting"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.PowerStatus", JvcProjectorConnection::POWERSTATUS_PoweredOn, $this->Translate("Powered On"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.PowerStatus", JvcProjectorConnection::POWERSTATUS_Cooldown, $this->Translate("Cooling down"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.PowerStatus", JvcProjectorConnection::POWERSTATUS_Emergency, $this->Translate("Emergency"), "", -1);
        }

        if (!IPS_VariableProfileExists("JvcProjector.Input"))
        {
            IPS_CreateVariableProfile("JvcProjector.Input", 1);

            IPS_SetVariableProfileAssociation("JvcProjector.Input", JvcProjectorConnection::INPUT_Unknown, $this->Translate("Unknown"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.Input", JvcProjectorConnection::INPUT_HDMI1, "HDMI #1", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.Input", JvcProjectorConnection::INPUT_HDMI2, "HDMI #2", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.Input", JvcProjectorConnection::INPUT_Component, "Component", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.Input", JvcProjectorConnection::INPUT_PC, "PC", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.Input", JvcProjectorConnection::INPUT_Video, "Video", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.Input", JvcProjectorConnection::INPUT_SVideo, "S-Video", "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.Input", self::INPUT_Switching, $this->Translate("Switching"), "", -1);
        }


        if (!IPS_VariableProfileExists("JvcProjector.SourceStatus"))
        {
            IPS_CreateVariableProfile("JvcProjector.SourceStatus", 1);

            IPS_SetVariableProfileAssociation("JvcProjector.SourceStatus", JvcProjectorConnection::SOURCESTATUS_Unknown, $this->Translate("Unknown"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.SourceStatus", JvcProjectorConnection::SOURCESTATUS_JVCLogo, $this->Translate("JVC Logo"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.SourceStatus", JvcProjectorConnection::SOURCESTATUS_NoValidSignal, $this->Translate("No valid signal"), "", -1);
            IPS_SetVariableProfileAssociation("JvcProjector.SourceStatus", JvcProjectorConnection::SOURCESTATUS_Okay, $this->Translate("Okay"), "", -1);
        }


        if (!IPS_VariableProfileExists("JvcProjector.Duration.Hours"))
        {
            IPS_CreateVariableProfile("JvcProjector.Duration.Hours", 1);

            IPS_SetVariableProfileText("JvcProjector.Duration.Hours", "", " Stunden");
            IPS_SetVariableProfileIcon("JvcProjector.Duration.Hours", "Clock");
        }
    }

    private function RegisterVariables()
    {
        $this->RegisterVariableBoolean(self::VARIABLE_Power, "Power", "~Switch", 1);
        $this->RegisterVariableInteger(self::VARIABLE_PowerStatus, $this->Translate("Power Status"), "JvcProjector.PowerStatus", 2);
        $this->RegisterVariableInteger(self::VARIABLE_Input, $this->Translate("Current input"), "JvcProjector.Input", 3);
        $this->RegisterVariableInteger(self::VARIABLE_SourceStatus, $this->Translate("Source Status"), "JvcProjector.SourceStatus", 4);
        $this->RegisterVariableString(self::VARIABLE_Signal, $this->Translate("Signal"), "", 5);
        $this->RegisterVariableString(self::VARIABLE_ColorSpace, $this->Translate("Color space"), "", 6);
        $this->RegisterVariableString(self::VARIABLE_ColorModel, $this->Translate("Color model"), "", 7);
        $this->RegisterVariableString(self::VARIABLE_HDRMode, $this->Translate("HDR mode"), "", 8);

        $this->RegisterVariableInteger(self::VARIABLE_LampHours, $this->Translate("Lamp hours"), "JvcProjector.Duration.Hours", 10);

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

        $this->SendDebug(__FUNCTION__, "host=" . $host . ", port=" . $port, 0);

        $jvcProjectorConnection = new SingleJvcProjectorConnection($host, $port);

        $jvcProjectorConnection->Connect();

        return $jvcProjectorConnection;
    }

    public function RequestAction($ident, $value)
    {
        $this->SendDebug(__FUNCTION__, "ident=" . $ident . ", value=" . $value, 0);

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

    public function UpdateProjectorStatus()
    {
        try
        {
            $jvcProjectorConnection = $this->Connect();

            $this->UpdateVariables($jvcProjectorConnection);

            return true;
        }
        catch (Exception $e)
        {
            $this->LogMessage("Fehler beim Ausführen von UpdateProjectorStatus Kommando: " . $e->getMessage(), KL_ERROR);
            return false;
        }
    }

    public function PowerOn()
    {
        $this->LogMessage("Schalte Projektor ein", KL_NOTIFY);

        try
        {
            $jvcProjectorConnection = $this->Connect();

            $powerStatus = $jvcProjectorConnection->GetPowerStatus();
            if ($powerStatus == JvcProjectorConnection::POWERSTATUS_Standby)
            {
                $this->LogMessage("Schalte Gerät ein", KL_MESSAGE);

                $jvcProjectorConnection->PowerOn();

                $this->UpdatePowerStatus($jvcProjectorConnection);
            }
            else if ($powerStatus == JvcProjectorConnection::POWERSTATUS_PoweredOn)
                $this->LogMessage("PowerOn Kommando ignoriert, Gerät ist bereits eingeschaltet", KL_WARNING);
            else if ($powerStatus == JvcProjectorConnection::POWERSTATUS_Starting)
                $this->LogMessage("PowerOn Kommando ignoriert, Gerät startet bereits", KL_WARNING);
            else
            {
                $this->LogMessage("PowerOn Kommando bei Status [" . $jvcProjectorConnection->TranslatePowerStatus($powerStatus) . "] ungültig", KL_ERROR);
                return false;
            }

            return true;
        }
        catch (Exception $e)
        {
            $this->LogMessage("Fehler beim Ausführen von PowerOn Kommando: " . $e->getMessage(), KL_ERROR);
            return false;
        }
    }

    public function PowerOff()
    {
        $this->LogMessage("Schalte Projektor aus", KL_NOTIFY);

        try
        {
            $jvcProjectorConnection = $this->Connect();

            $powerStatus = $jvcProjectorConnection->GetPowerStatus();
            if ($powerStatus == JvcProjectorConnection::POWERSTATUS_PoweredOn)
            {
                $this->LogMessage("Schalte Gerät aus", KL_MESSAGE);

                $jvcProjectorConnection->PowerOff();

                $this->UpdatePowerStatus($jvcProjectorConnection);
            }
            else if ($powerStatus == JvcProjectorConnection::POWERSTATUS_Standby)
                $this->LogMessage("PowerOff Kommando ignoriert, Gerät ist bereits ausgeschaltet", KL_WARNING);
            else if ($powerStatus == JvcProjectorConnection::POWERSTATUS_Cooldown)
                $this->LogMessage("PowerOff Kommando ignoriert, Gerät fährt bereits herunter", KL_WARNING);
            else
            {
                $this->LogMessage("PowerOff Kommando bei Status [" . $jvcProjectorConnection->TranslatePowerStatus($powerStatus) . "] ungültig", KL_ERROR);
                return false;
            }

            return true;
        }
        catch (Exception $e)
        {
            $this->LogMessage("Fehler beim Ausführen von PowerOff Kommando: " . $e->getMessage(), KL_ERROR);
            return false;
        }
    }

    public function SwitchInput(int $input)
    {
        $this->LogMessage("Schalte Eingang um auf " . $input, KL_NOTIFY);

        if ($input == self::INPUT_Switching)
        {
            $this->LogMessage("Ungültiger Eingang ausgewählt", KL_ERROR);
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
                $this->LogMessage("Schalte Eingang um auf [" . $jvcProjectorConnection-> TranslateInput($input) . "]", KL_MESSAGE);

                // Set intermediate switch state to give quicker feedback
                SetValueInteger($this->GetIDForIdent(self::VARIABLE_Input), self::INPUT_Switching);

                $jvcProjectorConnection->SwitchInput($input);

                $this->UpdateVariables($jvcProjectorConnection);
            }
            else
                $this->LogMessage("SwitchInput Kommando ignoriert, aktueller Eingang ist bereits [" . $jvcProjectorConnection->TranslateInput($input) . "]", KL_WARNING);

            return true;
        }
        catch (Exception $e)
        {
            $this->LogMessage("Fehler beim Ausführen von SwitchInput Kommando: " . $e->getMessage(), KL_ERROR);
            return false;
        }
    }

    public function SetLampPower(bool $high)
    {
        $this->LogMessage("Setze Powermodus Lampe auf " . $high, KL_NOTIFY);

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
            $this->LogMessage("Fehler beim Ausführen von SetLampPower Kommando: " . $e->getMessage(), KL_ERROR);
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

            $this->LogMessage("Funktion " . $function . " kann nur ausgeführt werden, wenn das Gerät eingeschaltet ist.", KL_ERROR);
            return false;
        }
        catch (Exception $e)
        {
            throw new Exception("Fehler beim Ausführen von GetPowerStatus Kommando: " . $e->getMessage());
        }
    }

    private function UpdateVariables(JvcProjectorConnection $jvcProjectorConnection)
    {
        // Only read beamer model on initial run
        if ($this->initialRun)
        {
            $this->SendDebug(__FUNCTION__, "Get Projector Model and MAC address", 0);

            try
            {
                $this->UpdateStringValueIfChanged(self::VARIABLE_Model, $jvcProjectorConnection->GetModel());
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von Modell: " . $e->getMessage(), KL_ERROR);
                $this->UpdateStringValueIfChanged(self::VARIABLE_Model, $this->Translate("Unknown"));
            }

            try
            {
                $this->UpdateStringValueIfChanged(self::VARIABLE_MACAddress, $jvcProjectorConnection->GetMACAddress());
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von MAC Adresse: " . $e->getMessage(), KL_ERROR);
                $this->UpdateStringValueIfChanged(self::VARIABLE_MACAddress, $this->Translate("Unknown"));
            }

            $this->initialRun = false;
        }

        try
        {
            $this->SendDebug(__FUNCTION__, "Update Power Status", 0);

            $powerStatus = $this->UpdatePowerStatus($jvcProjectorConnection);
        }
        catch (Exception $e)
        {
            $this->LogMessage("Fehler beim Ermitteln von PowerStatus: " . $e->getMessage(), KL_ERROR);
            $this->UpdateIntegerValueIfChanged(self::VARIABLE_PowerStatus, JvcProjectorConnection::POWERSTATUS_Unknown);
        }

        $currentInput = JvcProjectorConnection::INPUT_Unknown;
        $sourceStatus = JvcProjectorConnection::SOURCESTATUS_Unknown;
        $signal = $softwareVersion = $this->Translate("Unknown");

        if ($powerStatus == JvcProjectorConnection::POWERSTATUS_PoweredOn)
        {
            $this->SendDebug(__FUNCTION__, "Update variables for powered on projector", 0);

            try
            {
                $currentInput = $jvcProjectorConnection->GetCurrentInput();
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von Current Input: " . $e->getMessage(), KL_ERROR);
            }

            try
            {
                $sourceStatus = $jvcProjectorConnection->GetSourceStatus();
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von Source Status: " . $e->getMessage(), KL_ERROR);
            }

            try
            {
                $signal = $jvcProjectorConnection->GetSignal();
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von Signal: " . $e->getMessage(), KL_ERROR);
            }

            try
            {
                $this->UpdateIntegerValueIfChanged(self::VARIABLE_LampHours, $jvcProjectorConnection->GetLampHours());
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von Lamp Hours: " . $e->getMessage(), KL_ERROR);
            }

            try
            {
                $softwareVersion = $jvcProjectorConnection->GetVersion();
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von Software Version: " . $e->getMessage(), KL_ERROR);
            }
        }

        $this->UpdateIntegerValueIfChanged(self::VARIABLE_Input, $currentInput);
        $this->UpdateIntegerValueIfChanged(self::VARIABLE_SourceStatus, $sourceStatus);
        $this->UpdateStringValueIfChanged(self::VARIABLE_Signal, $signal);
        $this->UpdateStringValueIfChanged(self::VARIABLE_Version, $softwareVersion);

        $colorSpace = $colorModel = $hdrMode = $this->Translate("Unknown");

        if ($sourceStatus == JvcProjectorConnection::SOURCESTATUS_Okay)
        {
            $this->SendDebug(__FUNCTION__, "Update variables for valid source", 0);

            try
            {
                $colorSpace = $jvcProjectorConnection->GetColorSpace();
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von Color Space: " . $e->getMessage(), KL_ERROR);
            }

            try
            {
                $colorModel = $jvcProjectorConnection->GetColorModel();
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von Color Model: " . $e->getMessage(), KL_ERROR);
            }

            try
            {
                $hdrMode = $jvcProjectorConnection->GetHDRMode();
            }
            catch (Exception $e)
            {
                $this->LogMessage("Fehler beim Ermitteln von HDR Mode: " . $e->getMessage(), KL_ERROR);
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
}
