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

$string['pluginname'] = 'ExProctor';
$string['proctor:name'] = 'Exam Proctor';
$string['proctor:short_name'] = 'proctor';
$string['proctor:description'] = 'Proctor is a role of the ExProctor plugin. This role can be used to monitor students during exams.';
$string['exproctor:send_evidence'] = 'Send evidence files';
$string['exproctor:get_evidence'] = 'Read evidence files';
$string['exproctor:view_report'] = 'View proctoring report';
$string['exproctor:delete_evidence'] = 'Delete evidence files';
$string['settings:storage_method'] = 'Data storage method';
$string['settings:storage_method_description'] = 'Select how you want to store the proctoring evidence reports.';
$string['settings:aws_region'] = 'Amazon Web Service region';
$string['settings:aws_region_description'] = 'Select the AWS server region where S3 or Rekognition or both services are available.';
$string['settings:aws_access_key'] = 'AWS S3 access key';
$string['settings:aws_access_key_description'] = 'Access key id for S3 bucket.';
$string['settings:aws_secret_key'] = 'AWS S3 secret key';
$string['settings:aws_secret_key_description'] = 'Secret key for S3 bucket.';
$string['setting:screenshot_delay'] = 'The delay between screenshots in seconds';
$string['setting:screenshot_width'] = 'The width of the screenshot image in px (pixels)';
$string['setting:proctoring_method'] = 'Exam proctoring method';
$string['setting:proctoring_method_one'] = 'Take screenshot randomly';
$string['setting:proctoring_method_two'] = 'Take screenshot using AI';
$string['setting:proctoring_method_three'] = 'Live proctoring';
$string['setting:not_required'] = 'not required';
$string['setting:proctoring_required_option'] = 'must be accepted before beginning an attempt';
$string['openwebcam'] = 'Allow your webcam to continue';
$string['openscreen'] = 'Chose what to share';
$string['webcamproctoringstatement'] = 'This exam requires webcam validation process. <br />(Please allow your web browser to access your camera).';
$string['screenproctoringstatement'] = 'This exam requires screen validation process. <br />(Please select screen to share).';
$string['cam_html'] = '<div class="exproctor_camera"> <video id="exproctor_video_wb" autoplay="true", muted>Video stream not available.</video></div> <canvas id="exproctor_canvas_wb" style="display:none;"> </canvas> <img style="display:none;" id="exproctor_photo_wb" alt="The webcam capture will appear in this box."/>';
$string['screen_html'] = '<div class="exproctor_screen"> <video id="exproctor_video_sc" autoplay="true", muted>Screen stream not available.</video></div> <canvas id="exproctor_canvas_sc" style="display:none;"> </canvas> <img style="display:none;" id="exproctor_photo_sc" alt="The screen capture will appear in this box."/>';
$string['proctoringlabel'] = 'I agree with the validation process.';
$string['youmustagreeforwebcam'] = 'You must agree to webcam validate before continue your attempt.';
$string['youmustagreeforscreen'] = 'You must agree to screen validate before continue your attempt.';
$string['webcamproctoringrequired'] = 'Webcam identity validation';
$string['screenproctoringrequired'] = 'Screen identity validation';
$string['proctoringheader'] = '<strong>To continue with this quiz attempt, please grant your web browser permission to access your {$a}.</strong>';
$string['webcam_report'] = 'View webcam shots';
$string['screen_report'] = 'View screen shots';
$string['no_permission_report'] = 'You don\'t have permission to review validation reports.';
$string['proctoring_reports'] = 'Identity validation report for: ';
$string['proctoring_reports_desc'] = 'In this report you will find all the images of the students which are taken during the exam. Now you can validate their exam.';
$string['dateverified'] = 'Date and time';
$string['actions'] = 'Actions';
$string['pictures_report'] = 'View proctoring report';
$string['pictures_webcam_used_report'] = 'These are the webcam evidence captured during the quiz.';
$string['pictures_screen_used_report'] = 'These are the screen shots captured during the quiz.';
$string['pictures_webcam_no_report'] = 'There is no webcam evidence data for this quiz..';
$string['pictures_screen_no_report'] = 'There is no screen evidence data for this quiz..';
$string['std_name'] = 'Student Name';
$string['webcam_picture'] = 'Captured Webcam Pictures';
$string['screen_picture'] = 'Captured Screen shots';
$string['quizaccess_exproctor'] = 'Quizaccess Exam Proctoring';
$string['setting:screenshotdelay_method_one'] = '5 Seconds';
$string['setting:screenshotdelay_method_two'] = '10 Seconds';
$string['setting:screenshotdelay_method_three'] = '15 Seconds';
$string['setting:screenshotdelay_method_four'] = '20 Seconds';
$string['setting:screenshotdelay_method_five'] = '25 Seconds';
$string['setting:screenshotdelay_method_six'] = '30 Seconds';
$string['setting:frozen_message'] = 'This quiz already has attempts. Therefore ExProctor plugin settings can no longer be updated.';