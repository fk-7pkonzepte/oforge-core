<?php
/**
 * Created by PhpStorm.
 * User: Alexander Wegner
 * Date: 07.12.2018
 * Time: 09:03
 */

namespace Oforge\Engine\Modules\Core\Helper;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Class Cookie
 *
 * @package Oforge\Engine\Modules\Core\Helper
 */
class Cookie {

    /**
     * Delete the cookie by putting the expiration date in the past.
     *
     * @param Response $response
     * @param string $key
     *
     * @return Response
     */
    public function deleteCookie(Response $response, string $key) : Response {
        $cookie   = urlencode($key) . '=' . urlencode('deleted') . '; expires=Thu, 01-Jan-1970 00:00:01 GMT; Max-Age=0; path=/; secure; httponly';
        $response = $response->withAddedHeader('Set-Cookie', $cookie);

        return $response;
    }

    /**
     * Add a new cookie to the response.
     *
     * @param Response $response
     * @param string $cookieName
     * @param string $cookieValue
     * @param int $expirationMinutes
     *
     * @return Response
     * @throws \Exception If an error occurs while creating the expiration date (DateTimeImmutable).
     */
    public function addCookie(Response $response, string $cookieName, string $cookieValue, $expirationMinutes = 300) : Response {
        if ($expirationMinutes < 0) {
            $expirationMinutes = 0;
        }
        $expiry   = new \DateTimeImmutable('now + ' . $expirationMinutes . 'minutes');
        $cookie   = urlencode($cookieName) . '=' . urlencode($cookieValue) . '; expires=' . $expiry->format(\DateTime::COOKIE) . '; Max-Age='
                    . $expirationMinutes * 60 . '; path=/; secure; httponly';
        $response = $response->withAddedHeader('Set-Cookie', $cookie);

        return $response;
    }

    /**
     * @param Request $request
     * @param string $cookieName
     *
     * @return string|null
     */
    public function getCookieValue(Request $request, $cookieName) : ?string {
        $cookies = $request->getCookieParams();

        return isset($cookies[$cookieName]) ? urldecode($cookies[$cookieName]) : null;
    }

}
