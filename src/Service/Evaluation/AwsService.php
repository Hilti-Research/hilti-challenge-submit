<?php

namespace App\Service\Evaluation;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\RouterInterface;

readonly class AwsService
{
    public function __construct(private string $awsRegion, private string $awsBucketName, private string $awsAccessKeyId, private string $awsSecretAccessKey, private string $awsCallbackSecret, private LoggerInterface $logger, private RouterInterface $router)
    {
    }

    private function getS3Client(): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => $this->awsRegion,
            'credentials' => new Credentials($this->awsAccessKeyId, $this->awsSecretAccessKey),
        ]);
    }

    public function startEvaluation(string $bodyPath, string $returnUrl, string $evaluationId, string $challengeType, string $evaluationType): bool
    {
        try {
            $s3 = $this->getS3Client();

            $metadata = [
                // note: AWS will store metadata only in lowercase, hence explicitly set it here to catch bugs easier
                'key' => strtolower($evaluationId),
                'return-url' => strtolower($returnUrl),
                'challenge-type' => strtolower($challengeType), 'evaluation-type' => strtolower($evaluationType), // to select evaluation script
            ];

            $s3->putObject([
                'Bucket' => $this->awsBucketName,
                'Key' => $evaluationId,
                'Body' => fopen($bodyPath, 'r'),
                'ACL' => 'private',
                'Metadata' => $metadata,
            ]);

            $this->logger->info('Started evaluation', ['Metadata' => $metadata]);

            return true;
        } catch (\Exception $exception) {
            $this->logger->error('Error when uploading to AWS', ['exception' => $exception, 'evaluationId' => $evaluationId]);
        }

        return false;
    }

    public function downloadFile(string $bucket, string $key, string $path): bool
    {
        $context = ['bucket' => $bucket, 'key' => $key, 'path' => $path];

        try {
            $s3 = $this->getS3Client();

            $fileData = $s3->getObject([
                'Bucket' => $bucket,
                'Key' => $key,
            ]);

            file_put_contents($path, $fileData['Body']);

            $this->logger->info('Downloaded file', $context);

            return true;
        } catch (\Exception $exception) {
            $this->logger->error('Error when downloading from AWS', array_merge(['exception' => $exception], $context));
        }

        return false;
    }
}
