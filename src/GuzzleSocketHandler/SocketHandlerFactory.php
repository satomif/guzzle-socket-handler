<?php

namespace psrebniak\GuzzleSocketHandler;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class GuzzleSocketHandlerFactory
 */
class SocketHandlerFactory
{
    /**
     * @var string socket path
     */
    protected $path;

    /**
     * @var int socket_create $domain parameter
     */
    protected $domain;

    /**
     * @var int socket_create $type parameter
     */
    protected $type;

    /**
     * @var int socket_create $type parameter
     */
    protected $protocol;

    /**
     * @param string $path valid socket path
     */
    public function __construct($path)
    {
        $this->path = $path;
    }

    public function __invoke(RequestInterface $request, array $options)
    {
        if (isset($options['delay'])) {
            usleep($options['delay'] * 1000);
        }
        // set full uri request target with all keys (protocol, host etc)
        $request = $request->withRequestTarget((string)$request->getUri());
        $socket = new SocketHandler(
            $this->path,
            $options
        );

        $allowedRedirects = 0;
        if (isset($options[RequestOptions::ALLOW_REDIRECTS]['max'])) {
            $allowedRedirects = $options[RequestOptions::ALLOW_REDIRECTS]['max'];
        }

        do {
            $allowedRedirects--;
            $response = $socket->handle($request);
            if (in_array($response->getStatusCode(), [301, 302, 303])) {
                $request = $this->createRedirect($request, $response, 'GET', $options);
                continue;
            } elseif (in_array($response->getStatusCode(), [307, 308])) {
                $request = $this->createRedirect($request, $response, $request->getMethod(), $options);
                continue;
            }
            break;

        } while ($allowedRedirects >= 0);

        return new FulfilledPromise($response);
    }

    protected function createRedirect(RequestInterface $request, ResponseInterface $response, $method, $options)
    {
        if ($options[RequestOptions::ALLOW_REDIRECTS]['referer']) {
            $request->withHeader('referer', $request->getRequestTarget());
        }
        if ($options[RequestOptions::ALLOW_REDIRECTS]['track_redirects']) {
            $request = $request
                ->withAddedHeader('X-Guzzle-Redirect-History', $request->getRequestTarget())
                ->withAddedHeader('X-Guzzle-Redirect-Status-History', $response->getStatusCode());
        }

        $location = $response->getHeader('Location');
        return $request->withMethod($method)->withRequestTarget(array_shift($location));
    }
}