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

trait Filter
{
    protected array $filters_beforeRouting;
    protected array $filters_afterRouting;

    public function addFilterBeforeRouting($callback)
    {
        $this->filters_beforeRouting[] = $callback;
    }

    public function addFilterAfterRouting($callback)
    {
        $this->filters_afterRouting[] = $callback;
    }

    private function initFilters()
    {
        $this->filters_beforeRouting = array();
        $this->filters_afterRouting = array();
    }
}
