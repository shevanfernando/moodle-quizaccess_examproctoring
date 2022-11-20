<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     quizaccess_exproctor
 * @category    string
 * @copyright   2022 Shevan Thiranja Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Exam Proctoring';
$string['exproctor:send_evidence'] = 'Send evidence files';
$string['exproctor:get_evidence'] = 'Read evidence files';
$string['exproctor:view_report'] = 'View proctoring report';
$string['exproctor:delete_evidence'] = 'Delete evidence files';
$string['settings:storage_method'] = 'Data storage method';
$string['settings:storage_method_description'] = 'Proctored data storage method. (Values: Local/ AWS(S3))';
$string['settings:local_storage_path'] = 'Local storage folder path';
$string['settings:local_storage_path_description'] = 'File path for store proctored data in locally.';
$string['settings:aws_region'] = 'AWS S3 region';
$string['settings:aws_region_description'] = 'AWS region for S3 bucket.';
$string['settings:aws_access_id'] = 'AWS access key id';
$string['settings:aws_access_id_description'] = 'AWS access key id for S3 bucket.';
$string['settings:aws_access_key'] = 'AWS secret access key';
$string['settings:aws_access_key_description'] = 'AWS secret access key for S3 bucket.';
$string['setting:screen_shot_delay'] = 'The delay between screenshots in seconds';
$string['setting:screen_shot_width'] = 'The width of the screenshot image in px (pixels)';
$string['setting:proctoring_method'] = 'Exam proctoring method';
$string['setting:proctoring_method_one'] = 'Take screenshot randomly';
$string['setting:proctoring_method_sec'] = 'Live proctoring';
$string['setting:proctoring_method_three'] = 'AI detection';
$string['setting:proctoring_method_four'] = 'Recording';
$string['setting:not_required'] = 'not required';
$string['setting:proctoring_required_option'] = 'must be accepted before beginning an attempt';
$string['openwebcam'] = 'Allow your webcam to continue';
$string['openscreen'] = 'Chose what to share';
$string['webcamproctoringstatement'] = 'This exam requires webcam validation process. <br />(Please allow your web browser to access your camera).';
$string['screenproctoringstatement'] = 'This exam requires screen validation process. <br />(Please select screen to share).';
$string['camhtml'] = '<div class="camera"> <video id="video">Video stream not available.</video></div> <canvas id="canvas" style="display:none;"> </canvas> <img style="display:none;" id="photo" alt="The screen capture will appear in this box."/>';
$string['proctoringlabel'] = 'I agree with the validation process.';
$string['youmustagree'] = 'You must agree to validate your identity before continue.';
$string['webcamproctoringrequired'] = 'Webcam identity validation';
$string['screenproctoringrequired'] = 'Screen identity validation';
$string['proctoringheader'] = '<strong>To continue with this quiz attempt, please grant your web browser permission to access your {$a}.</strong>';
$string['picturesreport'] = 'View proctoring report';