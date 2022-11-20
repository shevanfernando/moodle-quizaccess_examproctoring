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
class quizaccess_exproctor extends quiz_access_rule_base
{
    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * There is no obligation to return anything. If it is not appropriate to tell students
     * about this rule, then just return ''.
     *
     * @param quiz $quizobj
     * @param int $timenow
     * @param bool $canignoretimelimits
     * @return quiz_access_rule_base|quizaccess_exproctor|null
     */
    public static function make(quiz $quizobj, $timenow, $canignoretimelimits)
    {
        if (empty($quizobj->get_quiz()->webcamproctoringrequired) && empty($quizobj->get_quiz()->screenproctoringrequired)) {
            return null;
        }
        return new self($quizobj, $timenow);
    }

    /**
     * Add any fields that this rule requires to the quiz settings form. This
     * method is called from mod_quiz_mod_form::definition(), while the
     * security section is being built.
     *
     * @param mod_quiz_mod_form $quizform the quiz settings form that is being built.
     * @param MoodleQuickForm $mform the wrapped MoodleQuickForm.
     * @throws coding_exception
     */
    public static function add_settings_form_fields(mod_quiz_mod_form $quizform, MoodleQuickForm $mform)
    {
        // this only for debug the code.
        // TODO: Remove this before push the code into git hub
        get_string_manager()->reset_caches();

        $mform->addElement('select', 'webcamproctoringrequired',
            get_string('webcamproctoringrequired', 'quizaccess_exproctor'),
            array(
                0 => get_string('setting:not_required', 'quizaccess_exproctor'),
                1 => get_string('setting:proctoring_required_option', 'quizaccess_exproctor'),
            ));
        $mform->addHelpButton('webcamproctoringrequired', 'webcamproctoringrequired', 'quizaccess_exproctor');
        $mform->addElement('select', 'screenproctoringrequired',
            get_string('screenproctoringrequired', 'quizaccess_exproctor'),
            array(
                0 => get_string('setting:not_required', 'quizaccess_exproctor'),
                1 => get_string('setting:proctoring_required_option', 'quizaccess_exproctor'),
            ));
        $mform->addHelpButton('screenproctoringrequired', 'screenproctoringrequired', 'quizaccess_exproctor');
        $mform->addElement('select', 'proctoringmethod',
            get_string('setting:proctoring_method', 'quizaccess_exproctor'),
            array(
                0 => get_string('setting:proctoring_method_one', 'quizaccess_exproctor'),
                1 => get_string('setting:proctoring_method_sec', 'quizaccess_exproctor'),
                2 => get_string('setting:proctoring_method_three', 'quizaccess_exproctor'),
                3 => get_string('setting:proctoring_method_four', 'quizaccess_exproctor'),
            ));
        $mform->addHelpButton('proctoringmethod', 'proctoringmethod', 'quizaccess_exproctor');
        $mform->addElement('text', 'screenshotdelay', get_string('setting:screenshot_delay', 'quizaccess_exproctor'));
        $mform->setDefault('screenshotdelay', 30000);
        $mform->addHelpButton('screenshotdelay', 'screenshotdelay', 'quizaccess_exproctor');
        $mform->addElement('text', 'screenshotwidth', get_string('setting:screenshot_width', 'quizaccess_exproctor'));
        $mform->setDefault('screenshotwidth', 230);
        $mform->addHelpButton('screenshotwidth', 'screenshotwidth', 'quizaccess_exproctor');
    }

    /**
     * Save any submitted settings when the quiz settings form is submitted. This
     * is called from quiz_after_add_or_update() in lib.php.
     *
     * @param object $quiz the data from the quiz form, including $quiz->id
     *      which is the id of the quiz being saved.
     * @throws dml_exception
     */
    public static function save_settings($quiz)
    {
        global $DB;
        $is_webcam_proctoring_required = $quiz->webcamproctoringrequired;
        $is_screen_proctoring_required = $quiz->screenproctoringrequired;
        $proctoring_method = $quiz->proctoringmethod;
        $screenshot_delay = $quiz->screenshotdelay;
        $screenshot_width = $quiz->screenshotwidth;
        if (empty($is_webcam_proctoring_required) && empty($is_screen_proctoring_required)) {
            $DB->delete_records('quizaccess_exproctor', array('quizid' => $quiz->id));
        } else {
            if (!$DB->record_exists('quizaccess_exproctor', array('quizid' => $quiz->id))) {
                $record = new stdClass();
                $record->quizid = $quiz->id;
                $record->webcamproctoringrequired = $is_webcam_proctoring_required;
                $record->screenproctoringrequired = $is_screen_proctoring_required;
                $record->proctoringmethod = $proctoring_method;
                $record->screenshotdelay = $screenshot_delay;
                $record->screenshotwidth = $screenshot_width;
                $DB->insert_record('quizaccess_exproctor', $record);
            } else {
                $record = $DB->get_record('quizaccess_exproctor', array('quizid' => $quiz->id));
                $record->webcamproctoringrequired = $is_webcam_proctoring_required;
                $record->screenproctoringrequired = $is_screen_proctoring_required;
                $record->proctoringmethod = $proctoring_method;
                $record->screenshotdelay = $screenshot_delay;
                $record->screenshotwidth = $screenshot_width;
                $DB->update_record('quizaccess_exproctor', $record);
            }
        }
    }

    /**
     * Delete any rule-specific settings when the quiz is deleted. This is called
     * from quiz_delete_instance() in lib.php.
     *
     * @param object $quiz the data from the database, including $quiz->id
     *      which is the id of the quiz being deleted.
     * @throws dml_exception
     */
    public static function delete_settings($quiz)
    {
        global $DB;
        $DB->delete_records('quizaccess_exproctor', array('quizid' => $quiz->id));
    }

    /**
     * Return the bits of SQL needed to load all the settings from all the access
     * plugins in one DB query. The easiest way to understand what you need to do
     * here is probalby to read the code of quiz_access_manager::load_settings().
     *
     * If you have some settings that cannot be loaded in this way, then you can
     * use the get_extra_settings() method instead, but that has
     * performance implications.
     *
     * @param int $quizid the id of the quiz we are loading settings for. This
     *     can also be accessed as quiz.id in the SQL. (quiz is a table alisas for {quiz}.)
     * @return array with three elements:
     *     1. fields: any fields to add to the select list. These should be alised
     *        if neccessary so that the field name starts the name of the plugin.
     *     2. joins: any joins (should probably be LEFT JOINS) with other tables that
     *        are needed.
     *     3. params: array of placeholder values that are needed by the SQL. You must
     *        used named placeholders, and the placeholder names should start with the
     *        plugin name, to avoid collisions.
     */
    public static function get_settings_sql($quizid): array
    {
        return array(
            'exproctor.webcamproctoringrequired,' . 'exproctor.screenproctoringrequired,' . 'exproctor.proctoringmethod,' . 'exproctor.screenshotdelay,' . 'exproctor.screenshotwidth',
            'LEFT JOIN {quizaccess_exproctor} exproctor ON exproctor.quizid = quiz.id',
            array());
    }

    /**
     * Check is preflight check is required.
     *
     * @param mixed $attemptid
     * @return bool
     */
    public function is_preflight_check_required($attemptid): bool
    {
        return empty($attemptid);
    }

    /**
     * add_preflight_check_form_fields
     *
     * @param mod_quiz_preflight_check_form $quizform
     * @param MoodleQuickForm $mform
     * @param mixed $attemptid
     * @return void
     * @throws coding_exception
     */
    public function add_preflight_check_form_fields(mod_quiz_preflight_check_form $quizform, MoodleQuickForm $mform, $attemptid)
    {
        global $PAGE;
        $data = $this->get_quiz_details();

        $data["is_quiz_started"] = false;

        $PAGE->requires->js_call_amd('quizaccess_exproctor/proctoring', 'webcam_proctoring', array($data));

        // this only for debug the code.
        // TODO: Remove this before push the code into git hub
        get_string_manager()->reset_caches();

        if ((bool)$data["webcamproctoringrequired"] && (bool)$data["screenproctoringrequired"]) {
            $this->get_screen_proctoring_form_fields($mform);
            $this->get_webcam_proctoring_form_fields($mform);
        } elseif ((bool)$data["screenproctoringrequired"]) {
            $this->get_screen_proctoring_form_fields($mform);
        } elseif ((bool)$data["webcamproctoringrequired"]) {
            $this->get_webcam_proctoring_form_fields($mform);
        };
    }

    /**
     * Get followings,
     * - screenproctoringrequired
     * - webcamproctoringrequired
     *
     * @return array
     *
     * @throws coding_exception
     */
    private function get_quiz_details(): array
    {
        $response = [];
        $response['cmid'] = $this->quiz->cmid;
        $response['courseid'] = $this->quiz->course;
        $response['quizid'] = $this->quiz->id;
        $response['screenproctoringrequired'] = $this->quiz->screenproctoringrequired;
        $response['webcamproctoringrequired'] = $this->quiz->webcamproctoringrequired;

        return $response;
    }

    private function get_screen_proctoring_form_fields($mform)
    {
        $mform->addElement('header', 'screenproctoringheader', get_string('openscreen', 'quizaccess_exproctor'));
        $mform->addElement('static', 'screenproctoringmessage', '', get_string('screenproctoringstatement', 'quizaccess_exproctor'));
    }

    private function get_webcam_proctoring_form_fields($mform)
    {
        $mform->addElement('header', 'webproctoringheader', get_string('openwebcam', 'quizaccess_exproctor'));
        $mform->addElement('static', 'webproctoringmessage', '', get_string('webcamproctoringstatement', 'quizaccess_exproctor'));
        $mform->addElement('static', 'cammessage', '', get_string('camhtml', 'quizaccess_exproctor'));
        $mform->addElement('checkbox', 'proctoring', '', get_string('proctoringlabel', 'quizaccess_exproctor'));
    }

    /**
     * Validate the preflight check
     *
     * @param mixed $data
     * @param mixed $files
     * @param mixed $errors
     * @param mixed $attemptid
     * @return mixed $errors
     * @throws coding_exception
     */
    public function validate_preflight_check($data, $files, $errors, $attemptid)
    {
        if (empty($data['proctoring'])) {
            $errors['proctoring'] = get_string('youmustagree', 'quizaccess_exproctor');
        }
        return $errors;
    }

    /**
     * Information, such as might be shown on the quiz view page, relating to this restriction.
     * There is no obligation to return anything. If it is not appropriate to tell students
     * about this rule, then just return ''.
     *
     * @return mixed a message, or array of messages, explaining the restriction
     *         (may be '' if no message is appropriate).
     * @throws coding_exception
     */
    public function description()
    {
        $data = $this->get_quiz_details();

        $proctoring_method = "";

        if ((bool)$data["webcamproctoringrequired"] && (bool)$data["screenproctoringrequired"]) {
            $proctoring_method = "webcam & screen";
        } elseif ((bool)$data["screenproctoringrequired"]) {
            $proctoring_method = "screen";
        } elseif ((bool)$data["webcamproctoringrequired"]) {
            $proctoring_method = "webcam";
        }

        $messages = [get_string('proctoringheader', 'quizaccess_exproctor', $proctoring_method)];

        $messages[] = $this->get_download_config_button();

        return $messages;
    }

    /**
     * Get a button to view the Proctoring report.
     *
     * @return string A link to view report
     * @throws coding_exception
     */
    private function get_download_config_button(): string
    {
        global $OUTPUT, $USER;

        $context = context_module::instance($this->quiz->cmid, MUST_EXIST);
        if (has_capability('quizaccess/exproctor:view_report', $context, $USER->id)) {
            # create a report.php
//            $httplink = \quizaccess_exproctor\link_generator::get_link($this->quiz->course, $this->quiz->cmid, false, is_https());

            return $OUTPUT->single_button('www.google.lk', get_string('picturesreport', 'quizaccess_exproctor'), 'get');
        }
        return '';
    }

    /**
     * Sets up the attempt (review or summary) page with any special extra
     * properties required by this rule.
     *
     * @param moodle_page $page the page object to initialise.
     * @throws coding_exception
     * @throws dml_exception
     */
    public function setup_attempt_page($page)
    {
        $cmid = optional_param('cmid', '', PARAM_INT);
        $attempt = optional_param('attempt', '', PARAM_INT);

        $page->set_title($this->quizobj->get_course()->shortname . ': ' . $page->title);
        $page->set_popup_notification_allowed(false); // Prevent message notifications.
        $page->set_heading($page->title);

        global $DB, $COURSE, $USER;
        if ($cmid) {
            $contextquiz = $DB->get_record('course_modules', array('id' => $cmid));

            $record = new stdClass();
            $record->courseid = $COURSE->id;
            $record->quizid = $contextquiz->id;
            $record->userid = $USER->id;
            $record->webcampicture = '';
            $record->status = $attempt;
            $record->timemodified = time();
            echo "<h1>First</h1>";
            var_dump($record);
            $record->id = $DB->insert_record('quizaccess_exproctor_wb_logs', $record, true);

            //////// Get Image Frequency and Image Width ////////
            $imagefrequencysql = "SELECT * FROM {config_plugins} WHERE plugin = 'quizaccess_exproctor' AND name = 'autoreconfigurefrequency'";
            $frequencydata = $DB->get_recordset_sql($imagefrequencysql);

            $frequency = 3 * 1000;
            if (count($frequencydata) > 0) {
                foreach ($frequencydata as $row) {
                    $frequency = (int)$row->value * 1000;
                }
            }

            $imagesizesql = "SELECT * FROM {config_plugins} WHERE plugin = 'quizaccess_exproctor' AND name = 'autoreconfigureimagewidth'";
            $imagesizedata = $DB->get_recordset_sql($imagesizesql);

            $image_width = 230;
            if (count($imagesizedata) > 0) {
                foreach ($imagesizedata as $row) {
                    $image_width = $row->value;
                }
            }

            $record->frequency = $frequency;
            $record->image_width = $image_width;

            $record->is_quiz_started = true;
            var_dump($record);
//            die();
//            $page->requires->js_call_amd('quizaccess_exproctor/proctoring', 'webcam_proctoring', array($record));
        }
    }
}
