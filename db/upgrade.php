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

/**
 * Exam proctoring module upgrade function.
 * @param string $oldversion the version we are upgrading from.
 */
function xmldb_quizaccess_exproctor_upgrade($oldversion)
{
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2022111900) {

        // Define table quizaccess_exproctor to be created.
        $table = new xmldb_table('quizaccess_exproctor');

        // Adding fields to table quizaccess_exproctor.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('quizid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('webcamproctoringrequired', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('screenproctoringrequired', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, '0');

        // Adding keys to table quizaccess_exproctor.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('quizid', XMLDB_KEY_FOREIGN_UNIQUE, ['quizid'], 'quiz', ['id']);

        // Conditionally launch create table for quizaccess_exproctor.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Examproctoring savepoint reached.
        upgrade_plugin_savepoint(true, 2022111900, 'quizaccess', 'exproctor');
    }

    if ($oldversion < 2022112002) {

        // Define field proctoringmethod to be added to quizaccess_exproctor.
        $table = new xmldb_table('quizaccess_exproctor');
        $field_proctoringmethod = new xmldb_field('proctoringmethod', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'screenproctoringrequired');
        $field_screenshotdelay = new xmldb_field('screenshotdelay', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '3', 'proctoringmethod');
        $field_screenshotwidth = new xmldb_field('screenshotwidth', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '230', 'screenshotdelay');

        // Conditionally launch add field proctoringmethod.
        if (!$dbman->field_exists($table, $field_proctoringmethod)) {
            $dbman->add_field($table, $field_proctoringmethod);
        }

        // Conditionally launch add field screenshotdelay.
        if (!$dbman->field_exists($table, $field_screenshotdelay)) {
            $dbman->add_field($table, $field_screenshotdelay);
        }

        // Conditionally launch add field screenshotwidth.
        if (!$dbman->field_exists($table, $field_screenshotwidth)) {
            $dbman->add_field($table, $field_screenshotwidth);
        }

        // Exproctor savepoint reached.
        upgrade_plugin_savepoint(true, 2022112002, 'quizaccess', 'exproctor');
    }

    return true;
}