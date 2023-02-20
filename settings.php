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
 * Plugin administration pages are defined here.
 *
 * @package     quizaccess_exproctor
 * @category    admin
 * @copyright   2022 Shevan Thiranja Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $ADMIN, $PAGE;

if ($hassiteconfig) {
    get_string_manager()->reset_caches();
    $PAGE->requires->js_call_amd('quizaccess_exproctor/dynamic_settings', 'init');

    if ($ADMIN->fulltree) {
        try {
            // Storage Choices.
            $choices = array("Local" => "Local", "AWS(S3)" => "AWS(S3)");

            $awsregions = array('us-east-2' => 'US East (Ohio) - us-east-2', 'us-east-1' => 'US East (N. Virginia) - us-east-1',
                'us-west-1' => 'US West (N. California) - us-west-1', 'us-west-2' => 'US West (Oregon) - us-west-2',
                'af-south-1' => 'Africa (Cape Town) - af-south-1', 'ap-east-1' => 'Asia Pacific (Hong Kong) - ap-east-1',
                'ap-south-2' => 'Asia Pacific (Hyderabad) - ap-south-2',
                'ap-southeast-3' => 'Asia Pacific (Jakarta) - ap-southeast-3',
                'ap-southeast-4' => 'Asia Pacific (Melbourne) - ap-southeast-4',
                'ap-south-1' => 'Asia Pacific (Mumbai) - ap-south-1', 'ap-northeast-3' => 'Asia Pacific (Osaka) - ap-northeast-3',
                'ap-northeast-2' => 'Asia Pacific (Seoul) - ap-northeast-2',
                'ap-southeast-1' => 'Asia Pacific (Singapore) - ap-southeast-1',
                'ap-southeast-2' => 'Asia Pacific (Sydney) - ap-southeast-2',
                'ap-northeast-1' => 'Asia Pacific (Tokyo) - ap-northeast-1', 'ca-central-1' => 'Canada (Central) - ca-central-1',
                'eu-central-1' => 'Europe (Frankfurt) - eu-central-1', 'eu-west-1' => 'Europe (Ireland) - eu-west-1',
                'eu-west-2' => 'Europe (London) - eu-west-2', 'eu-south-1' => 'Europe (Milan) - eu-south-1',
                'eu-west-3' => 'Europe (Paris) - eu-west-3', 'eu-south-2' => 'Europe (Spain) - eu-south-2',
                'eu-north-1' => 'Europe (Stockholm) - eu-north-1', 'eu-central-2' => 'Europe (Zurich) - eu-central-2',
                'me-south-1' => 'Middle East (Bahrain) - me-south-1', 'me-central-1' => 'Middle East (UAE) - me-central-1',
                'sa-east-1' => 'South America (São Paulo) - sa-east-1', 'us-gov-east-1' => 'AWS GovCloud (US-East) - us-gov-east-1',
                'us-gov-west-1' => 'AWS GovCloud (US-West) - us-gov-west-1');

            $settings->add(new admin_setting_configselect("quizaccess_exproctor/storagemethod",
                get_string("storage_method", "quizaccess_exproctor"),
                get_string("storage_method_description", "quizaccess_exproctor"), $choices["Local"], $choices));

            $settings->add(new admin_setting_configselect("quizaccess_exproctor/awsregion",
                get_string("aws_region", "quizaccess_exproctor"),
                get_string("aws_region_description", "quizaccess_exproctor"),
                $awsregions["us-east-2"], $awsregions));

            $settings->add(new admin_setting_configtext("quizaccess_exproctor/awsaccesskey",
                get_string("aws_access_key", "quizaccess_exproctor"),
                get_string("aws_access_key_description", "quizaccess_exproctor"), null, PARAM_RAW_TRIMMED));

            $settings->add(new admin_setting_configtext("quizaccess_exproctor/awssecretkey",
                get_string("aws_secret_key", "quizaccess_exproctor"),
                get_string("aws_secret_key_description", "quizaccess_exproctor"), null, PARAM_RAW_TRIMMED));
        } catch (coding_exception $e) {
            return "Error: " . $e->getMessage();
        }
    }
}
