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
 * Report for the quizaccess_exproctor plugin.
 *
 * @package     quizaccess_exproctor
 * @copyright   2022 Shevan Thiranja Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG, $DB, $PAGE, $OUTPUT, $USER;

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/lib/tablelib.php');

require_once($CFG->dirroot . '/mod/quiz/accessrule/exproctor/classes/aws_s3.php');
require_once($CFG->dirroot . '/mod/quiz/accessrule/exproctor/classes/exproctor_evidence.php');

use quizaccess_exproctor\aws_s3;
use quizaccess_exproctor\exproctor_evidence;

abstract class LogAction {
    const VIEW_ALL = 0;
    const VIEW_SINGLE = 1;
    const DELETE_ALL = 2;
    const DELETE_SINGLE = 3;
}

try {
    // Get vars.
    $courseid = (int) required_param('courseid', PARAM_INT);
    $cmid = (int) required_param('cmid', PARAM_INT);
    $quizid = (int) required_param('quizid', PARAM_INT);
    $studentid = (int) optional_param('studentid', '', PARAM_INT);
    $reportid = (int) optional_param('reportid', '', PARAM_INT);
    $logaction = (int) optional_param('logaction', '', PARAM_INT);
    $evidencetype = optional_param('evidencetype', '', PARAM_TEXT);

    $context = context_module::instance($cmid, MUST_EXIST);

    list ($course, $cm) = get_course_and_cm_from_cmid($cmid, 'quiz');

    require_login($course, true, $cm);

    $COURSE = $DB->get_record('course', array('id' => $courseid));
    $quiz = $DB->get_record('quiz', array('id' => $cm->instance));

    $params =
        array('courseid' => $courseid, 'quizid' => $quizid, 'cmid' => $cmid);

    if ($studentid) {
        $params['studentid'] = $studentid;
    }

    if ($logaction) {
        $params['logaction'] = $logaction;
    }

    get_string_manager()->reset_caches();

    $url = new moodle_url('/mod/quiz/accessrule/exproctor/report.php', $params);

    $PAGE->set_url($url);
    $PAGE->set_pagelayout('course');
    $PAGE->set_title($COURSE->shortname . ': ' . get_string('pluginname',
            'quizaccess_exproctor'));
    $PAGE->set_heading($COURSE->fullname . ': ' . get_string('pluginname',
            'quizaccess_exproctor'));

    $PAGE->navbar->add(get_string('pluginname', 'quizaccess_exproctor'), $url);

    echo $OUTPUT->header();
    echo "<div id='main'><h2>" . get_string('proctoring_reports', 'quizaccess_exproctor') . " " . $quiz->name .
        "</h2><div class='box generalbox m-b-1 adminerror alert alert-info p-y-1'>" . get_string('proctoring_reports_desc',
            'quizaccess_exproctor') . "</div>";

    // Delete evidence.
    if (has_capability('quizaccess/exproctor:delete_evidence', $context,
        $USER->id)) {
        $conditions = array();

        if ($logaction == LogAction::DELETE_SINGLE) {
            exproctor_evidence::delete_evidence_by_id($reportid);

            // Redirect to the report page.
            redirect(new moodle_url('/mod/quiz/accessrule/exproctor/report.php',
                array(
                    'courseid' => $courseid,
                    'quizid' => $quizid,
                    'studentid' => $studentid,
                    'cmid' => $cmid,
                    'logaction' => LogAction::VIEW_SINGLE
                )), ucwords($evidencetype) . " image is successfully deleted!",
                -11);
        }

        if ($logaction == LogAction::DELETE_ALL) {
            exproctor_evidence
                ::delete_evidences_by_quizid_and_courseid_and_userid($quizid,
                    $courseid, $studentid, $evidencetype);

            // Redirect to the report page.
            redirect(new moodle_url('/mod/quiz/accessrule/exproctor/report.php',
                array(
                    'courseid' => $courseid,
                    'quizid' => $quizid,
                    'cmid' => $cmid
                )), ucwords($evidencetype) . " images are successfully deleted!",
                -11);
        }
    } else {
        echo "<div class='box generalbox m-b-1 adminerror alert alert-danger p-y-1'>" . get_string('no_permission_to_delete_report',
                'quizaccess_exproctor') . "</div>";
    }

    // View evidence.
    if (has_capability('quizaccess/exproctor:view_report', $context,
        $USER->id)) {

        if ($logaction == LogAction::VIEW_ALL) {
            $evidences = exproctor_evidence
                ::get_unique_evidences_by_quizid_and_courseid($quizid,
                    $courseid);

            if (empty($evidences)) {
                echo "<div class='box generalbox m-b-1 adminerror alert alert-primary p-y- 1'>" . get_string('no_evidence_report',
                        'quizaccess_exproctor') . "</div>";
            } else {
                // Print report.
                $table =
                    new flexible_table('exproctor - report - ' . $COURSE->id . ' - ' . $cmid);

                $table->define_columns(array(
                    'fullname', 'email', 'evidencetype', 'dateverified',
                    'actions'
                ));

                $table->define_headers(array(
                    get_string('user'), get_string('email'),
                    get_string('evidencetype', 'quizaccess_exproctor'),
                    get_string('dateverified', 'quizaccess_exproctor'),
                    get_string('actions', 'quizaccess_exproctor')
                ));

                $table->define_baseurl($url);

                $table->set_attribute('class',
                    'generaltable generalbox reporttable');
                $table->setup();

                foreach ($evidences as $info) {
                    $data = array();
                    $data[] =
                        '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $info->get("userid") . '&course=' . $courseid .
                        '"target="_blank">' . $info->get("firstname") . ' ' . $info->get("lastname") . '</a>';

                    $data[] = $info->get("email");

                    $data[] = $info->get("evidencetype");

                    $data[] = date("Y/m/d H:m:s", $info->get("timecreated"));

                    $data[] =
                        "<a href='?courseid=" . $courseid . "&quizid=" . $quizid . "&studentid=" . $info->get("userid") . "&cmid=" .
                        $cmid . "&logaction=" . LogAction::VIEW_SINGLE . "'>"
                        . get_string("view_report",
                            "quizaccess_exproctor") . "</a>";

                    $table->add_data($data);
                }

                $table->finish_html();
            }
        }

        if ($logaction == LogAction::VIEW_SINGLE) {
            $evidences =
                exproctor_evidence::get_evidences_by_quizid_and_courseid_and_userid($quizid,
                    $courseid, $studentid);

            $table =
                new flexible_table('exproctor - report - pictures - ' . $COURSE->id . ' - ' . $cmid);

            $table->define_columns(array(
                'std_name', 'evidencetype', 'imagecolumn',
                'actions'
            ));

            $table->define_headers(array(
                get_string('std_name', 'quizaccess_exproctor'),
                get_string('evidencetype', 'quizaccess_exproctor'),
                get_string('imagecolumn', 'quizaccess_exproctor'),
                get_string('actions', 'quizaccess_exproctor')
            ));

            $table->define_baseurl($url);

            $table->set_attribute('class',
                'generaltable generalbox reporttable');
            $table->column_style('std_name', 'text-align', 'center');
            $table->column_style('std_name', 'width', '1 % ');
            $table->column_style('std_name', 'white-space', 'nowrap');
            $table->column_style('evidencetype', 'text-align', 'center');
            $table->column_style('evidencetype', 'width', '1 % ');
            $table->column_style('evidencetype', 'white-space', 'nowrap');
            $table->column_style('imagecolumn', 'text-align', 'center');
            $table->column_style('actions', 'text-align', 'center');
            $table->column_style('actions', 'width', '1 % ');
            $table->column_style('actions', 'white-space', 'nowrap');

            $table->setup();

            $s3client = new aws_s3();

            $firstname = "";
            $lastname = "";

            $webcampicture = "";
            $screenpicture = "";

            foreach ($evidences as $info) {
                $url = $info->get("url");
                $firstname = $info->get("firstname");
                $lastname = $info->get("lastname");

                if ($info->get("storagemethod") === 'AWS(S3)') {
                    $url = $s3client->get_image($url, $info->get("s3filename"));
                }

                $picture = "<a class='quiz-img-div' onclick='return confirm(" .
                    get_string("single_image_delete_confirm_msg", "quizaccess_exproctor") . ")' " .
                    "href='?courseid=" . $courseid . "&quizid=" . $quizid . "&studentid=" . $studentid . "&cmid=" . $cmid .
                    "&reportid=" . $info->get('id') . "&logaction=" . LogAction::DELETE_SINGLE . "'>" .
                    "<img src=" . $url . " width='320px' alt='" . $firstname . "_" . $lastname . "_" . $info->get('id') . "'/></a>";

                if ($info->get("evidencetype") === "webcam") {
                    $webcampicture .= $picture;
                } else {
                    $screenpicture .= $picture;
                }
            }

            if (!empty($webcampicture)) {
                $data = array();
                $data[] =
                    '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $studentid . '&course=' . $courseid .
                    '"target="_blank" > ' . $firstname . ' ' . $lastname . '</a>';

                $data[] = "Webcam";
                $data[] = $webcampicture;

                $data[] =
                    "<a onclick='return confirm(" .
                    get_string("all_image_delete_confirm_msg", "quizaccess_exproctor") .
                    ")' class='text-danger'  href='?courseid=" . $courseid . "&quizid=" . $quizid . "&studentid=" .
                    $studentid . "&cmid=" . $cmid . "&evidencetype=webcam&logaction=" . LogAction::DELETE_ALL .
                    "'>Delete all webcam evidences</a>";

                $table->add_data($data);
            }

            if (!empty($screenpicture)) {
                $data = array();
                $data[] =
                    '<a href="' . $CFG->wwwroot . '/user/view.php?id=' . $studentid . '&course=' . $courseid .
                    '"target="_blank" > ' . $firstname . ' ' . $lastname . '</a>';

                $data[] = "Screen";
                $data[] = $screenpicture;

                $data[] =
                    "<a onclick='return confirm(" .
                    get_string("all_image_delete_confirm_msg", "quizaccess_exproctor") .
                    ")' class='text-danger' href='?courseid=" . $courseid . "&quizid=" . $quizid . "&studentid=" . $studentid .
                    "&cmid=" . $cmid . "&evidencetype=screen&logaction=" . LogAction::DELETE_ALL .
                    "'>Delete all screen evidences</a>";

                $table->add_data($data);
            }
            $table->finish_html();
        }
    } else {
        echo "<div class='box generalbox m-b-1 adminerror alert alert-danger p-y-1' > " . get_string('no_permission_report',
                'quizaccess_exproctor') . "</div > ";
    }

    echo '</div>';
    echo $OUTPUT->footer();
} catch (Exception $e) {
    var_dump($e);
    die();
}
