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
 * dMindDrop report service.
 *
 * @package    block_xp
 * @copyright  2023 dMindDrop
 * @author     A H
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace block_xp\local\dmd;

use block_xp\di;
use block_xp\local\config\config;
use block_xp\task\dmd_points_report;

/**
 * dMindDrop report service.
 *
 * @package    block_xp
 * @copyright  2023 dMindDrop
 * @author     A H
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class dmd_report_service {

    /** @var config The config. */
    protected $config;
    /** @var int The courseId. */
    protected $courseId;

    /**
     * Constructor.
     *
     * @param int $courseid The course ID.
     * @param config $config The config.
     */
    public function __construct($courseid) {
        $this->courseId = $courseid;
        $this->config = di::get('config');
    }


    /**
     * Notify a user.
     *
     * @param int $userid The user ID.
     * @return void
     */
    public function report($userid, $points, $reason) {
        debugging('Executing report ', DEBUG_DEVELOPER);

        if( $this->canReport() ) {
            // TODO: create the task
            $reportTask = new dmd_points_report();
            $reportTask->set_custom_data([
                'userid' => $userid,
                'points' => $points,
                'reason' => $reason,
            ]);
            \core\task\manager::queue_adhoc_task($reportTask);
        } else {
            debugging('Cannot report ', DEBUG_DEVELOPER);

        }
    }

    private function canReport() {
        return  $this->config->get('dmd_url') && $this->config->get('dmd_apikey') && $this->config->get('dmd_apisecret');
    }

}
