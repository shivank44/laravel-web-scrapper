<?php

namespace App\Modules;

class ExtractUrl
{
    /**
     * Parse and check the URL Sets the following array parameters
     * scheme, host, port, user, pass, path, query, fragment, dirname, basename, filename, extension, domain, 
     * domainX, absolute address
     * @param string $url of the site
     * @param string $retdata if true then return the parsed URL data otherwise set the $urldata class variable
     * @return array|mixed|boolean
     */
    function parseURL($url,$retdata=true){
        $url = str_replace('www.','',$url);
        $url = substr($url,0,4)=='http'? $url: 'http://'.$url; //assume http if not supplied
        if ($urldata = parse_url(str_replace('&amp;','&',$url))){
            $path_parts = pathinfo($urldata['host']);
            $tmp = explode('.',$urldata['host']); $n = count($tmp);
            if ($n>=2){
                if ($n==4 || ($n==3 && strlen($tmp[($n-2)])<=3)){
                    $urldata['domain'] = $tmp[($n-3)].".".$tmp[($n-2)].".".$tmp[($n-1)];
                    $urldata['tld'] = $tmp[($n-2)].".".$tmp[($n-1)]; //top-level domain
                    $urldata['root'] = $tmp[($n-3)]; //second-level domain
                    $urldata['subdomain'] = ($n==4) ? $tmp[0] : (($n==3 && strlen($tmp[($n-2)])<=3) ? $tmp[0]: '');
                } else {
                    $urldata['domain'] = $tmp[($n-2)].".".$tmp[($n-1)];
                    $urldata['tld'] = $tmp[($n-1)];
                    $urldata['root'] = $tmp[($n-2)];
                    $urldata['subdomain'] = $n==3? $tmp[0]: '';
                }
            }
            //$urldata['dirname'] = $path_parts['dirname'];
            $urldata['basename'] = $path_parts['basename'];
            $urldata['filename'] = $path_parts['filename'];
            $urldata['extension'] = $path_parts['extension'] ?? '';
            $urldata['base'] = $urldata['scheme']."://".$urldata['host'];
            $urldata['abs'] = (isset($urldata['path']) && strlen($urldata['path']))? $urldata['path']: '/';
            $urldata['abs'] .= (isset($urldata['query']) && strlen($urldata['query']))? '?'.$urldata['query']: '';
            $urldata['query'] = (isset($urldata['query']) && strlen($urldata['query']))? '?'.$urldata['query']: '';
            $urldata['fragment'] = (isset($urldata['fragment']) && strlen($urldata['fragment']))? '#'.$urldata['fragment']: '';
            //Set data
            if ($retdata){
                return $urldata;
            } else {
                $this->urldata = $urldata;
                return true;
            }
        } else {
            //invalid URL
            return false;
        }
    }
}