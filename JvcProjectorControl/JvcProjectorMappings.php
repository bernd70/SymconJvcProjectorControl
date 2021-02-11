<?php

declare(strict_types=1);

class JvcProjectorMappings
{
    var $mapProjectorId = array(
        "ILAFPJ -- -XH1" => "DLA-X570R, RS420",
        "ILAFPJ -- -XH3" => "DLA-X770R, X970R, RS520, RS620",
        "ILAFPJ -- -XH4" => "DLA-HD350",
        "ILAFPJ -- -XH5" => "DLA-HD750, RS20",
        "ILAFPJ -- -XH7" => "DLA-RS10",
        "ILAFPJ -- -XH8" => "DLA-HD550",
        "ILAFPJ -- -XH9" => "DLA-HD950, HD990, RS25, RS35",
        "ILAFPJ -- -XHA" => "DLA-RS15",
        "ILAFPJ -- -XHB" => "DLA-X3, RS40",
        "ILAFPJ -- -XHC" => "DLA-X7, X9, RS50, RS60",
        "ILAFPJ -- -XHE" => "DLA-X30, RS45",
        "ILAFPJ -- -XHF" => "DLA-X70R, X90R, RS55, RS65",
        "ILAFPJ -- B2A1" => "DLA-NX9, NX11, V9R, RS3000",
        "ILAFPJ -- B2A2" => "DLA-N7, NX7, N8, V7, RS2000",
        "ILAFPJ -- B2A3" => "DLA-N5, NX5, N6, V5, RS1000");

    var $mapSignal = array(
        "02" => "480p",
        "03" => "576p",
        "04" => "720p50",
        "05" => "720p60",
        "06" => "1080i50",
        "07" => "1080i60",
        "08" => "1080p24",
        "09" => "1080p50",
        "0A" => "1080p60",
        "0B" => "No Signal",
        "0C" => "720p 3D",
        "0D" => "1080i 3D",
        "0E" => "1080p 3D",
        "0F" => "Out of Range",
        "10" => "4K(4096)60",
        "11" => "4K(4096)50",
        "12" => "4K(4096)30",
        "13" => "4K(4096)25",
        "14" => "4K(4096)24",
        "15" => "4K(3840)60",
        "16" => "4K(3840)50",
        "17" => "4K(3840)30",
        "18" => "4K(3840)25",
        "19" => "4K(3840)24",
        "1C" => "1080p25",
        "1D" => "1080p30",
        "1E" => "2048x1080 p24",
        "1F" => "2048x1080 p25",
        "20" => "2048x1080 p30",
        "21" => "2048x1080 p50",
        "22" => "2048x1080 p60",
        "23" => "3840x2160 p120",
        "24" => "4096x2160 p120",
        "25" => "VGA(640x480)",
        "26" => "SVGA(800x600)",
        "27" => "XGA(1024x768)",
        "28" => "SXGA(1280x1024)",
        "29" => "WXGA(1280x768)",
        "2A" => "WXGA+(1440x900)",
        "2B" => "WSXGA+(1680x1050)",
        "2C" => "WUXGA(1920x1200)",
        "2D" => "WXGA(1280x800)",
        "2E" => "FWXGA(1366x768)",
        "2F" => "WXGA++(1600x900)",
        "30" => "UXGA(1600x1200)",
        "31" => "QXGA",
        "32" => "WQXGA");

        var $mapHDRMode = array(
            "0" => "SDR",
            "1" => "HDR",
            "2" => "SMPTEST 2084",
            "F" => "None");        

        var $mapColorSpace = array(
            "0" => "No Data",
            "1" => "BT.601",
            "2" => "BT.709",
            "3" => "xvYCC601",
            "4" => "xvYCC709",
            "5" => "sYCC601",
            "6" => "Adobe YCC601",
            "7" => "Adobe RGB",
            "8" => "BT.2020", // Constant luminance
            "9" => "BT.2020", // Non constant luminance
            "A" => "Reserved (Other)");                    

        function GetProjectorModel(string $key, $default)
        {
            return $this->GetMapping($this->mapProjectorId, $key, $default);
        }

        function GetSignal(string $key, $default)
        {
            return $this->GetMapping($this->mapSignal, $key, $default);
        }

        function GetHDRMode(string $key, $default)
        {
            return $this->GetMapping($this->mapHDRMode, $key, $default);
        }        

        function GetColorSpace(string $key, $default)
        {
            return $this->GetMapping($this->mapColorSpace, $key, $default);
        }        
        
        private function GetMapping(array $map, string $key, $default)
        {
            if (array_key_exists($key, $map))
                return $map[$key];
    
            return $default;
        }           
}