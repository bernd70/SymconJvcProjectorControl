<?php

declare(strict_types=1);

error_reporting (E_ALL);

include "JvcProjectorConnection.php";

// print_r(phpinfo());
// print_r(xdebug_info());

class TestJvcConnection
{
    var $jvcProjectorConnection;

    function DoTest1()
    {
        $this->jvcProjectorConnection = new JvcProjectorConnection("192.168.1.61");

        try
        {
            $this->jvcProjectorConnection->Connect();

            $this->jvcProjectorConnection->TestCommunication();

            echo "Projector Model: " . $this->jvcProjectorConnection->GetModel() . "\n";
            echo "MAC address: " . $this->jvcProjectorConnection->GetMACAddress() . "\n";

            $powerState = $this->jvcProjectorConnection->GetPowerStatus();

            echo "Power State: " . $this->jvcProjectorConnection->TranslatePowerStatus($powerState) . "\n";
        
            // echo "Power On: " . ExecuteRequest($socket, "\x21\x89\x01PW1\x0a") . "\n";
            // echo "Power Off: " . ExecuteBasicRequest($socket, "PW0") . "\n";


            // $data = $this->jvcProjectorConnection->ExecuteAdvancedRequest("DSLA");
            // $data = $this->jvcProjectorConnection->ExecuteAdvancedRequest("FUEM");

            // PowerOn Required
            if ($powerState == JvcProjectorConnection::POWERSTATUS_PoweredOn)
            {
                echo "Lamp Hours: " . $this->jvcProjectorConnection->GetLampHours() . "\n";

                echo "Current Input: " . $this->jvcProjectorConnection->TranslateInput($this->jvcProjectorConnection->GetCurrentInput()) . "\n";
        
                $sourceStatus = $this->jvcProjectorConnection->GetSourceStatus();
                // echo "Source Status: " . $this->jvcProjectorConnection->TranslateSourceStatus($sourceStatus) . "\n";
                echo "Signal: " . $this->jvcProjectorConnection->GetSignal() . "\n";
                echo "Version: " . $this->jvcProjectorConnection->GetVersion() . "\n";

                //echo "MC: " . $this->jvcProjectorConnection->GetInfo("MC") . "\n";
                //echo "MF: " . $this->jvcProjectorConnection->GetInfo("MF") . "\n";

                // Valid source required
                if ($sourceStatus == JvcProjectorConnection::SOURCESTATUS_Okay)
                {
                    //echo "Gamma Table: " . $jvcProjectorConnection->GetGammaTable() . "\n";
                    //echo "Gamma Correction: " . $jvcProjectorConnection->GetGammaCorrectionValue() . "\n";

                    echo "RH: " . $this->jvcProjectorConnection->GetInfo("RH") . "\n"; // RH: 0F00
                    echo "RV: " . $this->jvcProjectorConnection->GetInfo("RV") . "\n"; // RV: 0870
                    echo "FH: " . $this->jvcProjectorConnection->GetInfo("FH") . "\n"; // FH: 34AE
                    echo "FV: " . $this->jvcProjectorConnection->GetInfo("FV") . "\n"; // FV: 1769
                    echo "DC: " . $this->jvcProjectorConnection->GetInfo("DC") . "\n"; // DC: 2
                    echo "XV: " . $this->jvcProjectorConnection->GetInfo("XV") . "\n"; // XV: 1
                    echo "CM: " . $this->jvcProjectorConnection->GetInfo("CM") . "\n"; // CM: 9
                    echo "HR: " . $this->jvcProjectorConnection->GetInfo("HR") . "\n"; // HR: F
                }
            }
        }
        catch (Exception $e) 
        {
            echo "Fehler beim Ausführen von Kommando: " . $e->getMessage() . "\n";
        }

        echo "Done\n";
    }

    function DoTest2()
    {
        $this->jvcProjectorConnection = new JvcProjectorConnection("192.168.1.61");

        try
        {
            echo "SwitchInput(2)\n";

            $this->jvcProjectorConnection->Connect();

            $this->jvcProjectorConnection->SwitchInput(2);

            echo "SwitchInput(2) done\n";
        }
        catch (Exception $e) 
        {
            echo "Fehler beim Ausführen von Kommando: " . $e->getMessage() . "\n";
        }

        try
        {
            echo "SwitchInput(1)\n";

            $this->jvcProjectorConnection->Connect();

            $this->jvcProjectorConnection->SwitchInput(1);

            echo "SwitchInput(1) done\n";
        }
        catch (Exception $e) 
        {
            echo "Fehler beim Ausführen von Kommando: " . $e->getMessage() . "\n";
        }

        echo "Done\n";
    }    

    // function DoTest3()
    // {
    //     $this->jvcProjectorConnection = new JvcProjectorConnection("192.168.1.61");

    //     try
    //     {
    //         $this->jvcProjectorConnection->Connect();

    //         while (true)
    //         {
    //             $data = $this->jvcProjectorConnection->ReadFromSocket();

    //             print_r($data);
    //         }
    //     }
    //     catch (Exception $e) 
    //     {
    //         echo "Socket Fehler: " . $e->getMessage() . "\n";
    //     }
    // }
}

$testClass = new TestJvcConnection();

$testClass->DoTest1();

?>
