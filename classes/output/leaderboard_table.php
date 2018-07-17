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
 * Leaderboard table.
 *
 * @package    block_xp
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_xp\output;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/tablelib.php');

use renderer_base;
use flexible_table;
use user_picture;
use block_xp\local\config\course_world_config;
use block_xp\local\leaderboard\leaderboard;
use block_xp\local\sql\limit;

/**
 * Leaderboard table.
 *
 * @package    block_xp
 * @copyright  2018 Frédéric Massart
 * @author     Frédéric Massart <fred@branchup.tech>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class leaderboard_table extends flexible_table {

    /** @var leaderboard The leaderboard. */
    protected $leaderboard;

    /** @var block_xp_renderer XP Renderer. */
    protected $xpoutput = null;

    /** @var int The user ID we're viewing the ladder for. */
    protected $userid;

    /** @var int The identity mode. */
    protected $identitymode = course_world_config::IDENTITY_ON;

    /** @var int The rank mode. */
    protected $rankmode = course_world_config::RANK_ON;

    /**
     * Constructor.
     *
     * @param leaderboard $leaderboard The leaderboard.
     * @param renderer_base $renderer The renderer.
     * @param array $options Options.
     * @param int $userid The user viewing this.
     */
    public function __construct(
            leaderboard $leaderboard,
            renderer_base $renderer,
            array $options = [],
            $userid
        ) {

        global $CFG, $USER;
        parent::__construct('block_xp_ladder');

        if (isset($options['rankmode'])) {
            $this->rankmode = $options['rankmode'];
        }
        if (isset($options['identitymode'])) {
            $this->identitymode = $options['identitymode'];
        }

        // The user ID we're viewing the ladder for.
        $this->userid = $userid;

        // Block XP stuff.
        $this->leaderboard = $leaderboard;
        $this->xpoutput = $renderer;

        // Define columns, and headers.
        $columns = array_keys($this->leaderboard->get_columns());
        $headers = array_map(function($header) {
            return (string) $header;
        }, array_values($this->leaderboard->get_columns()));
        $this->define_columns($columns);
        $this->define_headers($headers);

        // Define various table settings.
        $this->sortable(false);
        $this->collapsible(false);
        $this->set_attribute('class', 'block_xp-table');
        $this->column_class('rank', 'col-rank');
        $this->column_class('level', 'col-lvl');
        $this->column_class('userpic', 'col-userpic');
    }

    /**
     * Output the table.
     */
    public function out($pagesize) {
        $this->setup();

        // Compute where to start from.
        $requestedpage = optional_param($this->request[TABLE_VAR_PAGE], null, PARAM_INT);
        if ($requestedpage === null) {
            $mypos = $this->leaderboard->get_position($this->userid);
            if ($mypos !== null) {
                $this->currpage = floor($mypos / $pagesize);
            }
        }

        $this->pagesize($pagesize, $this->leaderboard->get_count());
        $ranking = $this->leaderboard->get_ranking(new limit($pagesize, (int) $this->get_page_start()));
        foreach ($ranking as $rank) {
            $row = (object) [
                'rank' => $rank->get_rank(),
                'state' => $rank->get_state()
            ];
            $this->add_data_keyed([
                'fullname' => $this->col_fullname($row),
                'level' => $this->col_lvl($row),
                'progress' => $this->col_progress($row),
                'rank' => $this->col_rank($row),
                'xp' => $this->col_xp($row),
                'userpic' => $this->col_userpic($row),
            ]);
        }
        $this->finish_output();
    }

    /**
     * Formats the column fullname.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_fullname($row) {
        $o = $this->col_userpic($row);
        if ($this->identitymode == course_world_config::IDENTITY_OFF && $row->state->get_id() != $this->userid) {
            $o .= get_string('someoneelse', 'block_xp');
        } else {
            $o .= parent::col_fullname($row->state->get_user());
        }
        return $o;
    }

    /**
     * Formats the level.
     *
     * @param stdClass $row Table row.
     * @return string
     */
    public function col_lvl($row) {
        return $this->xpoutput->small_level_badge($row->state->get_level());
    }

    /**
     * Formats the column progress.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_progress($row) {
        return $this->xpoutput->progress_bar($row->state);
    }

    /**
     * Formats the rank column.
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_rank($row) {
        if ($this->rankmode == course_world_config::RANK_REL) {
            $symbol = '';
            if ($row->rank > 0) {
                $symbol = '+';
            }
            // We want + when it's positive, and - when it's negative, else nothing.
            return $symbol . $this->xpoutput->xp($row->rank);
        }
        return $row->rank;
    }

    /**
     * Formats the rank column.
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_xp($row) {
        return $this->xpoutput->xp($row->state->get_xp());
    }

    /**
     * Formats the column userpic.
     *
     * @param stdClass $row Table row.
     * @return string Output produced.
     */
    public function col_userpic($row) {
        $options = [];
        if ($this->identitymode == course_world_config::IDENTITY_OFF && $this->userid != $row->state->get_id()) {
            $options = ['link' => false, 'alttext' => false];
        }
        return $this->xpoutput->user_picture($row->state->get_user());
    }

}
