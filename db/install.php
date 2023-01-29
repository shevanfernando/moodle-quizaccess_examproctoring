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
 * This file is executed right after the install.xml
 *
 * @package    quizaccess_exproctor
 * @copyright  2022 Shevan Thiranja Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom installation procedure
 *
 * @return void
 * @throws coding_exception
 */
function xmldb_quizaccess_exproctor_install()
{
    // Install the Exam Proctor role to system.
    $ex_proctor_id = create_role(
        get_string('proctor:name', 'quizaccess_exproctor'),
        get_string('proctor:short_name', 'quizaccess_exproctor'),
        get_string('proctor:description', 'quizaccess_exproctor'),
        get_string('proctor:short_name', 'quizaccess_exproctor')
    );

    // Set up the context levels where you can assign each role.
    set_role_contextlevels(
        $ex_proctor_id,
        array(CONTEXT_COURSE, CONTEXT_MODULE));
}