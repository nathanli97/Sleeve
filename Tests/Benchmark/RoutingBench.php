<?php

namespace Sleeve\Tests\Benchmark;

use Sleeve\Request;
use Sleeve\SleeveRouter;

/**
 * The main class of benchmarking
 */
class RoutingBench
{
    protected SleeveRouter $router;
    private Request $request;
    /**
     * @var string[]
     */
    private array $requests;
    private int $i;

    /**
     * The constructor.
     */
    public function __construct()
    {
        $this->i = 0;
        $this->router = new SleeveRouter();
        $this->requests = array(
            '/',
            '/test/520-555-5542',
            '/get_user/aka/info',
            '/system/power_management_s/520-555-5542',
            '/?load=true',
            '/test?kav=1234',
            '/get_user/aka/info?djsfg=23bjhsy&&jasdhfb=32847',
            '/system/power_management?shutdown=yes',
            '/udshskj/ioashj/sdlfkjn?sdhjfgsduhvf',
            '/asjkidgashbd',
            'askjdgasyhgdb/sdfguisf/sdffssd/qwe48edwn9065dx',
            'usdg/qkwehr89sdkjhbds/sdfugweb?skjfgsdhfsd',
        );
        $this->request = new Request();
        $this->router->respond('get', '/\d+', function (Request $request) {
            return 'GET /';
        });
        $this->router->respond('get', '/test/(?:1(?:[. -])?)?(?:\((?=\d{3}\)))?
        ([2-9]\d{2})(?:(?<=\(\d{3})\))? ?(?:(?<=\d{3})[.-])?([2-9]\d{2})[. -]?(\d{4})(?: 
        (?i:ext)\.? ?(\d{1,5}))?', function (Request $request) {
            return 'GET /test';
        });
        $this->router->respond('get', '/get_user/aka/info', function (Request $request) {
            return 'GET /get_user/aka/info';
        });
        $this->router->respond('get', '/system/power_management_s(?:1(?:[. -])?)?
        (?:\((?=\d{3}\)))?([2-9]\d{2})(?:(?<=\(\d{3})\))? ?(?:(?<=\d{3})[.-])?([2-9]\d{2})[. -]?
        (\d{4})(?: (?i:ext)\.? ?(\d{1,5}))?', function (Request $request) {
            return 'GET /system/power_management';
        });
        $this->router->respond('post', '/abc\d+', function (Request $request) {
            return 'GET /';
        });
        $this->router->respond('get', '/ttest(?:1(?:[. -])?)?(?:\((?=\d{3}\)))?
        ([2-9]\d{2})(?:(?<=\(\d{3})\))? ?(?:(?<=\d{3})[.-])?([2-9]\d{2})[. -]?(\d{4})
        (?: (?i:ext)\.? ?(\d{1,5}))?', function (Request $request) {
            return 'GET /test';
        });
        $this->router->respond('get', '/get_user/aka/info(?:1(?:[. -])?)?
        (?:\((?=\d{3}\)))?([2-9]\d{2})(?:(?<=\(\d{3})\))? ?(?:(?<=\d{3})[.-])?([2-9]\d{2})
        [. -]?(\d{4})(?: (?i:ext)\.? ?(\d{1,5}))?', function (Request $request) {
            return 'GET /get_user/aka/info';
        });
        $this->router->respond('head', '/system/power_management/\d?', function (Request $request) {
            return 'GET /system/power_management';
        });
    }
    /**
     * The bench function.
     * @Revs(3000)
     * @return void
     */
    public function benchRouting(): void
    {
        $this->request->url = $this->requests[$this->i++];
        $this->router->dispatch($this->request, $response, false);
        if ($this->i == 12) {
            $this->i = 0;
        }
    }
}
