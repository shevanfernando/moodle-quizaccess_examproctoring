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
 * Screenshot for the quizaccess_exproctor plugin.
 *
 * @package    quizaccess_exproctor
 * @copyright  2022 Shevan Thiranja Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_exproctor;

use core\persistent;

defined('MOODLE_INTERNAL') || die();


/**
 * screenshot
 */
class screenshot extends persistent
{

    /** Table name for the persistent. */
    const TABLE = 'quizaccess_exproctor_wb_logs';

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     */
    protected static function define_properties()
    {
        return [
            'courseid' => [
                'type' => PARAM_INT,
                'default' => '',
            ],
            'quizid' => [
                'type' => PARAM_INT,
                'default' => '',
            ],
            'userid' => [
                'type' => PARAM_INT,
                'default' => '',
            ],
            'webcampicture' => [
                'type' => PARAM_TEXT,
            ],
            'status' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
            'timemodified' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }
}
