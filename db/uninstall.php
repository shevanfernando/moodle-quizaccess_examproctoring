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
 * Implementation of the quizaccess_exproctor plugin.
 *
 * @package    quizaccess_exproctor
 * @copyright  2022 Shevan Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/quiz/accessrule/exproctor/classes/aws_s3.php');

use quizaccess_exproctor\aws_s3;

/**
 * Custom uninstallation procedure
 *
 * @return bool: only returns true
 * @throws moodle_exception
 */
function xmldb_quizaccess_exproctor_uninstall(): bool {
    global $DB;

    // Get role id.
    $role = $DB->get_record("role", array(
        'shortname' => get_string('proctor:short_name', 'quizaccess_exproctor')
    ));

    // Delete proctor role.
    if (!delete_role($role->id)) {
        // Delete failed.
        throw new moodle_exception("cannotdeleterolewithid", "error", "", $role->id);
    }

    // Delete all the S3 bucket.
    $s3client = new aws_s3();
    $s3client->delete_buckets();

    return true;
}
