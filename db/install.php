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
 *  Assign legacy capabilities
 *
 * @param $capability
 * @param $legacyperms
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 */
function assign_exproctor_legacy_capabilities($capability, $legacyperms): bool
{
    foreach ($legacyperms as $type => $perm) {

        $systemcontext = context_system::instance();

        if ($roles = get_archetype_roles($type)) {
            foreach ($roles as $role) {
                // Assign a site level capability.
                if (!assign_capability($capability, $perm, $role->id, $systemcontext->id)) {
                    return false;
                }
            }
        }
    }

    return true;
}


/**
 * Create capabilities for ExProctor plugin
 *
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 */
function update_exproctor_capabilities(): bool
{
    global $DB, $OUTPUT;

    $component = 'quizaccess/exproctor';

    $storedcaps = array();

    $filecaps = load_capability_def($component);
    foreach ($filecaps as $capname => $unused) {
        if (!preg_match('|^[a-z]+/[a-z_0-9]+:[a-z_0-9]+$|', $capname)) {
            debugging("Coding problem: Invalid capability name '$capname', use 'clonepermissionsfrom' field for migration.");
        }
    }

    // It is possible somebody directly modified the DB (according to accesslib_test anyway).
    // So ensure our updating is based on fresh data.
    cache::make('core', 'capabilities')->delete('core_capabilities');

    $cachedcaps = get_cached_capabilities($component);
    if ($cachedcaps) {
        foreach ($cachedcaps as $cachedcap) {
            array_push($storedcaps, $cachedcap->name);
            // update risk bitmasks and context levels in existing capabilities if needed
            if (array_key_exists($cachedcap->name, $filecaps)) {
                if (!array_key_exists('riskbitmask', $filecaps[$cachedcap->name])) {
                    $filecaps[$cachedcap->name]['riskbitmask'] = 0; // no risk if not specified
                }
                if ($cachedcap->captype != $filecaps[$cachedcap->name]['captype']) {
                    $updatecap = new stdClass();
                    $updatecap->id = $cachedcap->id;
                    $updatecap->captype = $filecaps[$cachedcap->name]['captype'];
                    $DB->update_record('capabilities', $updatecap);
                }
                if ($cachedcap->riskbitmask != $filecaps[$cachedcap->name]['riskbitmask']) {
                    $updatecap = new stdClass();
                    $updatecap->id = $cachedcap->id;
                    $updatecap->riskbitmask = $filecaps[$cachedcap->name]['riskbitmask'];
                    $DB->update_record('capabilities', $updatecap);
                }

                if (!array_key_exists('contextlevel', $filecaps[$cachedcap->name])) {
                    $filecaps[$cachedcap->name]['contextlevel'] = 0; // no context level defined
                }
                if ($cachedcap->contextlevel != $filecaps[$cachedcap->name]['contextlevel']) {
                    $updatecap = new stdClass();
                    $updatecap->id = $cachedcap->id;
                    $updatecap->contextlevel = $filecaps[$cachedcap->name]['contextlevel'];
                    $DB->update_record('capabilities', $updatecap);
                }
            }
        }
    }

    // Flush the cached again, as we have changed DB.
    cache::make('core', 'capabilities')->delete('core_capabilities');

    // Are there new capabilities in the file definition?
    $newcaps = array();

    foreach ($filecaps as $filecap => $def) {
        if (!$storedcaps ||
            ($storedcaps && in_array($filecap, $storedcaps) === false)) {
            if (!array_key_exists('riskbitmask', $def)) {
                $def['riskbitmask'] = 0; // no risk if not specified
            }
            $newcaps[$filecap] = $def;
        }
    }
    // Add new capabilities to the stored definition.
    $existingcaps = $DB->get_records_menu('capabilities', array(), 'id', 'id, name');
    foreach ($newcaps as $capname => $capdef) {
        $capability = new stdClass();
        $capability->name = $capname;
        $capability->captype = $capdef['captype'];
        $capability->contextlevel = $capdef['contextlevel'];
        $capability->component = $component;
        $capability->riskbitmask = $capdef['riskbitmask'];

        $DB->insert_record('capabilities', $capability, false);

        // Flush the cached, as we have changed DB.
        cache::make('core', 'capabilities')->delete('core_capabilities');

        if (isset($capdef['clonepermissionsfrom']) && in_array($capdef['clonepermissionsfrom'], $existingcaps)) {
            if ($rolecapabilities = $DB->get_records('role_capabilities', array('capability' => $capdef['clonepermissionsfrom']))) {
                foreach ($rolecapabilities as $rolecapability) {
                    //assign_capability will update rather than insert if capability exists
                    if (!assign_capability($capname, $rolecapability->permission,
                        $rolecapability->roleid, $rolecapability->contextid, true)) {
                        echo $OUTPUT->notification('Could not clone capabilities for ' . $capname);
                    }
                }
            }
            // we ignore archetype key if we have cloned permissions
        } else if (isset($capdef['archetypes']) && is_array($capdef['archetypes'])) {
            assign_exproctor_legacy_capabilities($capname, $capdef['archetypes']);
            // 'legacy' is for backward compatibility with 1.9 access.php
        } else if (isset($capdef['legacy']) && is_array($capdef['legacy'])) {
            assign_exproctor_legacy_capabilities($capname, $capdef['legacy']);
        }
    }
    // Are there any capabilities that have been removed from the file
    // definition that we need to delete from the stored capabilities and
    // role assignments?
    capabilities_cleanup($component, $filecaps);

    // reset static caches
    accesslib_reset_role_cache();

    // Flush the cached again, as we have changed DB.
    cache::make('core', 'capabilities')->delete('core_capabilities');

    return true;
}

/**
 * Create proctor role
 *
 * @return int
 * @throws coding_exception
 * @throws dml_exception
 */
function create_exproctor_role(): int
{
    global $DB;

    // Insert the role record.
    $role = new stdClass();
    $role->name = get_string('proctor:name', 'quizaccess_exproctor');
    $role->shortname = get_string('proctor:short_name', 'quizaccess_exproctor');
    $role->description = get_string('proctor:description', 'quizaccess_exproctor');
    $role->archetype = get_string('proctor:short_name', 'quizaccess_exproctor');

    //find free sort order number
    $role->sortorder = $DB->get_field('role', 'MAX(sortorder) + 1', array());

    if (empty($role->sortorder)) {
        $role->sortorder = 1;
    }

    return $DB->insert_record('role', $role);
}

/**
 * Custom installation procedure
 *
 * @return void
 * @throws coding_exception
 * @throws dml_exception
 */
function xmldb_quizaccess_exproctor_install()
{
    // Install the Exam Proctor role to system.
    $ex_proctor_id = create_exproctor_role();

    // Now is the correct moment to install capabilities - after creation of legacy roles, but before assigning of roles
    update_exproctor_capabilities();

    // Set up the context levels where you can assign each role.
    set_role_contextlevels($ex_proctor_id, array(CONTEXT_COURSE, CONTEXT_MODULE));
}