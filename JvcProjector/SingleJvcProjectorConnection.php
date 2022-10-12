<?php

declare(strict_types=1);

include "JvcProjectorConnection.php";

class SingleJvcProjectorConnection extends JvcProjectorConnection
{
    function __destruct()
    {
        $this->Disconnect();
    }

    public function Connect()
    {
        if (!IPS_SemaphoreEnter("JVCProjectorConnection", 5000))
            throw new Exception("Es besteht bereits eine Verbindung zum Projektor (Semaphore belegt)");

        try
        {
            parent::Connect();
        }
        catch (Exception $e)
        {
            IPS_SemaphoreLeave("JVCProjectorConnection");

            throw $e;
        }

    }

    public function Disconnect()
    {
        try
        {
            parent::Disconnect();
        }
        catch (Exception $e)
        {
            IPS_SemaphoreLeave("JVCProjectorConnection");

            throw $e;
        }

        IPS_SemaphoreLeave("JVCProjectorConnection");
    }
}

?>
