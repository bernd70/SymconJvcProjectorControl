<?php

declare(strict_types=1);

include "JvcProjectorConnection.php";

class SingleJvcProjectorConnection extends JvcProjectorConnection
{
    public function Connect()
    {
        if (!IPS_SemaphoreEnter("JVCProjectorConnection", 5000))
            throw new Exception("Es besteht bereits eine Verbindung zum Projektor (Semaphore belegt)");

        parent::Connect();
    }

    public function Disconnect()
    {
        parent::Disconnect();

        IPS_SemaphoreLeave("JVCProjectorConnection");
    }
}

?>
