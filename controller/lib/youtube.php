<?php
/**
 * ownCloud - ocDownloader
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author Xavier Beurois <www.sgc-univ.net>
 * @copyright Xavier Beurois 2015
 */

namespace OCA\ocDownloader\Controller\Lib;

class YouTube
{
    private $YTDLBinary = null;
    private $URL = null;
    private $ForceIPv4 = true;
    private $ProxyAddress = null;
    private $ProxyPort = 0;

    private $shellProxy='';
    private $shellIPV4='';

    public function __construct($YTDLBinary, $URL)
    {
        $this->YTDLBinary = $YTDLBinary;
        $this->URL = $URL;
        //youtube multibyte support
        putenv('LANG=en_US.UTF-8');
    }

    public function setForceIPv4($ForceIPv4)
    {
        $this->ForceIPv4 = $ForceIPv4;
        $this->shellIPV4=$this->ForceIPv4?' -4':'';
    }

    public function setProxy($ProxyAddress, $ProxyPort)
    {
        $this->ProxyAddress = $ProxyAddress;
        $this->ProxyPort = $ProxyPort;
        if (!is_null($this->ProxyAddress) && $this->ProxyPort > 0 && $this->ProxyPort <= 65536) {
            $this->shellProxy = ' --proxy ' . rtrim($this->ProxyAddress, '/')
            . ':' . $this->ProxyPort;
        } else {
            $this->shellProxy="";
        }
    }

    public function getVideoData($ExtractAudio = false)
    {
        $output = shell_exec(
            $this->YTDLBinary.' -i \''.$this->URL.'\' --get-url --get-filename --no-playlist'
            .($ExtractAudio?' -f bestaudio -x':' -f best')
            .$this->shellIPV4
            .$this->shellProxy
        );

        return $this->parseOutput(array_shift($output));
    }

    public function getPlaylist($ExtractAudio = false)
    {
        $playlist=array();
        $output = shell_exec(
            $this->YTDLBinary.' -i \''.$this->URL.'\' --get-url --get-filename'
            .($ExtractAudio?' -f bestaudio -x':' -f best')
            .$this->shellIPV4
            .$this->shellProxy
        );

        return $this->parseOutput($output);
    }

    private function parseOutput($output)
    {
        if (!is_null($output)) {
            $output = explode("\n", $output);
            
            if (count($output) >= 2) {
                $result=$OutProcessed = array();

                for ($i = 0; $i < count($output); $i++) {
                    if (mb_strlen(trim($output[$i])) > 0) {
                        $decodedUrl=urldecode($output[$i]);
                        if (strpos($decodedUrl, 'https://') === 0
                                && preg_match('#(\?|&)mime=video/#', $decodedUrl)) {
                            $OutProcessed['VIDEO'] = $output[$i];
                        } elseif (strpos($decodedUrl, 'https://') === 0
                                && preg_match('#(\?|&)mime=audio/#', $decodedUrl)) {
                            $OutProcessed['AUDIO'] = $output[$i];
                        } else {
                            $OutProcessed['FULLNAME'] = $output[$i];
                        }
                    }
                    
                    if ((!empty($OutProcessed['VIDEO']) || !empty($OutProcessed['AUDIO']))
                        && !empty($OutProcessed['FULLNAME'])) {
                        $result[]=$OutProcessed;
                        $OutProcessed=array();
                    }
                }
                return $result;
            }
        }
        return null;
    }
}
