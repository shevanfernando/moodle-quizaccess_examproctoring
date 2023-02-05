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

global $CFG;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . "/externallib.php");

use quizaccess_exproctor\aws_s3;

class quizaccess_exproctor_external extends external_api
{
    /**
     * This function set the status of the quiz in (webcam shot table) - (Set quiz status as finished)
     *
     * @param $courseid
     * @param $userid
     * @param $quizid
     * @return bool true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_wb_quiz_status($courseid, $userid, $quizid): bool
    {
        // Validate the params
        $params = self::validate_parameters(
            self::set_wb_quiz_status_parameters(),
            array(
                'courseid' => $courseid,
                'userid'   => $userid,
                'quizid'   => $quizid
            )
        );

        return self::updated_quiz_status('quizaccess_exproctor_wb_logs', $params);
    }

    /**
     * Returns description of method parameters (webcam shot table)
     *
     * @return external_function_parameters
     */
    public static function set_wb_quiz_status_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                'userid'   => new external_value(PARAM_INT, 'user id', VALUE_REQUIRED),
                'quizid'   => new external_value(PARAM_INT, 'quiz id', VALUE_REQUIRED),
            )
        );
    }

    /**
     * This function set the current quiz status as finished
     *
     * @param $tablename
     * @param $params
     * @return bool
     * @throws dml_exception
     */
    private static function updated_quiz_status($tablename, $params): bool
    {
        global $DB;

        $conditions = array('courseid'       => $params['courseid'],
                            'quizid'         => $params['quizid'],
                            'userid'         => $params['userid'],
                            'isquizfinished' => false);

        return $DB->set_field($tablename, 'isquizfinished', true, $conditions);
    }

    /**
     * Returns status of current quiz attempt (webcam shot table)
     *
     * @return external_description
     */
    public static function set_wb_quiz_status_returns()
    {
        return new external_value(PARAM_BOOL, 'current quiz attempt status updated');
    }

    /**
     * This function set the status of the quiz in (Screen-shot table) - (Set quiz status as finished)
     *
     * @param $courseid
     * @param $userid
     * @param $quizid
     * @return bool true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_sc_quiz_status($courseid, $userid, $quizid): bool
    {
        // Validate the params
        $params = self::validate_parameters(
            self::set_sc_quiz_status_parameters(),
            array(
                'courseid' => $courseid,
                'userid'   => $userid,
                'quizid'   => $quizid
            )
        );

        return self::updated_quiz_status('quizaccess_exproctor_sc_logs', $params);
    }

    /**
     * Returns description of method parameters (Screen-shot table)
     *
     * @return external_function_parameters
     */
    public static function set_sc_quiz_status_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                'userid'   => new external_value(PARAM_INT, 'user id', VALUE_REQUIRED),
                'quizid'   => new external_value(PARAM_INT, 'quiz id', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Returns status of current quiz attempt (Screen-shot table)
     *
     * @return external_description
     */
    public static function set_sc_quiz_status_returns()
    {
        return new external_value(PARAM_BOOL, 'current quiz attempt status updated');
    }

    /**
     * This function store the webcam shot
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @param $webcamshot
     * @param $bucketName
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function send_webcam_shot($courseid, $attemptid, $quizid, $webcamshot, $bucketName): array
    {
        $table_name = 'quizaccess_exproctor_wb_logs';

        // Validate the params
        $params = self::validate_parameters(
            self::send_webcam_shot_parameters(),
            array(
                'courseid'   => $courseid,
                'attemptid'  => $attemptid,
                'quizid'     => $quizid,
                'webcamshot' => $webcamshot,
                'bucketName' => $bucketName,
            )
        );

        $params["filearea"] = "webcam_images";

        return self::store_image($params, "webcam", $table_name);
    }

    /**
     * Store parameters
     *
     * @return external_function_parameters
     */
    public static function send_webcam_shot_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid'   => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                'attemptid'  => new external_value(PARAM_INT, 'attempt id', VALUE_REQUIRED),
                'quizid'     => new external_value(PARAM_INT, 'quiz id', VALUE_REQUIRED),
                'webcamshot' => new external_value(PARAM_RAW, 'webcam shot', VALUE_REQUIRED),
                'bucketName' => new external_value(PARAM_TEXT, 'S3 Bucket name', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Store image
     *
     * @throws dml_exception
     */
    private static function store_image($params, $type, $table_name): array
    {
        global $USER, $DB;

        // get last record and check the quiz is finished or not
        $conditions = array('courseid'       => $params['courseid'],
                            'attemptid'      => $params['attemptid'],
                            'quizid'         => $params['quizid'],
                            'userid'         => $USER->id,
                            'isquizfinished' => true);

        $warnings = array();
        $id = null;

        $number_of_records = $DB->count_records($table_name, $conditions);

        if ($number_of_records == 0) {
            $s3Client = new aws_s3();

            $settings = $s3Client->getData();

            $data = $params['screenshot'];
            $attemptid = $params['attemptid'];
            $courseid = $params['courseid'];

            if ($settings["storagemethod"] == 'Local') {
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

                $output = self::get_url_and_file_id($data, $type, $attemptid, $USER->id, $courseid, $context->id, $record, $fs);
            } else {
                list(, $data) = explode(';', $data);
                list(, $data) = explode(',', $data);
                $data = base64_decode($data);
                $filename = $type . '-' . $attemptid . '-' . $USER->id . '-' . $courseid . '-' . time() . rand(1, 1000) . '.png';

                $result = $s3Client->saveImage($params['bucketName'], $data, $filename);

                $output = array(
                    'url'     => $result['ObjectURL'],
                    'file_id' => explode(".", $filename)[0]
                );
            }

            $record = new stdClass();
            $record->courseid = $params['courseid'];
            $record->quizid = $params['quizid'];
            $record->userid = $USER->id;
            $record->screenshot = "{$output['url']}";
            $record->attemptid = $params['attemptid'];
            $record->fileid = "{$output['file_id']}";
            $record->timecreated = time();
            $record->timemodified = time();
            $record->storagemethod = $settings["storagemethod"];
            $id = $DB->insert_record($table_name, $record, true);
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
     * @return array
     * @throws dml_exception
     */
    private static function get_url_and_file_id($data, $type, $attemptid, $userid, $courseid, $contextid, $record, $fs): array
    {
        global $DB;

        list(, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        $filename = $type . '-' . $attemptid . '-' . $userid . '-' . $courseid . '-' . time() . rand(1, 1000) . '.png';

        $record->courseid = $courseid;
        $record->filename = $filename;
        $record->contextid = $contextid;
        $record->userid = $userid;

        $fs->create_file_from_string($record, $data);

        $conditions = array(
            'userid'    => $userid,
            'contextid' => $contextid,
            'mimetype'  => 'image/png',
            'component' => 'quizaccess_exproctor',
            'filearea'  => "{$record->filearea}",
            'filename'  => "{$filename}"
        );

        $filerecords = $DB->get_records("files", $conditions);

        $file_id = 0;
        foreach ($filerecords as $filerecord) {
            $file_id = $filerecord->id;
        }

        return array(
            "file_id" => $file_id,
            "url"     => ((moodle_url::make_pluginfile_url(
                $contextid,
                $record->component,
                $record->filearea,
                $record->itemid,
                $record->filepath,
                $record->filename,
                false
            )))
        );
    }

    /**
     * Webcam shots return parameters.
     *
     * @return external_single_structure
     */
    public static function send_webcam_shot_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'id'       => new external_value(PARAM_TEXT, 'webcam shot id'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Webcam shot return parameters.
     *
     * @return external_single_structure
     */
    public static function get_webcam_shot_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'webcamshots' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseid'     => new external_value(PARAM_NOTAGS, 'course id'),
                            'quizid'       => new external_value(PARAM_NOTAGS, 'quiz id'),
                            'userid'       => new external_value(PARAM_NOTAGS, 'user id'),
                            'webcamshot'   => new external_value(PARAM_RAW, 'webcam shot url'),
                            'fileid'       => new external_value(PARAM_RAW, 'file id'),
                            'timecreated'  => new external_value(PARAM_NOTAGS, 'create time of webcam shot'),
                            'timemodified' => new external_value(PARAM_NOTAGS, 'modified time of webcam shot'),
                        )
                    ),
                    'list of webcamshots'
                ),
                'warnings'    => new external_warnings()
            )
        );
    }

    /**
     * Get the webcam shots as service.
     *
     * @param mixed $courseid course id.
     * @param mixed $quizid context/quiz id.
     * @param mixed $userid user id.
     *
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function get_webcam_shot($courseid, $quizid, $userid): array
    {
        global $DB, $USER;

        // Validate the params.
        $params = self::validate_parameters(
            self::get_webcam_shot_parameters(),
            array(
                'courseid' => $courseid,
                'quizid'   => $quizid,
                'userid'   => $userid
            )
        );

        $context = context_module::instance($params['quizid']);

        // Default value for userid.
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        self::request_user_require_capability($params['userid'], $context, $USER);

        $warnings = array();

        $table_name = 'quizaccess_exproctor_wb_logs';
        if ($params['quizid']) {
            $records = $DB->get_records($table_name, $params, 'id DESC');
        } else {
            $records = $DB->get_records($table_name,
                array('courseid' => $courseid,
                      'userid'   => $userid), 'id DESC');
        }

        $returnedwebcamhosts = array();

        foreach ($records as $record) {
            if ($record->webcamshot !== '') {
                $returnedwebcamhosts[] = array(
                    'courseid'     => $record->courseid,
                    'quizid'       => $record->quizid,
                    'userid'       => $record->userid,
                    'webcamshot'   => $record->webcamshot,
                    'fileid'       => $record->fileid,
                    'timecreated'  => $record->timecreated,
                    'timemodified' => $record->timemodified,
                );
            }
        }

        $result = array();
        $result['webcamshots'] = $returnedwebcamhosts;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Set the webcam shots parameters.
     *
     * @return external_function_parameters
     */
    public static function get_webcam_shot_parameters(): external_function_parameters
    {
        global $USER;

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                'quizid'   => new external_value(PARAM_INT, 'quiz id', VALUE_OPTIONAL),
                'userid'   => new external_value(PARAM_INT, 'user id', VALUE_DEFAULT, $USER->id)
            )
        );
    }

    /**
     * Check user permissions
     *
     * @param $userid
     * @param $context
     * @param $USER
     * @return void
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    protected static function request_user_require_capability($userid, $context, $USER): void
    {
        $user = core_user::get_user($userid, '*', MUST_EXIST);
        core_user::require_active_user($user);

        // Extra checks so only users with permissions can view other users reports.
        if ($USER->id != $user->id) {
            require_capability('quizaccess/examproctoring:view_report', $context);
        }
    }

    /**
     * This function store the screen shot
     *
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @param $screenshot
     * @param $bucketName
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function send_screen_shot($courseid, $attemptid, $quizid, $screenshot, $bucketName): array
    {
        $table_name = 'quizaccess_exproctor_sc_logs';

        // Validate the params
        $params = self::validate_parameters(
            self::send_screen_shot_parameters(),
            array(
                'courseid'   => $courseid,
                'attemptid'  => $attemptid,
                'quizid'     => $quizid,
                'screenshot' => $screenshot,
                'bucketName' => $bucketName,
            )
        );

        $params["filearea"] = "screen_shots";

        return self::store_image($params, "screen", $table_name);
    }

    /**
     * Store parameters
     *
     * @return external_function_parameters
     */
    public static function send_screen_shot_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid'   => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                'attemptid'  => new external_value(PARAM_INT, 'attempt id', VALUE_REQUIRED),
                'quizid'     => new external_value(PARAM_INT, 'quiz id', VALUE_REQUIRED),
                'screenshot' => new external_value(PARAM_RAW, 'screen shot', VALUE_REQUIRED),
                'bucketName' => new external_value(PARAM_TEXT, 'S3 Bucket name', VALUE_REQUIRED),
            )
        );
    }

    /**
     * Screen shots return parameters.
     *
     * @return external_single_structure
     */
    public static function send_screen_shot_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'id'       => new external_value(PARAM_INT, 'screen shot id'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Screen shot return parameters.
     *
     * @return external_single_structure
     */
    public static function get_screen_shot_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'screenshots' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'courseid'     => new external_value(PARAM_NOTAGS, 'course id'),
                            'quizid'       => new external_value(PARAM_NOTAGS, 'quiz id'),
                            'userid'       => new external_value(PARAM_NOTAGS, 'user id'),
                            'screenshot'   => new external_value(PARAM_RAW, 'screen shot url'),
                            'fileid'       => new external_value(PARAM_RAW, 'file id'),
                            'timecreated'  => new external_value(PARAM_NOTAGS, 'create time of screen shot'),
                            'timemodified' => new external_value(PARAM_NOTAGS, 'modified time of screen shot'),
                        )
                    ),
                    'list of screenshots'
                ),
                'warnings'    => new external_warnings()
            )
        );
    }

    /**
     * Get the screen shots as service.
     *
     * @param mixed $courseid course id.
     * @param mixed $quizid context/quiz id.
     * @param mixed $userid user id.
     *
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    public static function get_screen_shot($courseid, $quizid, $userid): array
    {
        global $DB, $USER;

        // Validate the params.
        $params = self::validate_parameters(
            self::get_screen_shot_parameters(),
            array(
                'courseid' => $courseid,
                'quizid'   => $quizid,
                'userid'   => $userid
            )
        );

        $context = context_module::instance($params['quizid']);

        self::request_user_require_capability($params['userid'], $context, $USER);

        $warnings = array();

        $table_name = 'quizaccess_exproctor_sc_logs';
        if ($params['quizid']) {
            $records = $DB->get_records($table_name, $params, 'id DESC');
        } else {
            $records = $DB->get_records($table_name,
                array('courseid' => $courseid,
                      'userid'   => $userid), 'id DESC');
        }

        $returnedscreenhosts = array();

        foreach ($records as $record) {
            if ($record->screenshot !== '') {
                $returnedscreenhosts[] = array(
                    'courseid'     => $record->courseid,
                    'quizid'       => $record->quizid,
                    'userid'       => $record->userid,
                    'screenshot'   => $record->screenshot,
                    'fileid'       => $record->fileid,
                    'timecreated'  => $record->timecreated,
                    'timemodified' => $record->timemodified,
                );

            }
        }

        $result = array();
        $result['screenshots'] = $returnedscreenhosts;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Set the screen shot parameters.
     *
     * @return external_function_parameters
     */
    public static function get_screen_shot_parameters(): external_function_parameters
    {
        global $USER;

        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id', VALUE_REQUIRED),
                'quizid'   => new external_value(PARAM_INT, 'quiz id', VALUE_OPTIONAL),
                'userid'   => new external_value(PARAM_INT, 'user id', VALUE_DEFAULT, $USER->id)
            )
        );
    }
}
