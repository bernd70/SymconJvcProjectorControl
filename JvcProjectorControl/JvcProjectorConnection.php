<?php

declare(strict_types=1);

include "JvcProjectorMappings.php";

class JvcProjectorConnection
{
    const STRING_Unknown = "Unbekannt";

    const POWERSTATE_Unknown = 0;
    const POWERSTATE_Standby = 1;
    const POWERSTATE_Starting = 2;
    const POWERSTATE_PoweredOn = 3;
    const POWERSTATE_Cooldown = 4;
    const POWERSTATE_Emergency = 5;

    const INPUT_Unknown = 0;
    const INPUT_HDMI1 = 1;
    const INPUT_HDMI2 = 2;
    const INPUT_Component = 3;
    const INPUT_PC = 4;
    const INPUT_Video = 5;
    const INPUT_SVideo = 6;

    const SOURCESTATUS_Unknown = 0;
    const SOURCESTATUS_JVCLogo = 1;
    const SOURCESTATUS_Okay = 2;
    const SOURCESTATUS_NoValidSignal = 3;

    private $host;
    private $port;
    private $socket;
    private $isConnected = false;
    var $mappings;

    function __construct(string $host = "", int $port = 20554)
    {
        $this->SetServer($host, $port);
    }    

    function __destruct()
    {
        $this->Disconnect();
    }

    public function SetServer(string $host, int $port = 20554)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function Connect()
    {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) 
            throw new Exception("socket_create() fehlgeschlagen: " . socket_strerror(socket_last_error()));

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, array("sec" => 5, "usec" => 0));

        $retries = 3;

        while (true)
        {
            $result = socket_connect($this->socket, $this->host, $this->port);
            if ($result !== false)
                break;

            $socketError = socket_last_error($this->socket);
            if  ($socketError == 111) // ECONNREFUSED
            {
                IPS_Sleep(1000);

                $retries--;

                if ($retries > 0)
                    continue;
            }
            $error = "socket_connect() fehlgeschlagen: ($socketError) " . socket_strerror($socketError);
        
            socket_close($this->socket);
            unset($this->socket);
        
            throw new Exception($error);
        }

        $this->isConnected = true;

        // Wait for PJ_OK
        $reply = socket_read($this->socket, 2048);
        if ($reply != "PJ_OK")
        {
            $this->Disconnect();
            throw new Exception("Kein PJ_OK empfangen");
        }

        // Send PJREQ and wait for PJACK;
        $in="PJREQ";
        socket_write($this->socket, $in, strlen($in));
        $reply = socket_read($this->socket, 2048);
        if ($reply != "PJACK")
        {
            $this->Disconnect();
            throw new Exception("Kein PJACK empfangen");
        }

        $this->mappings = new JvcProjectorMappings();
    }

    public function Disconnect()
    {
        if (isset($this->socket) && $this->socket !== false)
        {
            socket_shutdown($this->socket);
            socket_close($this->socket);
            unset($this->socket);
        }

        $this->isConnected = false;
    }

    public function TestCommunication() : bool
    {
        try
        {
            return $this->ExecuteBasicRequest("\x00\x00");
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    public function GetPowerState() : int
    {
        $powerState = $this->ExecuteAdvancedRequest("PW");

        switch ($powerState)
        {
            case "0": return self::POWERSTATE_Standby;
            case "1": return self::POWERSTATE_PoweredOn;
            case "2": return self::POWERSTATE_Cooldown;
            case "3": return self::POWERSTATE_Starting; // Undocumented
            case "4": return self::POWERSTATE_Emergency;
            default: return self::POWERSTATE_Unknown;
        }
    }

    public function GetInfo(string $parameter) : string
    {
        try
        {
            $data = $this->ExecuteAdvancedRequest("IF" . $parameter);

            return $data;
        }
        catch (Exception $e) 
        {
            return "FAIL (" . $e->getMessage() . ")";
        }
    }    

    public function GetModel() : string
    {
        $modelCode = $this->ExecuteAdvancedRequest("MD");

        return $this->mappings->GetProjectorModel($modelCode, $modelCode);
    }

    public function GetCurrentInput() : int
    {
        $currentInput = $this->ExecuteAdvancedRequest("IP");

        switch ($currentInput)
        {
            case "0": return self::INPUT_SVideo;
            case "1": return self::INPUT_Video;
            case "2": return self::INPUT_Component;
            case "3": return self::INPUT_PC;
            case "6": return self::INPUT_HDMI1;
            case "7": return self::INPUT_HDMI2;
            default: return self::INPUT_Unknown;
        }
    }

    public function GetSourceStatus() : int
    {
        $sourceStatus = $this->ExecuteAdvancedRequest("SC");

        switch ($sourceStatus)
        {
            case "\x00": return self::SOURCESTATUS_JVCLogo;
            case "0": return self::SOURCESTATUS_NoValidSignal;
            case "1": return self::SOURCESTATUS_Okay;
            default: return self::SOURCESTATUS_Unknown;
        }
    }

    public function GetLampHours() : int
    {
        $lampHours = $this->ExecuteAdvancedRequest("IFLT");

        return hexdec($lampHours);
    }

    public function GetSignal() : string
    {
        $signal = $this->ExecuteAdvancedRequest("IFIS");

        return $this->mappings->GetSignal($signal, self::STRING_Unknown);
    }

    public function GetVersion() : string
    {
        $version = $this->ExecuteAdvancedRequest("IFSV");

        if (preg_match('/\d\d\d\dPJ/', $version))
        {
            return "v" . ltrim(substr($version, 0, 2), "0") . "." . substr($version, 2, 2);
        }

        return $version;
    }

    public function GetMACAddress() : string
    {
        $macAddress = $this->ExecuteAdvancedRequest("LSMA");

        if (strlen($macAddress) == 12)
        {
            return implode(":", str_split($macAddress, 2));
        }

        return $macAddress;
    }

    public function GetColorModel() : string
    {
        $colorSapce = $this->ExecuteAdvancedRequest("IFXV");
        $colorDepth = $this->ExecuteAdvancedRequest("IFDC");

        $colorModel = "Unknown Color Model";

        switch ($colorSapce)
        {
            case "0":
                $colorModel = "RGB";
                break;

            case "1":
                $colorModel = "YUV";
                break;
        }

        switch ($colorDepth)
        {
            case "0":
                $colorModel .= " 8 bit";
                break;

            case "1":
                $colorModel .= " 10 bit";
                break;
    
            case "2":
                $colorModel .= " 12 bit";
                break;
        }

        return $colorModel;
    }

    public function GetColorSpace() : string
    {
        $colorSapce = $this->ExecuteAdvancedRequest("IFCM");
        
        return $this->mappings->GetColorSpace($colorSapce, self::STRING_Unknown);
    }

    public function GetHDRMode() : string
    {
        $hdrMode = $this->ExecuteAdvancedRequest("IFHR");

        return $this->mappings->GetHDRMode($hdrMode, self::STRING_Unknown);
    }

    public function PowerOn()
    {
        $this->ExecuteBasicRequest("PW1");
    }

    public function PowerOff()
    {
        $this->ExecuteBasicRequest("PW0");
    }

    public function SwitchInput(int $input)
    {
        $command = "IP";

        switch ($input)
        {
            case self::INPUT_SVideo:
                $command .= "0";
                break;

            case self::INPUT_Video:
                $command .= "1";
                break;

            case self::INPUT_Component:
                $command .= "2";
                break;

            case self::INPUT_PC:
                $command .= "3";
                break;

            case self::INPUT_HDMI1:
                $command .= "6";
                break;

            case self::INPUT_HDMI2:
                $command .= "7";
                break;

            default:
                throw new Exception("Ungültiger Input: " . $input);
        }

        $this->ExecuteBasicRequest($command);
    }

    public function SetLampPower(bool $high)
    {
        if ($high)
            $this->ExecuteBasicRequest("PMLP1");
        else
            $this->ExecuteBasicRequest("PMLP0");
    }

    private function ReadFromSocket(int $maxBytes = 512) : string
    {
        $data = socket_read($this->socket, $maxBytes, PHP_NORMAL_READ);
        if (!$data)
        {
            $socketError = socket_last_error();
            if ($socketError == SOCKET_ETIMEDOUT)
                throw new Exception("Socket Timeout beim Warten auf Daten");

            // Disconnect on any other error
            $this->Disconnect();
            throw new Exception("Socket Fehler " . $socketError);
        }

        return $data;
    }

    private function ExecuteRequest(string $requestData) : string
    {
        // Send command and wait for answer
        if (0 == socket_write($this->socket, $requestData, strlen($requestData)))
            throw new Exception("Fehler beim Senden von Request: " . socket_strerror(socket_last_error($this->socket)));

        $expectedAnswer = "\x06\x89\x01" . substr($requestData, 3 ,2) . "\x0a";

        $data = $this->ReadFromSocket();
      
        if ($data != $expectedAnswer)
            throw new Exception("Unerwartete Antwort vom Beamer auf Request [" . $requestData . "]: " . $this->ResponseToHexString($data));

        return $data;
    }

    public function ExecuteBasicRequest(string $command) : bool
    {
        if (!$this->isConnected)
            throw new Exception("Socket nicht verbunden");

        $this->ExecuteRequest("!\x89\x01" . $command . "\x0a");

        return true;
    }

    public function ExecuteAdvancedRequest(string $command) : string
    {
        if (!$this->isConnected)
          throw new Exception("Socket nicht verbunden");

        $data = $this->ExecuteRequest("?\x89\x01" . $command . "\n");

        $advancedData = $this->ReadFromSocket();

        $expectedAnswerPrefix = "@\x89\x01" . substr($command, 0, 2);
        if (substr($advancedData, 0, 5) != $expectedAnswerPrefix || substr($advancedData, -1) != "\x0a")
            throw new Exception("Unerwartete erweiterte Antwort vom Beamer auf Request [" . $command . "]: " . $this->ResponseToHexString($advancedData));

        return substr($advancedData, 5, strlen($advancedData) - 6);
    }

    function ResponseToHexString(string $recvData)
    {
        $hexCodes = array();

        $length = strlen($recvData);

        for ($i=0; $i<$length; $i++)
            $hexCodes[] = bin2hex($recvData[$i]);
    
        return implode(" ", $hexCodes);
    }

    public function TranslatePowerState(int $powerState) : string
    {
        switch ($powerState)
        {
            case self::POWERSTATE_Standby: return "Standby";
            case self::POWERSTATE_Starting: return "Starting";
            case self::POWERSTATE_PoweredOn: return "Powered On";
            case self::POWERSTATE_Cooldown: return "Cooldown";
            case self::POWERSTATE_Emergency: return "Emergency";
            case self::POWERSTATE_Unknown: return self::STRING_Unknown;
            default: return "INVALID [" . $powerState . "]";
        }
    }

    function TranslateInput(int $input) : string
    {
        switch ($input)
        {
            case self::INPUT_SVideo: return "S-Video";
            case self::INPUT_Video: return "Video";
            case self::INPUT_Component: return "Component";
            case self::INPUT_PC: return "PC";
            case self::INPUT_HDMI1: return "HDMI #1";
            case self::INPUT_HDMI2: return "HDMI #2";
            case self::INPUT_Unknown: return self::STRING_Unknown;
            default: return "INVALID [" . $input . "]";

        }
    }    

    function TranslateSourceStatus(int $sourceStatus) : string
    {
        switch ($sourceStatus)
        {
            case self::SOURCESTATUS_Unknown: return self::STRING_Unknown;
            case self::SOURCESTATUS_JVCLogo: return "JVC Logo";
            case self::SOURCESTATUS_Okay: return "Okay";
            case self::SOURCESTATUS_NoValidSignal: return "No valid signal";
            default: return "INVALID [" . $sourceStatus . "]";
        }
    }
}

?>
