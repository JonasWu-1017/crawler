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
            if ('/' == $path) {
                libxml_use_internal_errors(true);
                $doc = new \DOMDocument();
                $doc->loadhtml($response->getBody());
                //Debug::out(__FILE__, __LINE__, __FUNCTION__, $doc);
                $title = $doc->getElementsByTagName("title")[0]->nodeValue;
                if (0 != strncmp($title, '301', strlen('301'))) {
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
                        'path'=>$path,
                        'title'=> $title,
                        'description' => (isset($description))?$description:'',
                        'body' => $response->getBody()
                    ];
                }
            }
        } catch(\Exception $e) {
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $e);
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
        echo sprintf("%s, %s, %s, %d\n", $this->page['path'], $this->page['title'], $this->page['description'], strlen($this->page['body']));
        Debug::out(__FILE__, __LINE__, __FUNCTION__, sprintf("%s, %s, %s, %d\n", $this->page['path'], $this->page['title'], $this->page['description'], strlen($this->page['body'])));
        Debug::out(__FILE__, __LINE__, __FUNCTION__, "{$this->observerId}finished crawling");
    }

    public function getResult(): string {
        return sprintf("%s, %s, %s, %d\n", $this->page['path'], $this->page['title'], $this->page['description'], strlen($this->page['body']));
    }
}

class BrowsershotController extends Controller
{
    function page_title($url):string {
        $ret = '';
        try {
            $logger = new CrawlLogger();
            Crawler::create()
                ->ignoreRobots()
                ->setMaximumDepth(1)
                ->setCurrentCrawlLimit(3)
                ->setDelayBetweenRequests(500) // 500ms
                ->doNotExecuteJavaScript()
                ->setCrawlObserver($logger)
                ->startCrawling($url);
            $ret = $logger->getResult();
        } catch(\Exception $e) {
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $e);
        }
        return $ret;
    }
    function screenshot() {
        Debug::out(__FILE__, __LINE__, __FUNCTION__, $_SERVER['QUERY_STRING']);
        try {
            $ret = Browsershot::url($_SERVER['QUERY_STRING'])
                    ->setOption('landscape', true)
                    ->windowSize(1920, 1080)
                    ->waitUntilNetworkIdle()
                    ->save(config('filesystems.disks.public.root') . '/screenshot.jpg');
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $this->page_title($_SERVER['QUERY_STRING']));
        } catch(\Exception $e) {
            Debug::out(__FILE__, __LINE__, __FUNCTION__, $e);
        }
    }
}
