<?php
/**
*   PHPUnit tests for the Net_Wifi class
*
*   @author Christian Weiske <cweiske@php.net>
*/

require_once 'Net/Wifi.php';
require_once 'PHPUnit.php';

class Net_Wifi_Test extends PHPUnit_TestCase
{
    /**
    *   the Net_Wifi instance
    */
    var $wls = null;
    
    
    
    // constructor of the test suite
    function Net_Wifi_Test($name) {
       $this->PHPUnit_TestCase($name);
    }

    
    
    // called before the test functions will be executed
    // this function is defined in PHPUnit_TestCase and overwritten
    // here
    function setUp() {
        $this->wls = new Net_Wifi();
    }

    
    
    /**
    *   tests the "current config" parser
    */
    function testParseCurrentConfig()
    {
        //not associated
        $strConfig = 
            "eth1      unassociated  ESSID:off/any\r\n" .
            "          Mode:Managed  Channel=0  Access Point: 00:00:00:00:00:00\r\n" .
            "          Bit Rate=0 kb/s   Tx-Power=20 dBm\r\n" .
            "          RTS thr:off   Fragment thr:off\r\n" .
            "          Encryption key:off\r\n" .
            "          Power Management:off\r\n" .
            "          Link Quality:0  Signal level:0  Noise level:0\r\n" .
            "          Rx invalid nwid:0  Rx invalid crypt:0  Rx invalid frag:0\r\n" .
            "          Tx excessive retries:0  Invalid misc:0   Missed beacon:0\r\n";

        $objConfig = $this->wls->parseCurrentConfig($strConfig);
        
        $this->assertEquals( 'net_wifi_config', strtolower(get_class($objConfig)));
        $this->assertFalse ( $objConfig->associated);
        $this->assertTrue  ( $objConfig->activated);
        $this->assertEquals( '00:00:00:00:00:00',   $objConfig->ap);

        
        //associated
        $strConfig = 
            "eth1      IEEE 802.11g  ESSID:\"wlan.informatik.uni-leipzig.de\"  Nickname:\"bogo\"\r\n" .
            "          Mode:Managed  Frequency:2.437 GHz  Access Point: 00:07:40:A0:75:E2   \r\n" .
            "          Bit Rate=54 Mb/s   Tx-Power=20 dBm   \r\n" .
            "          RTS thr:off   Fragment thr:off\r\n" .
            "          Power Management:off\r\n" .
            "          Link Quality=100/100  Signal level=-28 dBm  \r\n" .
            "          Rx invalid nwid:0  Rx invalid crypt:0  Rx invalid frag:0\r\n" .
            "          Tx excessive retries:0  Invalid misc:0   Missed beacon:102\r\n";
        
        $objConfig = $this->wls->parseCurrentConfig($strConfig);
        
        $this->assertTrue  ( $objConfig->associated);
        $this->assertTrue  ( $objConfig->activated);
        $this->assertEquals( '00:07:40:A0:75:E2'                , $objConfig->ap);
        $this->assertEquals( 'wlan.informatik.uni-leipzig.de'   , $objConfig->ssid);
        $this->assertEquals( 'managed'                          , $objConfig->mode);
        $this->assertEquals( 'bogo'                             , $objConfig->nick);
        $this->assertEquals( 54                                 , $objConfig->rate);
        $this->assertEquals( 20                                 , $objConfig->power);
        $this->assertEquals( 'IEEE 802.11g'                     , $objConfig->protocol);
        $this->assertEquals( -28                                , $objConfig->rssi);

        
        //radio off = deactivated interface
        $strConfig =
            "eth1      radio off  ESSID:\"phpconf\"\r\n" .
            "          Mode:Managed  Channel:0  Access Point: 00:00:00:00:00:00\r\n" .
            "          Bit Rate=0 kb/s   Tx-Power=off\r\n" .
            "          RTS thr:off   Fragment thr:off\r\n" .
            "          Power Management:off\r\n" .
            "          Link Quality:0  Signal level:0  Noise level:0\r\n" .
            "          Rx invalid nwid:0  Rx invalid crypt:0  Rx invalid frag:0\r\n" .
            "          Tx excessive retries:0  Invalid misc:6   Missed beacon:0\r\n";
        
        $objConfig = $this->wls->parseCurrentConfig($strConfig);
        $this->assertFalse( $objConfig->associated);
        $this->assertFalse( $objConfig->activated);

    }//function testParseCurrentConfig()
    
    
    
    /**
    *   tests the "parseScan" function which
    *   scans the iwlist output
    */
    function testParseScan()
    {
        //no peers
        $arLines = array("eth1      No scan results");
        $arCells = $this->wls->parseScan($arLines);
        $this->assertEquals( 0, count($arCells));

        //some peers
        //driver: ipw2200 0.21, acer travelmate 6003
        $arLines = array(
            "eth1      Scan completed :",
            "          Cell 01 - Address: 00:02:6F:08:4E:8A",
            "                    ESSID:\"eurospot\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:1",
            "                    Encryption key:off",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11 ",
            "                    Extra: RSSI: -54  dBm ",
            "                    Extra: Last beacon: 8ms ago",
            "          Cell 02 - Address: 00:0F:3D:4B:0D:6E",
            "                    ESSID:\"RIKA\"",
            "                    Protocol:IEEE 802.11g",
            "                    Mode:Master",
            "                    Channel:6",
            "                    Encryption key:on",
            "                    Bit Rate:54 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 9 11 6 12 18 24 36 48 54 ",
            "                    Extra: RSSI: -53  dBm ",
            "                    Extra: Last beacon: 754ms ago",
            "          Cell 03 - Address: 00:0D:BC:50:62:06",
            "                    ESSID:\"skyspeed\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:1",
            "                    Encryption key:off",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11 ",
            "                    Extra: RSSI: -59  dBm ",
            "                    Extra: Last beacon: 544ms ago",
            );
        
        $arCells = $this->wls->parseScan($arLines);
        
        $this->assertEquals( 3                           , count($arCells));
        
        $this->assertEquals( 'net_wifi_cell'             , strtolower(get_class($arCells[0])));
        
        $this->assertEquals( 'string'                    , gettype($arCells[0]->mac));
        $this->assertEquals( 'string'                    , gettype($arCells[0]->ssid));
        $this->assertEquals( 'string'                    , gettype($arCells[0]->mode));
        $this->assertEquals( 'integer'                   , gettype($arCells[0]->channel));
        $this->assertEquals( 'boolean'                   , gettype($arCells[0]->encryption));
        $this->assertEquals( 'string'                    , gettype($arCells[0]->protocol));
        //floatval() should return float and not double...
        $this->assertEquals( 'double'                    , gettype($arCells[0]->rate));
        $this->assertEquals( 'array'                     , gettype($arCells[0]->rates));
        $this->assertEquals( 'integer'                   , gettype($arCells[0]->rssi));
        $this->assertEquals( 'integer'                   , gettype($arCells[0]->beacon));
        
        
        $this->assertEquals( '00:02:6F:08:4E:8A'         , $arCells[0]->mac);
        $this->assertEquals( 'eurospot'                  , $arCells[0]->ssid);
        $this->assertEquals( 'master'                    , $arCells[0]->mode);
        $this->assertEquals( 1                           , $arCells[0]->channel);
        $this->assertEquals( false                       , $arCells[0]->encryption);
        $this->assertEquals( 'IEEE 802.11b'              , $arCells[0]->protocol);
        $this->assertEquals( 11                          , $arCells[0]->rate);
        $this->assertEquals( array(1., 2., 5.5, 11.)     , $arCells[0]->rates);
        $this->assertEquals( -54                         , $arCells[0]->rssi);
        $this->assertEquals( 8                           , $arCells[0]->beacon);

        $this->assertEquals( '00:0F:3D:4B:0D:6E'         , $arCells[1]->mac);
        $this->assertEquals( 'RIKA'                      , $arCells[1]->ssid);
        $this->assertEquals( 'master'                    , $arCells[1]->mode);
        $this->assertEquals( 6                           , $arCells[1]->channel);
        $this->assertEquals( true                        , $arCells[1]->encryption);
        $this->assertEquals( 'IEEE 802.11g'              , $arCells[1]->protocol);
        $this->assertEquals( 54                          , $arCells[1]->rate);
        $this->assertEquals( array(1., 2., 5.5, 6., 9., 11., 12., 18., 24., 36., 48., 54.), $arCells[1]->rates);
        $this->assertEquals( -53                         , $arCells[1]->rssi);
        $this->assertEquals( 754                         , $arCells[1]->beacon);

        $this->assertEquals( '00:0D:BC:50:62:06'         , $arCells[2]->mac);
        $this->assertEquals( 'skyspeed'                  , $arCells[2]->ssid);
        $this->assertEquals( 'master'                    , $arCells[2]->mode);
        $this->assertEquals( 1                           , $arCells[2]->channel);
        $this->assertEquals( false                       , $arCells[2]->encryption);
        $this->assertEquals( 'IEEE 802.11b'              , $arCells[2]->protocol);
        $this->assertEquals( 11                          , $arCells[2]->rate);
        $this->assertEquals( array(1., 2., 5.5, 11.)     , $arCells[2]->rates);
        $this->assertEquals( -59                         , $arCells[2]->rssi);
        $this->assertEquals( 544                         , $arCells[2]->beacon);

        
        //some other peers
        //driver: ipw2100 ???, samsung x10
        $arLines = array(
            "eth2      Scan completed :",
            "          Cell 01 - Address: 00:40:05:28:EB:45",
            "                    ESSID:\"default\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:6",
            "                    Encryption key:on",
            "                    Bit Rate:22 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11 22",
            "                    Extra: Signal: -88  dBm",
            "                    Extra: Last beacon: 747642ms ago",
            "          Cell 02 - Address: 00:30:F1:C8:E4:FB",
            "                    ESSID:\"Alien\"",
            "                    Protocol:IEEE 802.11g",
            "                    Mode:Master",
            "                    Channel:8",
            "                    Encryption key:on",
            "                    Bit Rate:54 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 6 9 11 12 18 24 36 48 54",
            "                    Extra: Signal: -84  dBm",
            "                    Extra: Last beacon: 1872456ms ago",
            "          Cell 03 - Address: 00:09:5B:2B:5F:74",
            "                    ESSID:\"Wireless\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:10",
            "                    Encryption key:on",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11",
            "                    Extra: Signal: -48  dBm",
            "                    Extra: Last beacon: 27631ms ago"
        );
        
        $arCells = $this->wls->parseScan($arLines);
        
        $this->assertEquals( 3                           , count($arCells));
        
        $this->assertEquals( '00:40:05:28:EB:45'         , $arCells[0]->mac);
        $this->assertEquals( 'default'                   , $arCells[0]->ssid);
        //different signal name
        $this->assertEquals( -88                         , $arCells[0]->rssi);
        $this->assertEquals( 747642                      , $arCells[0]->beacon);
        
        
        //with ipw2200 1.0 we've got "Signal level=..." instead of "Extra: Signal"
        $arLines = array(
            "eth1      Scan completed :",
            "  Cell 01 - Address: 00:03:C9:44:34:2C",
            "            ESSID:\"<hidden>\"",
            "            Protocol:IEEE 802.11bg",
            "            Mode:Master",
            "            Channel:5",
            "            Encryption key:on",
            "            Bit Rate:54 Mb/s",
            "            Extra: Rates (Mb/s): 1 2 5.5 6 9 11 12 18 24 36 48 54",
            "            Signal level=-51 dBm",
            "            Extra: Last beacon: 9ms ago"
        );

        $arCells = $this->wls->parseScan($arLines);
        
        $this->assertEquals( 1                           , count($arCells));
        
        $this->assertEquals( '00:03:C9:44:34:2C'         , $arCells[0]->mac);
        $this->assertEquals( '<hidden>'                  , $arCells[0]->ssid);
        //different signal name
        $this->assertEquals( -51                         , $arCells[0]->rssi);
        $this->assertEquals( 9                           , $arCells[0]->beacon);
        
        
        //ipw2200 1.0.1
        $arLines = array(
            "eth1      Scan completed :",
            "          Cell 01 - Address: 00:0D:BC:68:28:1A",
            "                    ESSID:\"Rai Private\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:1",
            "                    Encryption key:off",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11",
            "                    Quality=67/100  Signal level=-60 dBm",
            "                    Extra: Last beacon: 59ms ago",
            "          Cell 02 - Address: 00:0D:BC:68:28:05",
            "                    ESSID:\"Rai Private\"",
            "                    Protocol:IEEE 802.11b",
            "                    Mode:Master",
            "                    Channel:6",
            "                    Encryption key:off",
            "                    Bit Rate:11 Mb/s",
            "                    Extra: Rates (Mb/s): 1 2 5.5 11",
            "                    Quality=39/100  Signal level=-77 dBm",
            "                    Extra: Last beacon: 11ms ago"
        );

        $arCells = $this->wls->parseScan($arLines);
        
        $this->assertEquals( 2                          , count($arCells));
        
        $this->assertEquals( '00:0D:BC:68:28:1A'        , $arCells[0]->mac);
        $this->assertEquals( 'Rai Private'              , $arCells[0]->ssid);
        $this->assertEquals( -60                        , $arCells[0]->rssi);
        $this->assertEquals( 59                         , $arCells[0]->beacon);
        
        
        
        //ipw2100 carsten (unknown version)
        $arLines = array(
            'eth1      Scan completed :',
            '          Cell 01 - Address: 00:12:D9:AC:BD:00',
            '                    ESSID:"Rai Wireless"',
            '                    Mode:Master',
            '                    Frequency:2.412GHz',
            '                    Bit Rate:1Mb/s',
            '                    Bit Rate:2Mb/s',
            '                    Bit Rate:5.5Mb/s',
            '                    Bit Rate:6Mb/s',
            '                    Bit Rate:9Mb/s',
            '                    Bit Rate:11Mb/s',
            '                    Bit Rate:12Mb/s',
            '                    Bit Rate:18Mb/s',
            '                    Quality:12/100  Signal level:-86 dBm  Noise level:-98 dBm',
            '                    Encryption key:off'
        );
        
        $arCells = $this->wls->parseScan($arLines);
        
        $this->assertEquals( 1                          , count($arCells));
        $this->assertEquals( '00:12:D9:AC:BD:00'        , $arCells[0]->mac);
        $this->assertEquals( 'Rai Wireless'             , $arCells[0]->ssid);
        $this->assertEquals( 'master'                   , $arCells[0]->mode);
        $this->assertEquals( -86                        , $arCells[0]->rssi);
        $this->assertEquals( '2.412GHz'                 , $arCells[0]->frequency);
        $this->assertEquals( 18                         , $arCells[0]->rate);
        $this->assertEquals( array(1.,2.,5.5,6.,9.,11.,12.,18.), $arCells[0]->rates);
        $this->assertEquals( false                      , $arCells[0]->encryption);
        

    }//function testParseScan()
    
}//class Net_Wifi_Test extends PHPUnit_TestCase
?>