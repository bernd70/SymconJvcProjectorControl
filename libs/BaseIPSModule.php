<?php

declare(strict_types=1);

class BaseIPSModule extends IPSModule
{
    var $moduleName = "BaseIPSModule";

    const PROPERTY_ExtendedLogging = "ExtendedLogging";

    const STRING_DateTimeFormat = "j.n.Y, H:i:s";

    public function Create()
    {
        parent::Create();

        $this->RegisterPropertyBoolean(self::PROPERTY_ExtendedLogging, false);
    }

    public function Destroy()
    {
        // TBD: Läuft auf einen "InstanceInterface is not available" Fehler, wenn an die Stelle aufgerufen
        // $this->UnregisterAllMessages();

        parent::Destroy();
    }

    protected function UnregisterAllMessages()
    {
        $registeredMessages = $this->GetMessageList();

        if ($registeredMessages !== false)
        {
            $this->LogDebug("Unregistering " . count($registeredMessages) . " message notifications");

            foreach ($registeredMessages as $sender => $msgList) 
            {
                foreach ($msgList as $msg) 
                    $this->UnregisterMessage($sender, $msg);
            }
        }
    }    

    protected function Log(string $logMessage)
    {
        IPS_LogMessage($this->moduleName, $logMessage);
    }

    protected function LogWarning(string $logMessage, bool $extendedLogMessage = false)
    {
        if ($extendedLogMessage && !$this->ReadPropertyBoolean(self::PROPERTY_ExtendedLogging))
            return;
    
        IPS_LogMessage($this->moduleName, "[WARN] " . $logMessage);
    }

    protected function LogError(string $logMessage)
    {
        IPS_LogMessage($this->moduleName, "[ERROR] " . $logMessage);
    }

    protected function LogDebug(string $logMessage)
    {
        if (!$this->ReadPropertyBoolean(self::PROPERTY_ExtendedLogging))
            return;
    
        IPS_LogMessage($this->moduleName, "[EXTENDED] " . $logMessage);
    }
}

?>
