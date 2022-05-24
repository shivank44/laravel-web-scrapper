<?php


namespace App\Modules;

use DOMDocument;
use DOMXPath;
use Error;
use Exception;
use Illuminate\Support\Facades\Log;
use Spatie\Browsershot\Browsershot;

class ScrapUrl
{
    public $parseSentences = [];
    public $wordCount = 0;

    /**
     * @param string $url
     * @return array
     */
    public function getUrldata(string $url): array
    {
        $browsershot = new Browsershot();
        try{
            $url_status = $this->getHttpResponse($url);
            $statuscode = $url_status['statusCode'] ?? 200;
            if($statuscode == 404){
                throw new Exception("URL Not Found");
            }
            if($statuscode == 301){
                $url =$url_status['redirected_url'];
                if(!$url){
                    throw new Exception("Redirected outside domain");
                }
            }
            if($statuscode == 200 || $statuscode == 301){
                $html = $browsershot->url($url)
                ->waitUntilNetworkIdle(false)
                ->timeout(120)
                ->setDelay(300)
                ->bodyHtml();
            }else{
                throw new Exception($url_status['error']);
            }
        }catch(Exception $err){
             throw $err;
        }catch(Error $err){
            throw $err;
        }
        $html = preg_replace('#<script(.*?)>(.*?)</script>#is', '', $html);
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $dom->preserveWhiteSpace = false;

        //remove comment
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//comment()') as $comment) {
            $comment->parentNode->removeChild($comment);
        }
        
        $this->meta_sentences($dom);
        $remove_tag_array = ['script','style','meta','link','title','head'];
        $dom = $this->strip_particular_tag($remove_tag_array,$dom);
        $data = $this->showDOMNode($dom);

        return $data;
    }

    public function strip_particular_tag($tags=[],$dom){
        foreach($tags AS $tag){
            while (($r = $dom->getElementsByTagName($tag)) && $r->length) {
                $r->item(0)->parentNode->removeChild($r->item(0));
            }
            $dom->saveHTML();
        }
        return $dom;
    }

    function showDOMNode($dom) {
        $elements = $dom->getElementsByTagName('body');
        foreach($elements as $node){
            foreach($node->childNodes as $ch) {
                if($ch->hasChildNodes()){
                    $this->childNode($ch);
                }else{
                    $sentence = $ch->nodeValue;
                    $sentence = $this->clean_sentences($sentence);
                    if($sentence && !in_array($sentence,$this->parseSentences)){
                        $this->parseSentences[] = $sentence;
                        $this->wordCount += str_word_count($sentence);
                    }
                }
            }
        }
        return ['sentences' => $this->parseSentences, 'word_count' => $this->wordCount];
    }

    function childNode($children){
        foreach ($children->childNodes as $child){
            if($child->hasChildNodes()){
                $this->childNode($child);
            }else{
                $sentence = $child->nodeValue;
                $sentence = $this->clean_sentences($sentence);

                if($sentence && !in_array($sentence,$this->parseSentences)){
                    $this->parseSentences[] = $sentence;
                    $this->wordCount += str_word_count($sentence);
                }
            }    
        }
        return;
    }

    public function meta_sentences($dom){
        $metas = $dom->getElementsByTagName('meta');
        if($metas->length > 0){
            for ($i = 0; $i < $metas->length; $i++)
            {
                $meta = $metas->item($i);
                if($meta->getAttribute('name') == 'description'){
                    $sentence = $meta->getAttribute('content');
                    $sentence = $this->clean_sentences($sentence);
                    if($sentence && !in_array($sentence,$this->parseSentences)){
                        $this->parseSentences[] = "DOTAMETA_".$sentence;
                        $this->wordCount += str_word_count($sentence);
                    }
                }
                if($meta->getAttribute('name') == 'keywords'){
                    $sentence = $meta->getAttribute('content');
                    $sentence = $this->clean_sentences($sentence);
                    if($sentence && !in_array($sentence,$this->parseSentences)){
                        $this->parseSentences[] = "DOTAMETA_".$sentence;
                        $this->wordCount += str_word_count($sentence);
                    }
                }
            }
        }
    }

    function clean_sentences($sentence){
        $sentence = preg_replace("/<!--.*-->/s", '', $sentence);
        $sentence = preg_replace("~<!--(.*?)-->~s", '', $sentence);
        $sentence = preg_replace('/<!--(.|\s)*?-->/','', $sentence);
        $sentence = trim($sentence);
        return $sentence;
    }

    /**
     * @param string $url
     * @return int
    */
    public function getHttpResponse(string $url): array
    {
        $headers = @get_headers($url);
        Log::info("Header Log On getHttpResponse".json_encode($headers));
        if(!$headers){
            $urldata['statusCode'] = 200;
            $urldata['redirected_url'] = $url;
            $urldata['error'] = "Get Header Fails";
            return $urldata;
        }
        $urldata['statusCode'] = $this->getHttpResponseCode($url,$headers);
        $urldata['redirected_url'] = $this->get_final_url($url,$headers);
        $urldata['error'] = $headers[0];
        return $urldata;
    }

    /**
     * @param string $url
     * @return int
    */
    public function getHttpResponseCode(string $url,$headers=null): int
    {
        $headers = $headers ?? @get_headers($url);
        return substr($headers[0], 9, 3);
    }

     /**
     *
     * @param string $url
     * @return string
     */
    public function get_final_url($url,$headers=null){
        $extract_url = new ExtractUrl();
        $parse_url = $extract_url->parseURL($url);
        $headers = $headers ?? @get_headers($url);
        $final_url = "";
        foreach ($headers as $h)
        {
            if (substr($h,0,10) == 'location: ')
            {
                $final_url = trim(substr($h,10));
                break;
            }elseif (substr($h,0,10) == 'Location: ')
            {
                $final_url = trim(substr($h,10));
                break;
            }
        }
        $parse_url1 = $extract_url->parseURL($final_url);
        $final_url = $parse_url1 ? $final_url : ($parse_url['scheme']."://".$parse_url['host']."".$final_url);
        $parse_url2 = $extract_url->parseURL($final_url);
        if($parse_url2 && ($parse_url2['host'] != $parse_url['host'])){
            return 0;
        }
        return $final_url;
    }  
}
