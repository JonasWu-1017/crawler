<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Debug;

use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use Spatie\Crawler\CrawlObservers\CrawlObserver;
use Spatie\Crawler\Crawler;
use Spatie\Browsershot\Browsershot;

class CrawlLogger extends CrawlObserver
{
    protected string $observerId;
    private $internal_pages = [];
    private $page = [];
    public function __construct(string $observerId = '')
    {
        if ($observerId !== '') {
            $observerId .= ' - ';
        }

        $this->observerId = $observerId;
    }

    /**
     * Called when the crawler will crawl the url.
     *
     * @param \Psr\Http\Message\UriInterface   $url
     */
    public function willCrawl(UriInterface $url): void
    {
        Debug::out(__FILE__, __LINE__, __FUNCTION__, "{$this->observerId}willCrawl: {$url}");
    }

    /**
     * Called when the crawler has crawled the given url.
     *
     * @param \Psr\Http\Message\UriInterface $url
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param \Psr\Http\Message\UriInterface|null $foundOnUrl
     */
    public function crawled(
        UriInterface $url,
        ResponseInterface $response,
        ?UriInterface $foundOnUrl = null
    ): void {

        try {
            $path = $url->getPath();
            //if ('/' == $path && false === isset($this->page['title'])) {
            if ('/' == $path) {
                libxml_use_internal_errors(true);
                $doc = new \DOMDocument();
                $body = $response->getBody();
                if (true !== empty($body))
                {
                    $doc->loadhtml($body);
                    //Debug::out(__FILE__, __LINE__, __FUNCTION__, $doc);
                    $title = $doc->getElementsByTagName("title")[0]->nodeValue;
                    if (0 != strncmp($title, '301 ', strlen('301 ')) && 0 !== strncmp($title, '302 ', strlen('302 '))) {
                        $metas = $doc->getElementsByTagName('meta');
                        for ($i = 0; $i < $metas->length; $i++)
                        {
                            //meta tags
                            $meta = $metas->item($i);
                            //description.
                            if($meta->getAttribute('name') == 'description')
                                $description = $meta->getAttribute('content');
                        }
                        
                        $mock = new \DOMDocument;
                        $body = $doc->getElementsByTagName('body')->item(0);
                        foreach ($body->childNodes as $child){
                            $mock->appendChild($mock->importNode($child, true));
                        }
                        //Debug::out(__FILE__, __LINE__, __FUNCTION__, $mock->saveHTML());
                        
                        $this->page = [
                            'url' => $url,
                            'title' => $title,
                            'description' => (true !== empty($description))?$description:'',
                            'body' => $mock->saveHTML()
                        ];
                        $this->internal_pages[] = $this->page;
                    }
                }
            }
        } catch(\Exception $e) {
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $e->getMessage());
        }
        
        $this->logCrawl($url, $foundOnUrl);
        Debug::out(__FILE__, __LINE__, __FUNCTION__, $url);
    }

    public function crawlFailed(
        UriInterface $url,
        RequestException $requestException,
        ?UriInterface $foundOnUrl = null
    ): void {    
        $this->logCrawl($url, $foundOnUrl);
        Debug::out(__FILE__, __LINE__, __FUNCTION__, $url);
    }

    protected function logCrawl(UriInterface $url, ?UriInterface $foundOnUrl)
    {
        $logText = "{$this->observerId}hasBeenCrawled: {$url}";

        if ((string) $foundOnUrl) {
            $logText .= " - found on {$foundOnUrl}";
        }

        Debug::out(__FILE__, __LINE__, __FUNCTION__, $logText);
    }

    /**
     * Called when the crawl has ended.
     */
    public function finishedCrawling(): void
    {
        Debug::out(__FILE__, __LINE__, __FUNCTION__, "{$this->observerId}finished crawling");
        $index=0;
        foreach ($this->internal_pages as $p){
            Debug::out(__FILE__, __LINE__, __FUNCTION__, 
                sprintf("[%d] %s/%s/%s/%d", 
                    $index, 
                    (isset($p['url']))?$p['url']:'', 
                    (isset($p['title']))?$p['title']:'', 
                    (isset($p['description']))?$p['description']:'', 
                    (isset($p['body']))?strlen($p['body']):0
                )
            );
            $index++;
        }
    }

    public function getResult(): array {
        return $this->page;
    }
}

class BrowsershotController extends Controller
{
    function page_information($url):string {
        $ret = '';
        try {
            $istwitter = str_contains($url, "twitter.com");
            $logger = new CrawlLogger();
            Crawler::create()
                ->ignoreRobots()
                ->setMaximumDepth(($istwitter)?4:1)
                ->setCurrentCrawlLimit(($istwitter)?50:2)
                ->setDelayBetweenRequests(500) // 500ms
                ->doNotExecuteJavaScript()
                ->setCrawlObserver($logger)
                ->startCrawling($url);
            $array = $logger->getResult();
            $ret =  sprintf("website:%s, title=%s, description=%s, length of body=%d",
                        $url, 
                        (isset($array['title']))?$array['title']:'',
                        (isset($array['description']))?$array['description']:'',
                        (isset($array['body']))?strlen($array['body']):0
                    );
            file_put_contents(config('filesystems.disks.public.root') . '/' . base64_encode($url) . '.txt', json_encode($array));
            //echo $array['body'];
        } catch(\Exception $e) {
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $e->getMessage());
            $ret = sprintf("%s is Not accessible. (%s)", $url, $e->getMessage());
        }
        return $ret;
    }
    /*
    function get_content($URL){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $URL);
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }
    */
    function screenshot() {
        Debug::out(__FILE__, __LINE__, __FUNCTION__, $_SERVER['QUERY_STRING']);
        parse_str($_SERVER['QUERY_STRING'], $params);
        Debug::out(__FILE__, __LINE__, __FUNCTION__, $params);
        $url = (!empty($params['url']))?$params['url']:'';
        $force = (!empty($params['force']))?$params['force']:'0';
        if (empty($url)) {
            //echo sprintf("website needs!");
            $resp = config('response.ErrorHttpHeader');
            return response()->json($resp);
        }        
        try {
            ini_set('default_socket_timeout', 10);            
            $header = get_headers($url);
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $header);
            $body = '';
            if (false !== array_search("HTTP/1.1 200 OK", $header, true)) {
                $index = array_search("HTTP/1.1 301 Moved Permanently", $header, true);
                if (false !== $index) {
                    for($i=$index+1;$i<count($header);$i++) {
                        if (str_contains($header[$i], "Location: ")) {
                            $location = substr($header[$i], strlen("Location: "));
                            break;
                        }
                    }
                }
                else 
                    $location = $url;
                /*
                libxml_use_internal_errors(true);
                $d = new \DOMDocument;
                $mock = new \DOMDocument;                
                $d->loadhtml($this->get_content($location));
                $body = $d->getElementsByTagName('body')->item(0);
                foreach ($body->childNodes as $child){
                    $mock->appendChild($mock->importNode($child, true));
                }
                $body = $mock->saveHTML();
                Debug::out(__FILE__, __LINE__, __FUNCTION__, $body);
                */
                Debug::out(__FILE__, __LINE__, __FUNCTION__, $location);
                Debug::out(__FILE__, __LINE__, __FUNCTION__, base64_encode($location));
                $array = null;
                if ('0' === $force) {
                    if (file_exists(config('filesystems.disks.public.root') . '/' . base64_encode($location) . '.jpg')) {
                        $array = json_decode(file_get_contents(config('filesystems.disks.public.root') . '/' . base64_encode($location) . '.txt'), true);                        
                        $info =  sprintf("website:%s, title=%s, description=%s, length of body=%d",
                                    $location, 
                                    (isset($array['title']))?$array['title']:'',
                                    (isset($array['description']))?$array['description']:'',
                                    (isset($array['body']))?strlen($array['body']):0
                                );
                        $array['display'] = config('filesystems.disks.public.url') . '/' . base64_encode($location) . '.jpg';
                    }
                } else {
                    $info = $this->page_information($location);
                    Browsershot::url($location)
                        ->setOption('landscape', true)
                        ->windowSize(1920, 1080)
                        ->waitUntilNetworkIdle()
                        ->save(config('filesystems.disks.public.root') . '/' . base64_encode($location) . '.jpg');
                    $array = json_decode(file_get_contents(config('filesystems.disks.public.root') . '/' . base64_encode($location) . '.txt'), true);
                    $array['display'] = config('filesystems.disks.public.url') . '/' . base64_encode($location) . '.jpg';
                }
                Debug::out(__FILE__, __LINE__, __FUNCTION__, $array);
                $resp = config('response.Success');
                $resp += ['data' => $array];
                $ret = sprintf("%s is OK. (%s)", $url, $info);                
            }
            else {
                $ret = sprintf("%s is NOT accessible. (failed to get_headers)", $url);
                $resp = config('response.ErrorWebsite');
            }
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $ret);
            //echo $ret;            
        } catch(\Exception $e) {
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $e->getMessage());
            //echo sprintf("%s is NOT valid url. (%s)", $url, $e->getMessage());
            $resp = config('response.Exception');
            $resp += ['data' => $e->getMessage()];
        }
        Debug::out(__FILE__, __LINE__, __FUNCTION__, $resp);
        return response()->json($resp);
    }
}
