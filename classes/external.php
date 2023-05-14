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
 * External class for quizaccess_exproctor.
 *
 * @package    quizaccess_exproctor
 * @copyright  2022 Shevan Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . '/mod/quiz/accessrule/exproctor/classes/aws_s3.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/exproctor/classes/aws_rekognition.php');

use quizaccess_exproctor\aws_rekognition;
use quizaccess_exproctor\aws_s3;

class quizaccess_exproctor_external extends external_api {
    /**
     * This function set the status of the quiz (Set quiz status as finished)
     *
     * @param $courseid
     * @param $userid
     * @param $quizid
     *
     * @return bool true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_quiz_status($courseid, $userid, $quizid): bool {
        global $DB;

        // Validate the params.
        $params = self::validate_parameters(self::set_quiz_status_parameters(),
            array(
                'courseid' => $courseid,
                'userid' => $userid,
                'quizid' => $quizid
            )
        );

        $conditions = array(
            'courseid' => $params['courseid'],
            'quizid' => $params['quizid'],
            'userid' => $params['userid'],
            'isquizfinished' => false
        );

        return $DB->set_field("quizaccess_exproctor_evid", 'isquizfinished',
            true, $conditions
        );
    }

    /**
     * Returns description of method parameters (webcam shot table)
     *
     * @return external_function_parameters
     */
    public static function set_quiz_status_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT,
                'course id',
                VALUE_REQUIRED
            ),
            'userid' => new external_value(PARAM_INT,
                'user id',
                VALUE_REQUIRED
            ),
            'quizid' => new external_value(PARAM_INT,
                'quiz id',
                VALUE_REQUIRED
            ),
        ));
    }

    /**
     * Returns status of current quiz attempt (webcam shot table)
     *
     * @return external_description
     */
    public static function set_quiz_status_returns() {
        return new external_value(PARAM_BOOL,
            'Current quiz attempt status updated'
        );
    }

    /**
     * This function store the webcam shot
     *
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @param $webcamshot
     * @param $bucketname
     * @param $aiproctoring
     *
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function send_webcam_shot(
        $courseid,
        $attemptid,
        $quizid,
        $webcamshot,
        $bucketname,
        $aiproctoring
    ): array {
        // Validate the params.
        $params = self::validate_parameters(self::send_webcam_shot_parameters(),
            array(
                'courseid' => $courseid,
                'attemptid' => $attemptid,
                'quizid' => $quizid,
                'webcamshot' => $webcamshot,
                'bucketName' => $bucketname,
                'aiproctoring' => $aiproctoring,
            )
        );

        $params["filearea"] = "webcam_images";

        return self::store_image($params, "webcam");
    }

    /**
     * Store parameters
     *
     * @return external_function_parameters
     */
    public static function send_webcam_shot_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT,
                'course id',
                VALUE_REQUIRED
            ),
            'attemptid' => new external_value(PARAM_INT,
                'attempt id',
                VALUE_REQUIRED
            ),
            'quizid' => new external_value(PARAM_INT,
                'quiz id',
                VALUE_REQUIRED
            ),
            'webcamshot' => new external_value(PARAM_RAW,
                'webcam shot',
                VALUE_REQUIRED
            ),
            'bucketName' => new external_value(PARAM_TEXT,
                'S3 Bucket name',
                VALUE_OPTIONAL
            ),
            'aiproctoring' => new external_value(PARAM_BOOL,
                'Ai proctoring is need',
                VALUE_OPTIONAL
            ),
        ));
    }

    /**
     * Store image
     *
     * @throws dml_exception
     */
    private static function store_image($params, $type): array {
        global $USER, $DB;

        $tablename = "quizaccess_exproctor_evid";
        $fileid = null;
        $s3filename = null;

        // Get last record and check the quiz is finished or not.
        $conditions = array(
            'courseid' => (int) $params['courseid'],
            'attemptid' => (int) $params['attemptid'],
            'quizid' => (int) $params['quizid'],
            'userid' => (int) $USER->id,
            'isquizfinished' => true,
            'evidencetype' => $type
        );

        $warnings = array();
        $id = null;

        $numberofrecords = $DB->count_records_sql("SELECT COUNT(id) FROM {" . $tablename . "} WHERE courseid = :courseid AND " .
            " attemptid = :attemptid AND quizid = :quizid AND userid = :userid AND isquizfinished = :isquizfinished AND " .
            "evidencetype = :evidencetype", $conditions);

        if ($numberofrecords == 0) {
            $s3client = new aws_s3();

            if ($type == 'screen') {
                $data = $params['screenshot'];
            } else {
                $data = $params['webcamshot'];
            }

            $settings = $s3client->get_data();

            $attemptid = $params['attemptid'];
            $courseid = $params['courseid'];

            if ($settings["storagemethod"] === 'Local') {
                $record = new stdClass();
                $record->filearea = $params["filearea"];
                $record->component = 'quizaccess_exproctor';
                $record->filepath = '';
                $record->itemid = $params['attemptid'];
                $record->license = '';
                $record->author = '';

                $context = context_module::instance($params['quizid']);

                $fs = get_file_storage();
                $record->filepath = file_correct_filepath($record->filepath);

                $result = self::get_url_and_fileid($data, $type, $attemptid,
                    $USER->id, $courseid,
                    $context->id, $record, $fs
                );

                $fileid = $result["file_id"];
                $url = $result["url"];
            } else {
                list(, $data) = explode(';', $data);
                list(, $data) = explode(',', $data);
                $data = base64_decode($data);
                $filename =
                    $type . '-' . $attemptid . '-' . $USER->id . '-' . $courseid . '-' . time() . rand(1, 1000) . '.png';

                if ($type === "webcam" && $params['aiproctoring']) {
                    // AI proctoring.
                    $rekognition = new aws_rekognition();
                    $result = $rekognition->store_evidence_which_false_ai_proctor($params['bucketName'], $data, $filename);
                    if (empty($result)) {
                        return ["id" => 0, "warnings" => $warnings];
                    }
                } else {
                    $result = $s3client->save_image($params['bucketName'], $data, $filename);
                }

                $url = $result['ObjectURL'];
                $s3filename = explode(".", $filename)[0];
            }

            $record = new stdClass();
            $record->courseid = (int) $params['courseid'];
            $record->quizid = (int) $params['quizid'];
            $record->userid = (int) $USER->id;
            $record->attemptid = (int) $params['attemptid'];
            $record->fileid = $fileid;
            $record->s3filename = $s3filename;
            $record->url = "{$url}";
            $record->timecreated = time();
            $record->timemodified = time();
            $record->storagemethod = $settings["storagemethod"];
            $record->evidencetype = $type;
            $id = $DB->insert_record($tablename, $record);
        } else {
            $warnings[] = "Quiz already finished!";
        }

        $result = array();
        $result['id'] = $id;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * This function generates URL for images and get file id
     *
     * @param $data
     * @param $type
     * @param $attemptid
     * @param $userid
     * @param $courseid
     * @param $contextid
     * @param $record
     * @param $fs
     *
     * @return array
     * @throws dml_exception
     */
    private static function get_url_and_fileid(
        $data,
        $type,
        $attemptid,
        $userid,
        $courseid,
        $contextid,
        $record,
        $fs
    ): array {
        global $DB;

        list(, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        $filename =
            $type . '-' . $attemptid . '-' . $userid . '-' . $courseid . '-' . time() . rand(1,
                1000) . '.png';

        $record->courseid = $courseid;
        $record->filename = $filename;
        $record->contextid = $contextid;
        $record->userid = $userid;

        $fs->create_file_from_string($record, $data);

        $conditions = array(
            'userid' => $userid, 'contextid' => $contextid,
            'mimetype' => 'image/png',
            'component' => 'quizaccess_exproctor',
            'filearea' => "{$record->filearea}",
            'filename' => "{$filename}"
        );

        $filerecords = $DB->get_records("files", $conditions);

        $fileid = 0;
        foreach ($filerecords as $filerecord) {
            $fileid = $filerecord->id;
        }

        return array(
            "file_id" => $fileid,
            "url" => ((moodle_url::make_pluginfile_url($contextid,
                $record->component,
                $record->filearea,
                $record->itemid,
                $record->filepath,
                $record->filename
            )))
        );
    }

    /**
     * Webcam shots return parameters.
     *
     * @return external_single_structure
     */
    public static function send_webcam_shot_returns(): external_single_structure {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_TEXT,
                'webcam shot id'
            ),
            'warnings' => new external_warnings()
        ));
    }

    /**
     * This function store the screenshot
     *
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @param $screenshot
     * @param $bucketname
     *
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function send_screen_shot(
        $courseid,
        $attemptid,
        $quizid,
        $screenshot,
        $bucketname
    ): array {
        // Validate the params.
        $params = self::validate_parameters(self::send_screen_shot_parameters(),
            array(
                'courseid' => $courseid,
                'attemptid' => $attemptid,
                'quizid' => $quizid,
                'screenshot' => $screenshot,
                'bucketName' => $bucketname,
                'aiproctoring' => false,
            )
        );

        $params["filearea"] = "screen_shots";

        return self::store_image($params, "screen");
    }

    /**
     * Store parameters
     *
     * @return external_function_parameters
     */
    public static function send_screen_shot_parameters(): external_function_parameters {
        return new external_function_parameters(array(
            'courseid' => new external_value(PARAM_INT,
                'course id',
                VALUE_REQUIRED
            ),
            'attemptid' => new external_value(PARAM_INT,
                'attempt id',
                VALUE_REQUIRED
            ),
            'quizid' => new external_value(PARAM_INT,
                'quiz id',
                VALUE_REQUIRED
            ),
            'screenshot' => new external_value(PARAM_RAW,
                'screen shot',
                VALUE_REQUIRED
            ),
            'bucketName' => new external_value(PARAM_TEXT,
                'S3 Bucket name',
                VALUE_OPTIONAL
            ),
            'aiproctoring' => new external_value(PARAM_BOOL,
                'Ai proctoring is need',
                VALUE_OPTIONAL,
                false
            ),
        ));
    }

    /**
     * Screen shots return parameters.
     *
     * @return external_single_structure
     */
    public static function send_screen_shot_returns(): external_single_structure {
        return new external_single_structure(array(
            'id' => new external_value(PARAM_INT,
                'screen shot id'
            ),
            'warnings' => new external_warnings()
        ));
    }
}
