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
 * @return string
 * @throws coding_exception
 * @throws dml_exception
 */
function xmldb_quizaccess_exproctor_install(): string
{
    global $DB;

    $name = get_string('proctor:name', 'quizaccess_exproctor');
    $shortname = get_string('proctor:short_name', 'quizaccess_exproctor');
    $description = get_string('proctor:description', 'quizaccess_exproctor');
    $archetype = get_string('proctor:short_name', 'quizaccess_exproctor');
    $systemContext = context_system::instance();

    // Create the Exam Proctor role to system.
    $exproctorId = create_role($name, $shortname, $description);

    // Update proctor role archetype. Because in previous step it is not add to mdl_role table
    $DB->update_record('role', array('id' => $exproctorId, 'archetype' => $archetype));

    $examProctorCapabilities = array(
        'mod/assign:view',
        'mod/assign:grade',
        'mod/assign:viewgrades',
        'mod/assign:showhiddengrader',
        'mod/assign:exportownsubmission',
        'mod/quiz:view',
        'mod/quiz:preview',
        'mod/quiz:grade',
        'mod/quiz:viewoverrides',
        'mod/quiz:regrade',
        'mod/quiz:viewreports'
    );

    // Assign capabilities
    foreach ($examProctorCapabilities as $capability) {
        // Add quiz access to the Proctor role.
        assign_capability($capability, CAP_ALLOW, $exproctorId, $systemContext->id);
    }

    // Set up the context levels where you can assign each role.
    set_role_contextlevels($exproctorId, array(CONTEXT_COURSE, CONTEXT_MODULE));

    return 'ExProctor plugin installation completed successfully.';
}
