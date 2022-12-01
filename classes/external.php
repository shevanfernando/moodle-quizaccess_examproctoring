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

require_once("$CFG->libdir/externallib.php");

class quizaccess_exproctor_external extends external_api
{
    /**
     * This function set the status of the quiz in (webcam shot table) - (Set quiz status as finished)
     *
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @return bool true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_wb_quiz_status($courseid, $attemptid, $quizid): bool
    {
        // Validate the params
        self::validate_parameters(
            self::set_wb_quiz_status_parameters(),
            array(
                'courseid' => $courseid,
                'attemptid' => $attemptid,
                'quizid' => $quizid
            )
        );

        $data = self::updated_quiz_status('quizaccess_exproctor_wb_logs', $courseid, $attemptid, $quizid);

        var_dump($data);
        die();

        return true;
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
                'attemptid' => new external_value(PARAM_INT, 'attempt id', VALUE_REQUIRED),
                'quizid' => new external_value(PARAM_INT, 'quiz id', VALUE_REQUIRED),
            )
        );
    }

    /**
     * This function set the current quiz status as finished
     *
     * @param $tablename
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @return bool
     * @throws dml_exception
     */
    private static function updated_quiz_status($tablename, $courseid, $attemptid, $quizid): bool
    {
        global $DB, $USER;

        $conditions = array('courseid' => $courseid, 'attemptid' => $attemptid, 'quizid' => $quizid, 'userid' => $USER->id, 'isquizfinished' => false);

        $data = $DB->set_field($tablename, 'isquizfinished', true, $conditions);

        return $data;
    }

    /**
     * Returns status of current quiz attempt (webcam shot table)
     *
     * @return external_description
     */
    public static function set_wb_quiz_status_returns()
    {
        return new external_value(PARAM_BOOL, 'current quiz attempt status updated status');
    }

    /**
     * This function set the status of the quiz in (Screen-shot table) - (Set quiz status as finished)
     *
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @return bool true
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function set_sc_quiz_status($courseid, $attemptid, $quizid): bool
    {
        // Validate the params
        self::validate_parameters(
            self::set_sc_quiz_status_parameters(),
            array(
                'courseid' => $courseid,
                'attemptid' => $attemptid,
                'quizid' => $quizid
            )
        );

        $data = self::updated_quiz_status('quizaccess_exproctor_sc_logs', $courseid, $attemptid, $quizid);

        return $data;
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
                'attemptid' => new external_value(PARAM_INT, 'attempt id', VALUE_REQUIRED),
                'quizid' => new external_value(PARAM_INT, 'quiz id', VALUE_REQUIRED),
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
        return new external_value(PARAM_BOOL, 'current quiz attempt status updated status');
    }

    /**
     * This function store the webcam shot
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @param $webcamshot
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function send_webcam_shot($courseid, $attemptid, $quizid, $webcamshot): array
    {
        global $DB, $USER;

        $table_name = 'quizaccess_exproctor_wb_logs';

        // Validate the params
        self::validate_parameters(
            self::send_webcam_shot_parameters(),
            array(
                'courseid' => $courseid,
                'attemptid' => $attemptid,
                'quizid' => $quizid,
                'webcamshot' => $webcamshot,
            )
        );

        // get last record and check the quiz is finished or not
        $conditions = array('courseid' => $courseid, 'attemptid' => $attemptid, 'quizid' => $quizid, 'userid' => $USER->id, 'isquizfinished' => true);

        $warnings = array();
        $id = null;

        $number_of_records = $DB->count_records($table_name, $conditions);

        if ($number_of_records == 0) {
            $record = new stdClass();
            $record->filearea = 'picture';
            $record->component = 'quizaccess_exproctor';
            $record->filepath = '';
            $record->itemid = $attemptid;
            $record->license = '';
            $record->author = '';

            $context = context_module::instance($quizid);

            $fs = get_file_storage();
            $record->filepath = file_correct_filepath($record->filepath);

            $data = self::get_url_and_file_id($webcamshot, 'webcam', $attemptid, $USER->id, $courseid, $context->id, $record, $fs);

            $record = new stdClass();
            $record->courseid = $courseid;
            $record->quizid = $quizid;
            $record->userid = $USER->id;
            $record->webcamshot = "{$data->url}";
            $record->status = $attemptid;
            $record->fileid = $data->file_id;
            $record->timecreated = time();
            $record->timemodified = time();
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
     * Store parameters
     *
     * @return external_function_parameters
     */
    public static function send_webcam_shot_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'attemptid' => new external_value(PARAM_INT, 'attempt id'),
                'quizid' => new external_value(PARAM_INT, 'quiz id'),
                'webcamshot' => new external_value(PARAM_RAW, 'webcam shot'),
            )
        );
    }

    /**
     * This function generates URL for images
     *
     * @param $data
     * @param $type
     * @param $attemptid
     * @param $userId
     * @param $courseid
     * @param $contextid
     * @param $record
     * @param $fs
     * @return moodle_url
     */
    private static function get_url_and_file_id($data, $type, $attemptid, $userId, $courseid, $contextid, $record, $fs): array
    {
        global $DB;

        list(, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        $filename = $type . '-' . $attemptid . '-' . $userId . '-' . $courseid . '-' . time() . rand(1, 1000) . '.png';

        $record->courseid = $courseid;
        $record->filename = $filename;
        $record->contextid = $contextid;
        $record->userid = $userId;

        $fs->create_file_from_string($record, $data);

        $filesql = 'SELECT * FROM {files} WHERE userid IN (' . $userId . ') AND contextid IN (' . $contextid . ') AND mimetype = \'image/png\' AND component = \'quizaccess_exproctor\' AND filearea = \'picture\' AND filename IN (' . $filename . ') ORDER BY id DESC LIMIT 1';

        $filerecord = $DB->get_records_sql($filesql);

        $file_id = 0;
        if (!empty($filerecord)) {
            $file_id = $filerecord->id;
        }

        return array(
            "file_id" => $file_id,
            "url" => (moodle_url::make_pluginfile_url(
                $contextid,
                $record->component,
                $record->filearea,
                $record->itemid,
                $record->filepath,
                $record->filename,
                false
            )));
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
                'id' => new external_value(PARAM_INT, 'webcam screen shot id'),
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
                            'courseid' => new external_value(PARAM_NOTAGS, 'course id'),
                            'quizid' => new external_value(PARAM_NOTAGS, 'quiz id'),
                            'userid' => new external_value(PARAM_NOTAGS, 'user id'),
                            'webcamshot' => new external_value(PARAM_RAW, 'webcam shot url'),
                            'fileid' => new external_value(PARAM_RAW, 'file id'),
                            'timecreated' => new external_value(PARAM_NOTAGS, 'create time of webcam shot'),
                            'timemodified' => new external_value(PARAM_NOTAGS, 'modified time of webcam shot'),
                        )
                    ),
                    'list of webcamshots'
                ),
                'warnings' => new external_warnings()
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

        $params = array(
            'courseid' => $courseid,
            'quizid' => $quizid,
            'userid' => $userid
        );

        // Validate the params.
        self::validate_parameters(self::get_webcam_shot_parameters(), $params);

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
                array('courseid' => $courseid, 'userid' => $userid), 'id DESC');
        }

        $returnedwebcamhosts = array();

        foreach ($records as $record) {
            if ($record->webcamshot !== '') {
                $returnedwebcamhosts[] = array(
                    'courseid' => $record->courseid,
                    'quizid' => $record->quizid,
                    'userid' => $record->userid,
                    'webcamshot' => $record->webcamshot,
                    'fileid' => $record->fileid,
                    'timecreated' => $record->timecreated,
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
     * Set the cam shots parameters.
     *
     * @return external_function_parameters
     */
    public static function get_webcam_shot_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id', NULL_NOT_ALLOWED),
                'quizid' => new external_value(PARAM_INT, 'quiz id'),
                'userid' => new external_value(PARAM_INT, 'user id')
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
     * @param $courseid
     * @param $attemptid
     * @param $quizid
     * @param $webcamshot
     * @return array
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function send_screen_shot($courseid, $attemptid, $quizid, $screenshot): array
    {
        global $DB, $USER;

        $table_name = 'quizaccess_exproctor_sc_logs';

        // Validate the params
        self::validate_parameters(
            self::send_webcam_shot_parameters(),
            array(
                'courseid' => $courseid,
                'attemptid' => $attemptid,
                'quizid' => $quizid,
                'screenshot' => $screenshot,
            )
        );

        // get last record and check the quiz is finished or not
        $conditions = array('courseid' => $courseid, 'attemptid' => $attemptid, 'quizid' => $quizid, 'userid' => $USER->id, 'isquizfinished' => true);

        $warnings = array();
        $id = null;

        $number_of_records = $DB->count_records($table_name, $conditions);

        if ($number_of_records == 0) {
            $record = new stdClass();
            $record->filearea = 'picture';
            $record->component = 'quizaccess_exproctor';
            $record->filepath = '';
            $record->itemid = $attemptid;
            $record->license = '';
            $record->author = '';

            $context = context_module::instance($quizid);

            $fs = get_file_storage();
            $record->filepath = file_correct_filepath($record->filepath);

            $data = self::get_url_and_file_id($screenshot, 'screen', $attemptid, $USER->id, $courseid, $context->id, $record, $fs);

            $record = new stdClass();
            $record->courseid = $courseid;
            $record->quizid = $quizid;
            $record->userid = $USER->id;
            $record->screenshot = "{$data->url}";
            $record->status = $attemptid;
            $record->fileid = $data->file_id;
            $record->timecreated = time();
            $record->timemodified = time();
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
     * Store parameters
     *
     * @return external_function_parameters
     */
    public static function send_screen_shot_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id'),
                'attemptid' => new external_value(PARAM_INT, 'attempt id'),
                'quizid' => new external_value(PARAM_INT, 'quiz id'),
                'screenshot' => new external_value(PARAM_RAW, 'screen shot'),
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
                'id' => new external_value(PARAM_INT, 'screen shot id'),
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
                            'courseid' => new external_value(PARAM_NOTAGS, 'course id'),
                            'quizid' => new external_value(PARAM_NOTAGS, 'quiz id'),
                            'userid' => new external_value(PARAM_NOTAGS, 'user id'),
                            'screenshot' => new external_value(PARAM_RAW, 'screen shot url'),
                            'fileid' => new external_value(PARAM_RAW, 'file id'),
                            'timecreated' => new external_value(PARAM_NOTAGS, 'create time of screen shot'),
                            'timemodified' => new external_value(PARAM_NOTAGS, 'modified time of screen shot'),
                        )
                    ),
                    'list of screenshots'
                ),
                'warnings' => new external_warnings()
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

        $params = array(
            'courseid' => $courseid,
            'quizid' => $quizid,
            'userid' => $userid
        );

        // Validate the params.
        self::validate_parameters(self::get_screen_shot_parameters(), $params);

        $context = context_module::instance($params['quizid']);

        // Default value for userid.
        if (empty($params['userid'])) {
            $params['userid'] = $USER->id;
        }

        self::request_user_require_capability($params['userid'], $context, $USER);

        $warnings = array();

        $table_name = 'quizaccess_exproctor_sc_logs';
        if ($params['quizid']) {
            $records = $DB->get_records($table_name, $params, 'id DESC');
        } else {
            $records = $DB->get_records($table_name,
                array('courseid' => $courseid, 'userid' => $userid), 'id DESC');
        }

        $returnedscreenhosts = array();

        foreach ($records as $record) {
            if ($record->screenshot !== '') {
                $returnedscreenhosts[] = array(
                    'courseid' => $record->courseid,
                    'quizid' => $record->quizid,
                    'userid' => $record->userid,
                    'screenshot' => $record->screenshot,
                    'fileid' => $record->fileid,
                    'timecreated' => $record->timecreated,
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
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'course id', NULL_NOT_ALLOWED),
                'quizid' => new external_value(PARAM_INT, 'quiz id'),
                'userid' => new external_value(PARAM_INT, 'user id')
            )
        );
    }
}
