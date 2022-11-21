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

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class quizaccess_exproctor_external extends external_api
{

    public static function send_webcam_shot($courseid, $webcamshotid, $quizid, $webcampicture): array
    {
        global $DB, $USER;

        // Validate the params
        self::validate_parameters(
            self::send_webcam_shot_parameters(),
            array(
                'courseid' => $courseid,
                'webcamshotid' => $webcamshotid,
                'quizid' => $quizid,
                'webcampicture' => $webcampicture,
            )
        );

        $warnings = array();

        $record = new stdClass();
        $record->filearea = 'picture';
        $record->component = 'quizaccess_exproctor';
        $record->filepath = '';
        $record->itemid = $webcamshotid;
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
        $filename = 'webcam-' . $webcamshotid . '-' . $USER->id . '-' . $courseid . '-' . time() . rand(1, 1000) . '.png';

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

        $camshot = $DB->get_record('quizaccess_exproctor_wb_logs', array('id' => $webcamshotid));

        $record = new stdClass();
        $record->courseid = $courseid;
        $record->quizid = $quizid;
        $record->userid = $USER->id;
        $record->webcampicture = "{$url}";
        $record->status = $camshot->status;
        $record->fileid = $file_id;
        $record->timemodified = time();
        $webcamshotid = $DB->insert_record('quizaccess_exproctor_wb_logs', $record, true);

        $result = array();
        $result['webcamshotid'] = $webcamshotid;
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
                'webcamshotid' => new external_value(PARAM_INT, 'webcam shot id'),
                'quizid' => new external_value(PARAM_INT, 'quiz id'),
                'webcampicture' => new external_value(PARAM_RAW, 'webcam picture'),
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
                'webcamshotid' => new external_value(PARAM_INT, 'webcam shot sent id'),
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
                            'courseid' => new external_value(PARAM_NOTAGS, 'webcam shot course id'),
                            'quizid' => new external_value(PARAM_NOTAGS, 'webcam shot quiz id'),
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
                'courseid' => new external_value(PARAM_INT, 'webcam shot course id'),
                'quizid' => new external_value(PARAM_INT, 'webcam shot quiz id'),
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
}
