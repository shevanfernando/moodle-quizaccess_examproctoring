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

global $CFG;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/quiz/accessrule/exproctor/aws_sdk/aws-autoloader.php');

use Aws\Exception\AwsException;
use Aws\Result;
use Aws\S3\S3Client;
use dml_exception;
use Exception;

class aws_s3
{
    private $s3Client;
    private $data;

    /**
     * Constructor
     *
     * @throws dml_exception
     * @throws Exception
     */
    public function __construct()
    {
        global $DB;

        $records = $DB->get_records("config_plugins", ['plugin' => 'quizaccess_exproctor'], 'id', 'name, value');

        foreach ($records as $elements) {
            $this->data[$elements->name] = $elements->value;
        }

        if (!empty($this->data)) {
            $this->s3Client = new S3Client([
                'version'     => 'latest',
                'region'      => $this->data['awsregion'],
                'credentials' => [
                    'key'    => $this->data['awsaccesskey'],
                    'secret' => $this->data['awssecretkey']
                ]
            ]);
        } else {
            throw new Exception('Error: there is no setting record related to the ExProctor plugin.');
        }
    }

    /**
     * Get S3 Client
     *
     * @return S3Client
     */
    public function getS3Client(): S3Client
    {
        return $this->s3Client;
    }

    /**
     * Get exproctor settings array
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     *  Delete all the S3 Buckets
     *
     * @return bool|string
     */
    public function deleteBuckets()
    {
        try {
            // Get all the Buckets in S3
            $files = $this->s3Client->listBuckets();

            foreach ($files['Buckets'] as $bucket) {
                // Get bucket name
                $bucketName = $bucket["Name"];

                // Delete bucket
                $this->deleteBucket($bucketName);
            }

            return true;
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    /**
     *  Delete S3 Bucket
     *
     * @param $bucketName
     * @return bool|string
     */
    public function deleteBucket($bucketName)
    {
        try {
            // Delete the objects in the bucket before attempting to delete
            // the bucket
            $iterator = $this->s3Client->getIterator('ListObjects', array(
                'Bucket' => $bucketName
            ));

            foreach ($iterator as $object) {
                $this->deleteImage($bucketName, $object['Key']);
            }

            // Delete the bucket
            $this->s3Client->deleteBucket(array('Bucket' => $bucketName));

            // Wait until the bucket is not accessible
            $this->s3Client->waitUntil('BucketNotExists', array('Bucket' => $bucketName));

            return true;
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    /**
     * Delete Image
     *
     * @param $bucketName
     * @param $fileName
     * @return Result|string
     */
    public function deleteImage($bucketName, $fileName)
    {
        try {
            // Delete image
            return $this->s3Client->deleteObject([
                'Bucket' => $bucketName,
                'Key'    => $fileName
            ]);
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    /**
     * Create S3 bucket
     *
     * @return string
     */
    public function createBucket($attempt): string
    {
        global $DB;

        try {
            $result = [];

            foreach (["quizaccess_exproctor_wb_logs",
                         "quizaccess_exproctor_sc_logs"] as $table) {
                $r = $DB->get_records($table, array("attemptid" => $attempt));

                $result = array_merge($result, $r);
            }

            if (empty($result)) {
                # Create the bucket name
                $bucketName = md5(time());

                // Create the bucket
                $this->s3Client->createBucket([
                    'Bucket' => $bucketName,
                ]);

                // Poll the bucket until it is accessible
                $this->s3Client->waitUntil('BucketExists', array('Bucket' => $bucketName));
            } else {
                $bucketName = empty($result[0]->screenshot) ? $result[0]->webcamshot : $result[0]->screenshot;
                $bucketName = $this->getBucketNameUsingUrl($bucketName);
            }

            return $bucketName;
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    public function getBucketNameUsingUrl(string $bucketName)
    {
        $bucketName = explode(".s3." . $this->data["awsregion"], $bucketName)[0];
        return str_replace("https://", "", $bucketName);
    }

    /**
     *  Save images
     *
     * @param $bucketName
     * @param $imageData
     * @param $fileName
     * @return Result|string
     */
    public function saveImage($bucketName, $imageData, $fileName)
    {
        try {
            // Save image
            return $this->s3Client->putObject([
                'Bucket'      => $bucketName,
                'Key'         => $fileName,
                'Body'        => $imageData,
                'ContentType' => 'image/png'
            ]);
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }

    public function getImage(string $url, $fileName)
    {
        try {
            $bucketName = $this->getBucketNameUsingUrl($url);

            //Creating a presigned URL
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $bucketName,
                'Key'    => $fileName . '.png'
            ]);

            $request = $this->s3Client->createPresignedRequest($cmd, '+20 minutes');

            // Get the actual presigned-url
            return (string)$request->getUri();
        } catch (AwsException $e) {
            return 'Error: ' . $e->getAwsErrorMessage();
        }
    }
}

