<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Usage report task.
 *
 * @package    block_xp
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_xp\task;

use block_xp\di;
use block_xp\local\routing\url;
use curl;

/**
 * Usage report task class.
 *
 * @package    block_xp
 * @copyright  2022 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dmd_points_report extends \core\task\adhoc_task {
    /**
     * Execute.
     */
    public function execute() {
        debugging('Executing report task ', DEBUG_DEVELOPER);
        $config = di::get('config');
        $url= $config->get('dmd_url');
        $apiKey= $config->get('dmd_apikey');
        $apiSecret= $config->get('dmd_apisecret');
        if( !$url || !$apiKey || !$apiSecret ){
            return;
        }
        $data = $this->get_custom_data_as_string();



        $curl = new curl();
        $curl->setHeader(['Content-Type: application/json']);
        $curl->setHeader(['X-Api-Key: '.$apiKey]);
        $curl->setHeader(['X-Api-Secret: '.$apiSecret]);
        $resp = $curl->post($url, $data);
        if ($curl->get_errno()) {
            debugging('Get error from curl: ' . $curl->get_errno(), DEBUG_DEVELOPER);
            return false;
        }

        $respdata = json_decode($resp);
        debugging('Resp from curl'. $resp, DEBUG_DEVELOPER);
    }


}
