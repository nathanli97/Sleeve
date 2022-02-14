<?php

/**
 * Copyright [2022] [nathanli]
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 *
 * PHP Version 7.4
 *
 * @category Router
 * @package  Sleeve
 * @author   nathanli <xingru97@gmail.com>
 * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
 * @link     https://github.com/nathanli/Sleeve
 */

namespace Sleeve;

/**
 * The response cookies wrapper class
 *
 * @category Router
 * @package  Sleeve
 * @author   nathanli <xingru97@gmail.com>
 * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
 * @link     https://github.com/nathanli/Sleeve
 */

class ResponseCookies
{
    protected array $names;
    protected array $values;
    protected array $expires;
    protected array $paths;
    protected array $domains;
    protected array $secures;
    protected array $httponly;

    /**
     * The constructor.
     *
     * @category Router
     * @package  Sleeve
     * @author   nathanli <xingru97@gmail.com>
     * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
     * @link     https://github.com/nathanli/Sleeve
     */
    public function __construct()
    {
        $this->names = array();
        $this->values = array();
        $this->expires = array();
        $this->paths = array();
        $this->domains = array();
        $this->secures = array();
        $this->httponly = array();
    }

    /**
     * Add a new cookie this cookie set.
     *
     * @param string $name     The cookie name
     * @param string $value    The value of this cookie
     * @param int    $expires  The expires time of this cookie
     * @param string $path     The valid path of this cookie
     * @param string $domain   The valid domain of this cookie
     * @param bool   $secure   Is the cookie is secure?
     * @param bool   $httponly Is the cookie is http-only?
     *
     * @return void
     *
     * @category Router
     * @package  Sleeve
     * @author   nathanli <xingru97@gmail.com>
     * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
     * @link     https://github.com/nathanli/Sleeve
     */
    public function addCookie(
        string $name,
        string $value = "",
        int $expires = 0,
        string $path = "",
        string $domain = "",
        bool $secure = false,
        bool $httponly = false
    ) {
        $this->names[] = $name;
        $this->values[] = $value;
        $this->expires[] = $expires;
        $this->paths[] = $path;
        $this->domains[] = $domain;
        $this->secures[] = $secure;
        $this->httponly[] = $httponly;
    }

    /**
     * Set the cookies to response.
     *
     * @return void
     *
     * @category Router
     * @package  Sleeve
     * @author   nathanli <xingru97@gmail.com>
     * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
     * @link     https://github.com/nathanli/Sleeve
     */
    public function setCookies(): void
    {
        $num = sizeof($this->names);
        for ($i = 0; $i < $num; $i++) {
            setcookie(
                $this->names[$i],
                $this->values[$i],
                $this->expires[$i],
                $this->paths[$i],
                $this->domains[$i],
                $this->secures[$i],
                $this->httponly[$i]
            );
        }
    }

    /**
     * Is the cookie set has cookies?
     *
     * @return bool Is the cookie set has cookies?
     *
     * @category Router
     * @package  Sleeve
     * @author   nathanli <xingru97@gmail.com>
     * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
     * @link     https://github.com/nathanli/Sleeve
     */
    public function hasCookies(): bool
    {
        return sizeof($this->names) > 0;
    }
}
