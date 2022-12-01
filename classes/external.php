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
     * This function set the status of the quiz in (Webcam-shot table) - (Set quiz status as finished)
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
     * Returns description of method parameters (Webcam-shot table)
     *
     * @return external_function_parameters
     */
    public static function set_wb_quiz_status_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED),
                'attemptid' => new external_value(PARAM_INT, 'Attempt id', VALUE_REQUIRED),
                'quizid' => new external_value(PARAM_INT, 'Quiz id', VALUE_REQUIRED),
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
     * Returns status of current quiz attempt (Webcam-shot table)
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
                'courseid' => new external_value(PARAM_INT, 'Course id', VALUE_REQUIRED),
                'attemptid' => new external_value(PARAM_INT, 'Attempt id', VALUE_REQUIRED),
                'quizid' => new external_value(PARAM_INT, 'Quiz id', VALUE_REQUIRED),
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

    public static function send_webcam_shot($courseid, $attemptid, $quizid, $webcampicture): array
    {
        global $DB, $USER;

        // Validate the params
        self::validate_parameters(
            self::send_webcam_shot_parameters(),
            array(
                'courseid' => $courseid,
                'attemptid' => $attemptid,
                'quizid' => $quizid,
                'webcampicture' => $webcampicture,
            )
        );

        $warnings = array();

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

        // For base64 to file.
        $data = $webcampicture;
        list($type, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        $filename = 'webcam-' . $attemptid . '-' . $USER->id . '-' . $courseid . '-' . time() . rand(1, 1000) . '.png';

        $record->courseid = $courseid;
        $record->filename = $filename;
        $record->contextid = $context->id;
        $record->userid = $USER->id;

        $file = $fs->create_file_from_string($record, $data);

        $filesql = 'SELECT * FROM {files} WHERE userid IN (' . $USER->id . ') AND contextid IN (' . $context->id . ') AND mimetype = \'image/png\' AND component = \'quizaccess_exproctor\' AND filearea = \'picture\' ORDER BY id DESC LIMIT 1';

        $usersfile = $DB->get_records_sql($filesql);

        $file_id = 0;
        foreach ($usersfile as $tempfile):
            $file_id = $tempfile->id;
        endforeach;

        $url = moodle_url::make_pluginfile_url(
            $context->id,
            $record->component,
            $record->filearea,
            $record->itemid,
            $record->filepath,
            $record->filename,
            false
        );

        $camshot = $DB->get_record('quizaccess_exproctor_wb_logs', array('id' => $attemptid));

        $record = new stdClass();
        $record->courseid = $courseid;
        $record->quizid = $quizid;
        $record->userid = $USER->id;
        $record->webcampicture = "{$url}";
        $record->status = $camshot->status;
        $record->fileid = $file_id;
        $record->timemodified = time();
        $attemptid = $DB->insert_record('quizaccess_exproctor_wb_logs', $record, true);

        $result = array();
        $result['attemptid'] = $attemptid;
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
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'attemptid' => new external_value(PARAM_INT, 'Attempt id'),
                'quizid' => new external_value(PARAM_INT, 'Quiz id'),
                'webcamshot' => new external_value(PARAM_RAW, 'webcam picture'),
            )
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
                'attemptid' => new external_value(PARAM_INT, 'webcam shot sent id'),
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
                            'courseid' => new external_value(PARAM_NOTAGS, 'Course id'),
                            'quizid' => new external_value(PARAM_NOTAGS, 'Quiz id'),
                            'userid' => new external_value(PARAM_NOTAGS, 'webcam shot user id'),
                            'webcampicture' => new external_value(PARAM_RAW, 'webcam shot photo'),
                            'timemodified' => new external_value(PARAM_NOTAGS, 'webcam shot time modified'),
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
     * @param mixed $courseid Course id.
     * @param mixed $quizid context/Quiz id.
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

        self::request_user_require_capability($params, $context, $USER);

        $warnings = array();
        if ($params['quizid']) {
            $camshots = $DB->get_records('quizaccess_exproctor_wb_logs', $params, 'id DESC');
        } else {
            $camshots = $DB->get_records('quizaccess_exproctor_wb_logs',
                array('courseid' => $courseid, 'userid' => $userid), 'id DESC');
        }

        $returnedwebcamhosts = array();

        foreach ($camshots as $camshot) {
            if ($camshot->webcampicture !== '') {
                $returnedwebcamhosts[] = array(
                    'courseid' => $camshot->courseid,
                    'quizid' => $camshot->quizid,
                    'userid' => $camshot->userid,
                    'webcampicture' => $camshot->webcampicture,
                    'timemodified' => $camshot->timemodified,
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
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'quizid' => new external_value(PARAM_INT, 'Quiz id'),
                'userid' => new external_value(PARAM_INT, 'webcam shot user id')
            )
        );
    }

    /**
     * Check permissions to view report
     *
     * @param array $params
     * @param context $context
     * @param $USER
     *
     * @return void
     * @throws dml_exception
     * @throws moodle_exception
     * @throws required_capability_exception
     */
    protected static function request_user_require_capability(array $params, context $context, $USER)
    {
        $user = core_user::get_user($params['userid'], '*', MUST_EXIST);
        core_user::require_active_user($user);

        // Extra checks so only users with permissions can view other users reports.
        if ($USER->id != $user->id) {
            require_capability('quizaccess/examproctoring:view_report', $context);
        }
    }

    public static function send_screen_shot($courseid, $screenshotid, $quizid, $screenpicture): array
    {
        global $DB, $USER;

        // Validate the params
        self::validate_parameters(
            self::send_screen_shot_parameters(),
            array(
                'courseid' => $courseid,
                'screenshotid' => $screenshotid,
                'quizid' => $quizid,
                'screenpicture' => $screenpicture,
            )
        );

        $warnings = array();

        $record = new stdClass();
        $record->filearea = 'picture';
        $record->component = 'quizaccess_exproctor';
        $record->filepath = '';
        $record->itemid = $screenshotid;
        $record->license = '';
        $record->author = '';

        $context = context_module::instance($quizid);
        $fs = get_file_storage();
        $record->filepath = file_correct_filepath($record->filepath);

        // For base64 to file.
        $data = $screenpicture;
        list($type, $data) = explode(';', $data);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);
        $filename = 'screen-' . $screenshotid . '-' . $USER->id . '-' . $courseid . '-' . time() . rand(1, 1000) . '.png';

        $record->courseid = $courseid;
        $record->filename = $filename;
        $record->contextid = $context->id;
        $record->userid = $USER->id;

        $file = $fs->create_file_from_string($record, $data);

        $filesql = 'SELECT * FROM {files} WHERE userid IN (' . $USER->id . ') AND contextid IN (' . $context->id . ') AND mimetype = \'image/png\' AND component = \'quizaccess_exproctor\' AND filearea = \'picture\' ORDER BY id DESC LIMIT 1';

        $usersfile = $DB->get_records_sql($filesql);

        $file_id = 0;
        foreach ($usersfile as $tempfile):
            $file_id = $tempfile->id;
        endforeach;

        $url = moodle_url::make_pluginfile_url(
            $context->id,
            $record->component,
            $record->filearea,
            $record->itemid,
            $record->filepath,
            $record->filename,
            false
        );

        $camshot = $DB->get_record('quizaccess_exproctor_sc_logs', array('id' => $screenshotid));

        $record = new stdClass();
        $record->courseid = $courseid;
        $record->quizid = $quizid;
        $record->userid = $USER->id;
        $record->screenpicture = "{$url}";
        $record->status = $camshot->status;
        $record->fileid = $file_id;
        $record->timemodified = time();
        $screenshotid = $DB->insert_record('quizaccess_exproctor_sc_logs', $record, true);

        $result = array();
        $result['screenshotid'] = $screenshotid;
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
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'screenshotid' => new external_value(PARAM_INT, 'screen shot id'),
                'quizid' => new external_value(PARAM_INT, 'Quiz id'),
                'screenpicture' => new external_value(PARAM_RAW, 'screen picture'),
            )
        );
    }

    /**
     * Webcam shots return parameters.
     *
     * @return external_single_structure
     */
    public static function send_screen_shot_returns(): external_single_structure
    {
        return new external_single_structure(
            array(
                'screenshotid' => new external_value(PARAM_INT, 'screen shot sent id'),
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Webcam shot return parameters.
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
                            'courseid' => new external_value(PARAM_NOTAGS, 'Course id'),
                            'quizid' => new external_value(PARAM_NOTAGS, 'Quiz id'),
                            'userid' => new external_value(PARAM_NOTAGS, 'screen shot user id'),
                            'screenpicture' => new external_value(PARAM_RAW, 'screen shot photo'),
                            'timemodified' => new external_value(PARAM_NOTAGS, 'screen shot time modified'),
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
     * @param mixed $courseid Course id.
     * @param mixed $quizid context/Quiz id.
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

        self::request_user_require_capability($params, $context, $USER);

        $warnings = array();
        if ($params['quizid']) {
            $camshots = $DB->get_records('quizaccess_exproctor_sc_logs', $params, 'id DESC');
        } else {
            $camshots = $DB->get_records('quizaccess_exproctor_sc_logs',
                array('courseid' => $courseid, 'userid' => $userid), 'id DESC');
        }

        $returnedscreenhosts = array();

        foreach ($camshots as $camshot) {
            if ($camshot->screenpicture !== '') {
                $returnedscreenhosts[] = array(
                    'courseid' => $camshot->courseid,
                    'quizid' => $camshot->quizid,
                    'userid' => $camshot->userid,
                    'screenpicture' => $camshot->screenpicture,
                    'timemodified' => $camshot->timemodified,
                );

            }
        }

        $result = array();
        $result['screenshots'] = $returnedscreenhosts;
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Set the cam shots parameters.
     *
     * @return external_function_parameters
     */
    public static function get_screen_shot_parameters(): external_function_parameters
    {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'quizid' => new external_value(PARAM_INT, 'Quiz id'),
                'userid' => new external_value(PARAM_INT, 'screen shot user id')
            )
        );
    }
}
