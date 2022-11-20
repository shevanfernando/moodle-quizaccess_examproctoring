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
 * Services for the quizaccess_examproctoring plugin.
 *
 * @package    quizaccess_examproctoring
 * @copyright  2022 Shevan Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined("MOODLE_INTERNAL") || die;

$functions = array(
    'quizaccess_examproctoring_send_webcam_shot' => array(
        'classname' => 'quizaccess_examproctoring_external',
        'methodname' => 'send_webcam_shot',
        'description' => 'Send a webcam snapshot on the given session',
        'type' => 'write',
        'ajax' => true,
        'capabilities' => 'quizaccess/examproctoring:send_evidence',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    ),
    'quizaccess_examproctoring_get_webcam_shot' => array(
        'classname' => 'quizaccess_examproctoring_external',
        'methodname' => 'get_webcam_shot',
        'description' => 'Get list of webcam snapshot in the given session',
        'type' => 'read',
        'ajax' => true,
        'capabilities' => 'quizaccess/examproctoring:get_evidence',
        'services' => array(MOODLE_OFFICIAL_MOBILE_SERVICE)
    )
);
