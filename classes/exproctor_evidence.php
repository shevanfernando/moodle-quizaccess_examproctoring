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

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/quiz/accessrule/exproctor/classes/aws_s3.php');

use coding_exception;
use core\persistent;
use core_user;
use dml_exception;
use lang_string;

ini_set('max_execution_time', 0);

/**
 * ExProctor evidence persistent class.
 *
 * @package    quizaccess_exproctor
 * @copyright  2022 Shevan Thiranja Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exproctor_evidence extends persistent {

    /** Table name for the persistent. */
    const TABLE = 'quizaccess_exproctor_evid';

    /**
     * Get unique exproctor evidences records by quiz ID and course ID
     *
     * @param int $quizid
     * @param int $courseid
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_unique_evidences_by_quizid_and_courseid(
        int $quizid,
        int $courseid
    ): array {
        global $DB;

        $sql =
            "SELECT DISTINCT string_agg(distinct e.evidencetype, ' | ') AS evidencetype, e.userid, u.firstname, u.lastname," .
            " u.email, max(e.timecreated) AS timecreated FROM {" . static::TABLE . "} e INNER JOIN {user} u ON " .
            "u.id = e.userid WHERE e.quizid = :quizid AND e.courseid = :courseid GROUP BY " .
            " e.userid, u.firstname, u.lastname, u.email";

        $persistents = [];

        $recordset = $DB->get_recordset_sql($sql,
            ['quizid' => $quizid, "courseid" => $courseid]);

        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();

        return $persistents;
    }

    /**
     * Delete exproctor evidence record by ID
     *
     * @param int $id evidence table id
     *
     * @return bool
     * @throws dml_exception
     */
    public static function delete_evidence_by_id(
        int $id
    ): bool {
        $persistents = static::get_evidence_by_id($id);

        return static::delete_evidence($persistents, array("id" => $id));
    }

    /**
     * Get exproctor evidence record with file information by ID
     *
     * @param int $id
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_evidence_by_id(
        int $id
    ): array {
        global $DB;

        $sql =
            "SELECT e.id, e.url, e.fileid, e.s3filename, e.storagemethod, f.contextid, f.filearea, f.itemid FROM {"
            . static::TABLE . "} e LEFT JOIN {files} f ON f.id = e.fileid WHERE e.id = :id";

        $persistents = [];

        $recordset = $DB->get_recordset_sql($sql,
            [
                'id' => $id
            ]);

        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();

        return $persistents;
    }

    /**
     * Delete evidence
     *
     * @param array $persistents
     * @param array $conditions
     * @param bool $isdeleteall
     * @return bool
     * @throws dml_exception
     */
    private static function delete_evidence(
        array $persistents,
        array $conditions,
        bool $isdeleteall = false
    ): bool {
        global $DB;

        if (empty($persistents)) {
            return false;
        }

        foreach ($persistents as $persistent) {
            if (array_key_exists("evidencetype",
                    $conditions) && ($persistent->get("evidencetype") !== $conditions["evidencetype"])) {
                continue;
            }

            if ($persistent->get("storagemethod") === "Local") {
                // Delete the actual file.
                $fs = get_file_storage();
                $fs->delete_area_files_select($persistent->get("contextid"),
                    "quizaccess_exproctor",
                    ("{$persistent->get("filearea")}"),
                    " = :itemid AND id = :id", array(
                        "itemid" => $persistent->get("itemid"),
                        "id" => $persistent->get("fileid")
                    ));
            } else {
                // S3 Bucket record delete.
                $s3 = new aws_s3();
                $bucketname = $s3->get_bucket_name_using_url($persistent->get("url"));
                if ($isdeleteall) {
                    $s3->delete_bucket($bucketname, $persistent->get("evidencetype"));
                } else {
                    $s3->delete_image($bucketname, $persistent->get("s3filename") . ".png");
                }
            }
        }

        $deletewhere = array_key_exists("id", $conditions) ? "id = :id" :
            'quizid = :quizid AND courseid = :courseid AND userid = :userid AND ' . $DB->sql_compare_text('evidencetype') .
            ' = ' . $DB->sql_compare_text(':evidencetype');

        return $DB->delete_records_select(static::TABLE, $deletewhere,
            $conditions);
    }

    /**
     * Delete exproctor evidence record by ID
     *
     * @param int $quizid
     * @param int $courseid
     * @param int $userid
     * @param string $evidencetype
     *
     * @return bool
     * @throws dml_exception
     */
    public static function delete_evidences_by_quizid_and_courseid_and_userid(
        int $quizid,
        int $courseid,
        int $userid,
        string $evidencetype
    ): bool {
        $persistents =
            static::get_evidences_by_quizid_and_courseid_and_userid($quizid,
                $courseid, $userid);

        return static::delete_evidence($persistents, array(
            "quizid" => $quizid,
            "courseid" => $courseid, "userid" => $userid,
            "evidencetype" => $evidencetype
        ), true);
    }

    /**
     * Get all exproctor evidences records by quiz ID, course ID and user ID
     *
     * @param int $quizid
     * @param int $courseid
     * @param int $userid
     *
     * @return array
     * @throws dml_exception
     */
    public static function get_evidences_by_quizid_and_courseid_and_userid(
        int $quizid,
        int $courseid,
        int $userid
    ): array {
        global $DB;

        $sql =
            "SELECT e.id, e.userid, u.firstname, u.lastname, e.evidencetype, e.url, e.fileid, e.s3filename, " .
            "e.storagemethod, f.contextid, f.filearea, f.itemid FROM {" . static::TABLE . "} e INNER JOIN {user} u ON " .
            " u.id = e.userid LEFT JOIN {files} f ON f.id = e.fileid WHERE e.quizid = :quizid AND e.courseid = :courseid AND " .
            "e.userid = :userid ORDER BY e.id";

        $persistents = [];

        $recordset = $DB->get_recordset_sql($sql,
            [
                'quizid' => $quizid, "courseid" => $courseid,
                "userid" => $userid
            ]);

        foreach ($recordset as $record) {
            $persistents[] = new static(0, $record);
        }
        $recordset->close();

        return $persistents;
    }

    /**
     * Return the definition of the properties of this model.
     *
     * @return array
     * @throws coding_exception
     */
    protected static function define_properties(): array {
        return [
            'id' => [
                'type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "id", "data_type" => "an integer")
                ),
            ], 'firstname' => [
                'type' => PARAM_TEXT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "firstname", "data_type" => "a string")
                ),
            ], 'lastname' => [
                'type' => PARAM_TEXT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "lastname", "data_type" => "a string")
                ),
            ], 'email' => [
                'type' => PARAM_TEXT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "email", "data_type" => "a string")
                ),
            ], 'courseid' => [
                'type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "courseid", "data_type" => "an integer")
                ),
            ], 'quizid' => [
                'type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "quizid", "data_type" => "an integer")
                ),
            ], 'userid' => [
                'type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED
            ], 'attemptid' => [
                'type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "attemptid", "data_type" => "an integer")
                ),
            ], 'fileid' => [
                'type' => PARAM_INT, 'null' => NULL_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "fileid", "data_type" => "an integer")
                ),
            ], 'itemid' => [
                'type' => PARAM_INT, 'null' => NULL_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "itemid", "data_type" => "an integer")
                ),
            ], 's3filename' => [
                'type' => PARAM_TEXT, 'null' => NULL_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "s3filename", "data_type" => "a string")
                ),
            ], 'contextid' => [
                'type' => PARAM_INT, 'null' => NULL_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "contextid", "data_type" => "an integer")
                ),
            ], 'filearea' => [
                'type' => PARAM_TEXT, 'null' => NULL_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array("field" => "filearea", "data_type" => "a string")
                ),
            ], 'url' => [
                'type' => PARAM_RAW, 'null' => NULL_NOT_ALLOWED,
            ], 'isquizfinished' => [
                'type' => PARAM_INT, 'null' => NULL_NOT_ALLOWED,
                "default" => 0,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array(
                        "field" => "isquizfinished",
                        "data_type" => "an integer"
                    )
                ),
            ], 'storagemethod' => [
                'type' => PARAM_TEXT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array(
                        "field" => "storagemethod",
                        "data_type" => "a string"
                    )
                ),
            ], 'evidencetype' => [
                'type' => PARAM_TEXT, 'null' => NULL_NOT_ALLOWED,
                'message' => new lang_string(
                    'invalid_data',
                    'quizaccess_exproctor',
                    array(
                        "field" => "evidencetype",
                        "data_type" => "a string"
                    )
                ),
            ],
        ];
    }

    /**
     * Validate the user ID.
     *
     * @param int $value The value.
     *
     * @return true|lang_string
     * @throws coding_exception
     */
    protected function validate_userid(
        int $value
    ) {
        $msg = array("field" => "userid", "data_type" => "an integer");

        if (empty($value)) {
            return new lang_string(
                'empty_data',
                'quizaccess_exproctor',
                $msg
            );
        }

        if (!core_user::is_real_user($value, true)) {
            return new lang_string(
                'invalid_data',
                'quizaccess_exproctor',
                $msg
            );
        }

        return true;
    }
}
