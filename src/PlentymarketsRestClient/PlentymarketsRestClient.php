<?php

namespace repat\PlentymarketsRestClient;

use Carbon\Carbon;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use Stringy\Stringy as s;

class PlentymarketsRestClient
{
    const PATH_LOGIN = 'rest/login';
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';
    const THROTTLING_PREFIX_LONG_PERIOD = 'X-Plenty-Global-Long-Period';
    const THROTTLING_PREFIX_SHORT_PERIOD = 'X-Plenty-Global-Short-Period';
    const THROTTLING_PREFIX_ROUTE = 'X-Plenty-Route';

    private $client;
    private $config;
    private $configFile;
    private $rateLimitingEnabled = true;
    private $throttledOnLastRequest = false;

    public function __construct($configFile, $config)
    {
        $this->client = new Client();
        $this->config = $config;

        if (!file_exists($configFile)) {
            $this->configFile = $configFile;
            $this->saveConfigFile();
        }

        $this->setConfigFile($configFile);

        if (!$this->isAccessTokenValid()) {
            $this->login();
        }
    }

    public function getRateLimitingEnabled()
    {
        return $this->rateLimitingEnabled;
    }

    public function setRateLimitingEnabled($rateLimitingEnabled)
    {
        $this->rateLimitingEnabled = $rateLimitingEnabled;
        return $this;
    }

    public function getThrottledOnLastRequest()
    {
        return $this->throttledOnLastRequest;
    }

    public function singleCall($method, $path, $params = [])
    {
        $path = ltrim($path, '/');

        if (!($path == self::PATH_LOGIN)) {
            $params = array_merge($params, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config['access_token'],
                ],
            ]);
        }

        try {
            /* @var $response ResponseInterface */
            $response = $this->client->request($method, $this->config['url'] . $path, $params);
        } catch (\Exception $e) {
            return false;
        }

        $this->throttledOnLastRequest = false;

        if ($this->rateLimitingEnabled) {
            $this->handleRateLimiting($response);
        }

        return json_decode($response->getBody(), true);
    }

    public function get($path, $array = [])
    {
        return $this->singleCall(self::METHOD_GET, $path, ['query' => $array]);
    }

    public function post($path, $array = [])
    {
        return $this->singleCall(self::METHOD_POST, $path, ['json' => $array]);
    }

    public function put($path, $array = [])
    {
        return $this->singleCall(self::METHOD_PUT, $path, ['json' => $array]);
    }

    public function delete($path, $array = [])
    {
        return $this->singleCall(self::METHOD_DELETE, $path, ['json' => $array]);
    }

    private function isAccessTokenValid()
    {
        if (!in_array('valid_until', $this->config)) {
            return false;
        }
        return Carbon::parse($this->config['valid_until'])->gt(Carbon::now());
    }

    private function login()
    {
        $response = $this->singleCall(self::METHOD_POST, self::PATH_LOGIN, [
            'form_params' => [
                'username' => $this->config['username'],
                'password' => $this->config['password'],
            ],
        ]);

        $this->config['access_token'] = $response['accessToken'];
        $this->config['valid_until'] = Carbon::now()->addSeconds($response['expiresIn'])->toDateTimeString();

        $this->saveConfigFile();
    }

    private function saveConfigFile()
    {
        file_put_contents($this->configFile, serialize($this->config));
    }

    private function correctURL($url)
    {
        $sUrl = new s($url);

        if (!($sUrl->contains('https'))) {
            $url = str_replace('http://', 'https://.', $url);
        }

        $url = rtrim($url, '/') . '/';

        return $url;
    }

    private function setConfigFile($configFile)
    {
        $this->configFile = $configFile;

        if (!file_exists($configFile)) {
            throw new \Exception('config file does not exists.');
        }

        $this->config = unserialize(file_get_contents($this->configFile));

        if (!array_key_exists('username', $this->config)
                        || !array_key_exists('password', $this->config)
                        || !array_key_exists('url', $this->config)) {
            throw new \Exception('username and/or password and/or url not in config(file)');
        }

        $this->config['url'] = $this->correctURL($this->config['url']);

        $this->saveConfigFile();
    }

    private function handleRateLimiting(ResponseInterface $response)
    {
        $prefixes = [
                        self::THROTTLING_PREFIX_LONG_PERIOD,
                        self::THROTTLING_PREFIX_SHORT_PERIOD,
                        self::THROTTLING_PREFIX_ROUTE
                    ];

        $throttled = 0;

        foreach ($prefixes as $prefix) {
            $throttled += $this->handleThrottling($response, $prefix, $throttled);
        }
    }

    private function handleThrottling(ResponseInterface $response, $prefix, $throttled = 0)
    {
        $callsLeft = $response->getHeader($prefix . '-Calls-Left');
        $decay =  $response->getHeader($prefix . '-Decay');

        if (count($callsLeft) < 1 || count($decay) < 1) {
            return 0;
        }

        if ($callsLeft[0] < 1 && $decay[0] > $throttled) {
            sleep($decay[0] - $throttled);
            $this->throttledOnLastRequest = true;
            return $decay[0];
        }

        return 0;
    }
}
