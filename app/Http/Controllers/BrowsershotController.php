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
                    $metas = $doc->getElementsByTagName('meta');
                    for ($i = 0; $i < $metas->length; $i++)
                    {
                        //meta tags
                        $meta = $metas->item($i);
                        //description.
                        if($meta->getAttribute('name') == 'description')
                            $description = $meta->getAttribute('content');
                    }

                    $this->page = [
                        'url' => $url,
                        'title' => $title,
                        'description' => (true !== empty($description))?$description:'',
                        'body' => $body
                    ];
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
        //echo sprintf("%s, %s, %s, %d\n", $this->page['path'], $this->page['title'], $this->page['description'], strlen($this->page['body']));
        //Debug::out(__FILE__, __LINE__, __FUNCTION__, sprintf("%s, %s, %s, %d\n", $this->page['path'], $this->page['title'], $this->page['description'], strlen($this->page['body'])));
        Debug::out(__FILE__, __LINE__, __FUNCTION__, "{$this->observerId}finished crawling");
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
                ->setMaximumDepth(($istwitter)?3:1)
                ->setCurrentCrawlLimit(($istwitter)?50:2)
                ->setDelayBetweenRequests(500) // 500ms
                ->doNotExecuteJavaScript()
                ->setCrawlObserver($logger)
                ->startCrawling($url);
            $array = $logger->getResult();
            $ret = sprintf("website:%s, title=%s, description=%s, length of body=%d",
                        $url, (isset($array['title']))?$array['title']:'', (isset($array['description']))?$array['description']:'', (isset($array['body']))?strlen($array['body']):0);
        } catch(\Exception $e) {
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $e->getMessage());
            $ret = sprintf("%s is Not accessible. (%s)", $url, $e->getMessage());
        }
        return $ret;
    }
    function screenshot() {
        Debug::out(__FILE__, __LINE__, __FUNCTION__, $_SERVER['QUERY_STRING']);        
        try {
            ini_set('default_socket_timeout', 10);
            $url = $_SERVER['QUERY_STRING'];
            $header = get_headers($url);
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $header);
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
                Debug::out(__FILE__, __LINE__, __FUNCTION__, $location);
                $info = $this->page_information($location);
                $ret = Browsershot::url($location)
                        ->setOption('landscape', true)
                        ->windowSize(1920, 1080)
                        ->waitUntilNetworkIdle()
                        ->save(config('filesystems.disks.public.root') . '/screenshot.jpg');
                $ret = sprintf("%s is OK. (%s)", $url, $info);
            }
            else {
                $ret = sprintf("%s is NOT accessible. (failed to get_headers)", $url);
            }
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $ret);
            echo $ret;
        } catch(\Exception $e) {
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $e->getMessage());
            echo sprintf("%s is NOT valid url. (%s)", $url, $e->getMessage());
        }
    }
}
