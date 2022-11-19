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
 * Implementation of the quizaccess_examproctoring plugin.
 *
 * @package    quizaccess_examproctoring
 * @copyright  2022 Shevan Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../../../config.php');
$context = context_system::instance();

global $PAGE;

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/mod/quiz/accessrule/examproctoring/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title($SITE->fullname);
$PAGE->set_heading(get_string('pluginname', 'quizaccess_examproctoring'));

//$PAGE->requires->js_call_amd('quizaccess_examproctoring/countdowntimer', 'init', array("Shevan", "Fernando"));
//$PAGE->requires->js_call_amd('quizaccess_examproctoring/countdowntimer', 'init', $params);

echo $OUTPUT->header();

//$params = (object)[
//    "message" => "Hello Shevan, Template is rendering!"
//];
//
//echo $OUTPUT->render_from_template('quizaccess_examproctoring/countdowntimer', $params);

echo $OUTPUT->footer();
