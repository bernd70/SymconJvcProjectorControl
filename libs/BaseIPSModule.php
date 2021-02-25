<?php

declare(strict_types=1);

class BaseIPSModule extends IPSModule
{
    const STRING_DateTimeFormat = "j.n.Y, H:i:s";

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
            $this->SendDebug(__FUNCTION__, "Unregistering " . count($registeredMessages) . " message notifications", 0);

            foreach ($registeredMessages as $sender => $msgList) 
            {
                foreach ($msgList as $msg) 
                    $this->UnregisterMessage($sender, $msg);
            }
        }
    }


    // Return true if the value was changed
    protected function UpdateIntegerValueIfChanged(string $varIdent, int $newValue) : bool
    {
        if ($newValue === false)
            return false;

        $oldValue = GetValueInteger($this->GetIDForIdent($varIdent));
        if ($oldValue != $newValue)
        {
            $this->SendDebug(__FUNCTION__, "Update ident=" . $varIdent . ", oldValue=" . $oldValue . ", newValue=" . $newValue, 0);

            SetValueInteger($this->GetIDForIdent($varIdent), $newValue);
            return true;
        }

        return false;
    }

    protected function UpdateStringValueIfChanged(string $varIdent, string $newValue) : bool
    {
        if ($newValue === false)
            return false;

        $oldValue = GetValueString($this->GetIDForIdent($varIdent));
        if ($oldValue != $newValue)
        {
            $this->SendDebug(__FUNCTION__, "Update ident=" . $varIdent . ", oldValue=" . $oldValue . ", newValue=" . $newValue, 0);

            SetValueString($this->GetIDForIdent($varIdent), $newValue);
            return true;
        }

        return false;
    }    
}

?>
