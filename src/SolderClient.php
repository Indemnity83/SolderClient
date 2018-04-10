<?php

/*
 * This file is part of TechnicPack Solder.
 *
 * (c) Syndicate LLC
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace TechnicPack\Solder;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use TechnicPack\Solder\Resources\Build;
use TechnicPack\Solder\Resources\Modpack;
use GuzzleHttp\Exception\RequestException;
use TechnicPack\Solder\Exception\BadJSONException;
use TechnicPack\Solder\Exception\ResourceException;
use TechnicPack\Solder\Exception\ConnectionException;
use TechnicPack\Solder\Exception\InvalidURLException;
use TechnicPack\Solder\Exception\UnauthorizedException;

class SolderClient
{
    public $url;
    public $key;

    /** @var Client */
    private $client;

    const VERSION = '0.1.4';

    public static function factory($url, $key, $headers = [], $handler = null, $timeout = 3)
    {
        $url = self::validateUrl($url);

        $client = new Client([
            'base_uri' => $url,
            'timeout' => $timeout,
            'headers' => self::prepareHeaders($headers),
            'handler' => $handler ?: HandlerStack::create(),
        ]);

        if (! self::validateKey($client, $key)) {
            throw new UnauthorizedException('Key failed to validate.', 403);
        }

        return new self($client, [
            'url' => $url,
            'key' => $key,
        ]);
    }

    protected function __construct($client, $properties)
    {
        $this->client = $client;
        foreach ($properties as $key => $val) {
            $this->{$key} = $val;
        }
    }

    protected static function prepareHeaders($headers)
    {
        $headers = array_merge([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'User-Agent' => 'SolderClient/'.self::VERSION,
        ], $headers);

        return $headers;
    }

    private function handle($endpoint, $params = [])
    {
        $uri = $this->buildUri($endpoint, $params);

        try {
            $response = $this->client->get($uri);
        } catch (RequestException $e) {
            throw new ConnectionException('Request to \''.$uri.'\' failed. '.$e->getMessage(), 0, $e);
        }

        $status_code = $response->getStatusCode();
        if ($status_code >= 300) {
            throw new ConnectionException('Request to \''.$uri.'\' failed. '.$response->getReasonPhrase(), $status_code);
        }

        if (null === $json = json_decode($response->getBody(), true)) {
            throw new BadJSONException('Failed to decode JSON for \''.$uri.'\'', 500);
        }

        return $json;
    }

    public function getModpackNames()
    {
        $response = $this->handle('modpack');

        if ($this->arrayMissing($response, 'modpacks')) {
            throw new ResourceException('Got an unexpected response from Solder', 500);
        }

        return $response['modpacks'];
    }

    public function getModpacks($recursive = false)
    {
        if (! $recursive) {
            return $this->getModpackNames();
        }

        $response = $this->handle('modpack', ['include' => 'full']);

        if ($this->arrayMissing($response, 'modpacks')) {
            throw new ResourceException('Got an unexpected response from Solder', 500);
        }

        return collect($response['modpacks'])->map(function ($modpack) {
            new Modpack($modpack);
        });
    }

    public function getModpack($modpackSlug)
    {
        $response = $this->handle("modpack/$modpackSlug");

        if (array_key_exists('error', $response) || array_key_exists('status', $response)) {
            if ($response['error'] == 'Modpack does not exist' || $response['status'] == '404') {
                throw new ResourceException('Modpack does not exist', 404);
            } elseif ($response['error'] == 'You are not authorized to view this modpack.' || $response['status'] == '401') {
                throw new UnauthorizedException('You are not authorized to view this modpack.', 401);
            } else {
                throw new ResourceException('Got an unexpected response from Solder', 500);
            }
        }

        return new Modpack($response);
    }

    public function getBuild($modpackSlug, $buildVersion)
    {
        $response = $this->handle("modpack/$modpackSlug/$buildVersion", ['include' => 'mods']);

        if (array_key_exists('error', $response) || array_key_exists('status', $response)) {
            if ($response['error'] == 'Build does not exist' || $response['status'] == '404') {
                throw new ResourceException('Build does not exist', 404);
            } elseif ($response['error'] == 'You are not authorized to view this build.' || $response['status'] == '401') {
                throw new UnauthorizedException('You are not authorized to view this build.', 401);
            } else {
                throw new ResourceException('Got an unexpected response from Solder', 500);
            }
        }

        return new Build($response);
    }

    public static function validateUrl($url)
    {
        $url = rtrim($url, '/').'/';

        if (! preg_match("/\/api\/?$/", $url)) {
            throw new InvalidURLException('You must include api/ at the end of your URL');
        }

        return $url;
    }

    public static function validateKey(Client $client, $key)
    {
        try {
            $response = $client->get("verify/$key");
        } catch (RequestException $e) {
            if ($e->hasResponse()) {
                throw new ConnectionException('Request to verify Solder API failed. Solder API returned HTTP code '.$e->getResponse()->getStatusCode(), 0, $e);
            } else {
                throw new ConnectionException('Request to verify Solder API failed. Solder API returned '.$e->getMessage(), 0, $e);
            }
        }

        $json = json_decode($response->getBody(), true);

        if ($json === null) {
            throw new BadJSONException('Failed to decode JSON response when verifying API key');
        }

        return array_key_exists('valid', $json);
    }

    private function buildUri($endpoint, $params)
    {
        $params = array_merge([
            'k' => $this->key,
        ], $params);

        return $endpoint.'?'.http_build_query($params);
    }

    private function arrayHas($array, $key)
    {
        if (! is_array($array)) {
            return false;
        }

        if (! array_key_exists($key, $array)) {
            return false;
        }

        return true;
    }

    private function arrayMissing($array, $key)
    {
        return ! $this->arrayHas($array, $key);
    }
}
