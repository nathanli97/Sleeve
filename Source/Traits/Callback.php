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
 * PHP Version 7.4
 *
 * @category Router
 * @package  Sleeve
 * @author   nathanli <xingru97@gmail.com>
 * @license  Apache2 http://www.apache.org/licenses/LICENSE-2.0
 * @link     https://github.com/nathanli/Sleeve
 */

namespace Sleeve\Traits;

use Sleeve\Exceptions\UnexpectedCallbackFunctionReturnValue;
use Sleeve\Response;

/**
 * The Callback class.
 * This class will manage all callbacks occurs in this library.
 * @author nathanli <xingru97@gmail.com>
 * @package Sleeve
 * @license Apache2
 */
trait Callback
{
    /**
     * The HTTP Error callback functions.
     * @var array
     */
    protected array $http_error_callbacks;

    /**
     * The disabled method callback functions.
     * @var array
     */
    protected array $disabled_method_access_callbacks;

    /**
     * The unimplemented method callback functions.
     * @var array
     */
    protected array $unimplemented_method_access_callbacks;

    /**
     * Add callback function for Http Error Handler.
     * When the router encounters HTTP Error, This Handler will be called.
     * Callback prototype: function your_callback(Request $request, Response $response): Response
     *                     {
     *                          return $response;
     *                     }
     * @param $callback
     * @return void
     */
    public function onHttpError($callback)
    {
        $this->http_error_callbacks[] = $callback;
    }

    /**
     * Add a callback which will be called when trying to access a disabled HTTP Method.
     * Callback prototype: function your_callback(Request $request, Response $response): Response
     *                     {
     *                          return $response;
     *                     }
     * @param $callback
     * @return void
     */
    public function onDisabledMethod($callback)
    {
        $this->disabled_method_access_callbacks[] = $callback;
    }

    /**
     * Add a callback which will be called when trying to access an unimplemented HTTP Method.
     * Callback prototype: function your_callback(Request $request, Response $response): Response
     *                     {
     *                          return $response;
     *                     }
     * @param $callback
     * @return void
     */
    public function onUnimplementedMethod($callback)
    {
        $this->unimplemented_method_access_callbacks[] = $callback;
    }

    /**
     * Clear all HTTP Error callback function
     * @return void
     */
    public function clearHttpErrorCallbacks(): void
    {
        $this->http_error_callbacks = array();
    }

    /**
     * Initialize callbacks
     * @return void
     */
    private function initCallbacks(): void
    {
        $this->http_error_callbacks = array();
        $this->disabled_method_access_callbacks = array();
        $this->unimplemented_method_access_callbacks = array();
    }

    /**
     * Call callback
     * @param $callbacks
     * @param array $args
     * @return false|mixed|void
     */
    private function callCallback($callbacks, array $args = array())
    {
        if (sizeof($callbacks) > 0) {
            foreach ($callbacks as $callback) {
                return call_user_func_array($callback, $args);
            }
        }
    }

    /**
     * Process the value return by user's callback.
     * @param $returnVal
     * @param Response $response
     * @return Response
     */
    private function processCallbackReturnValue($returnVal, Response $response): Response
    {
        if (is_integer($returnVal)) {
            if ($returnVal >= 100 && $returnVal <= 699) {
                $response->status_code = $returnVal;
            } else {
                $response->body = strval($returnVal);
            }
        } elseif (is_string($returnVal)) {
            $response->body = $returnVal;
        } elseif ($returnVal instanceof Response) {
            $response = $returnVal;
        } elseif (is_null($returnVal)) {
            return $response;
        } else {
            throw new UnexpectedCallbackFunctionReturnValue();
        }
        return $response;
    }
}
