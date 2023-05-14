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
 * quizaccess_exproctor file description here.
 *
 * @package    quizaccess_exproctor
 * @copyright  2022 Shevan Fernando <w.k.b.s.t.fernando@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace quizaccess_exproctor;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/mod/quiz/accessrule/exproctor/aws_sdk/aws-autoloader.php');

use Aws\Exception\AwsException;
use Aws\Result;
use Aws\S3\S3Client;
use dml_exception;
use Exception;
use Iterator;

class aws_s3 {
    private $s3client;
    private $data;

    /**
     * Constructor
     *
     * @throws dml_exception
     * @throws Exception
     */
    public function __construct() {
        global $DB;

        $records = $DB->get_records("config_plugins",
            ['plugin' => 'quizaccess_exproctor'], 'id', 'name, value');

        foreach ($records as $elements) {
            $this->data[$elements->name] = $elements->value;
        }

        if (!empty($this->data)) {
            $this->s3client = new S3Client([
                'version' => 'latest',
                'region' => $this->data['awsregion'],
                'credentials' => [
                    'key' => $this->data['awsaccesskey'],
                    'secret' => $this->data['awssecretkey']
                ]
            ]);
        } else {
            throw new Exception('Error: there is no setting record related to the ExProctor plugin.');
        }
    }

    /**
     * Get exproctor settings array
     *
     * @return array
     */
    public function get_data(): array {
        return $this->data;
    }

    /**
     *  Delete all the S3 Buckets
     *
     * @return bool|string
     */
    public function delete_buckets() {
        try {
            // Get all the Buckets in S3.
            $files = $this->s3client->listBuckets();

            foreach ($files['Buckets'] as $bucket) {
                // Get bucket name.
                $bucketname = $bucket["Name"];

                // Delete bucket.
                $this->delete_bucket($bucketname);
            }

            return true;
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    /**
     *  Delete S3 Bucket
     *
     * @param $bucketname
     * @param $evidencetype
     *
     * @return bool|string
     */
    public function delete_bucket($bucketname, $evidencetype = null) {
        try {
            // Delete the objects in the bucket before attempting to delete the bucket.
            $iterator = $this->get_items_in_s3($bucketname);

            foreach ($iterator as $object) {
                if (!empty($evidencetype) && (explode('-', $object['Key'])[0]
                        !== $evidencetype)) {
                    continue;
                }
                $this->delete_image($bucketname, $object['Key']);
            }

            $items = $this->get_items_in_s3($bucketname);

            if (!$items->valid()) {
                // Delete the bucket.
                $this->s3client->deleteBucket(array('Bucket' => $bucketname));

                // Wait until the bucket is not accessible.
                $this->s3client->waitUntil('BucketNotExists',
                    array('Bucket' => $bucketname));
            }

            return true;
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    /**
     *
     * Get all the items in s3 bucket
     *
     * @param $bucketname
     *
     * @return Iterator
     */
    private function get_items_in_s3($bucketname): Iterator {
        return $this->s3client->getIterator('ListObjects', array(
            'Bucket' => $bucketname
        ));
    }

    /**
     * Delete Image
     *
     * @param $bucketname
     * @param $filename
     *
     * @return Result|string
     */
    public function delete_image($bucketname, $filename) {
        try {
            // Delete image.
            return $this->s3client->deleteObject([
                'Bucket' => $bucketname,
                'Key' => $filename
            ]);
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    /**
     * Create S3 bucket
     *
     * @param $attempt
     * @return string
     */
    public function create_bucket($attempt): string {
        global $DB;

        try {
            $query = "SELECT url FROM {quizaccess_exproctor_evid} WHERE attemptid = :attemptid ORDER BY id DESC LIMIT 1";
            $result =
                $DB->get_record_sql($query,
                    array("attemptid" => (int) $attempt));

            if (empty($result)) {
                // Create the bucket name.
                $bucketname = md5(time());

                // Create the bucket.
                $this->s3client->createBucket([
                    'Bucket' => $bucketname,
                ]);

                // Poll the bucket until it is accessible.
                $this->s3client->waitUntil('BucketExists',
                    array('Bucket' => $bucketname));
            } else {
                $bucketname = $result->url;
                $bucketname = $this->get_bucket_name_using_url($bucketname);
            }

            return $bucketname;
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        } catch (dml_exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    public function get_bucket_name_using_url(string $url) {
        $bucketname = explode(".s3." . $this->data["awsregion"], $url)[0];
        return str_replace("https://", "", $bucketname);
    }

    /**
     *  Save images
     *
     * @param $bucketname
     * @param $imagedata
     * @param $filename
     *
     * @return Result|string
     */
    public function save_image($bucketname, $imagedata, $filename) {
        try {
            // Save image.
            return $this->s3client->putObject([
                'Bucket' => $bucketname,
                'Key' => $filename,
                'Body' => $imagedata,
                'ContentType' => 'image/png'
            ]);
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    /**
     * Get image url
     *
     * @param string $url
     * @param $filename
     * @return string
     */
    public function get_image(string $url, $filename): string {
        try {
            $bucketname = $this->get_bucket_name_using_url($url);

            // Creating a pre-signed URL.
            $cmd = $this->s3client->getCommand('GetObject', [
                'Bucket' => $bucketname,
                'Key' => $filename . '.png'
            ]);

            $request =
                $this->s3client->createPresignedRequest($cmd, '+20 minutes');

            // Get the actual pre-signed-url.
            return (string) $request->getUri();
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }
}
