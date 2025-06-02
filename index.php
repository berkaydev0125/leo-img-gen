<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

function generateLeonardoImage($apiKey, $prompt) {
    $client = new Client([
        'base_uri' => 'https://cloud.leonardo.ai/api/rest/v1/',
        'headers' => [
            'Authorization' => "Bearer $apiKey",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]
    ]);

    $response = $client->post('generations', [
        'json' => [
            'modelId' => 'de7d3faf-762f-48e0-b3b7-9d0ac3a3fcf3',
            'contrast' => 3.5,
            'prompt' => $prompt,
            'num_images' => 4,
            'width' => 1200,
            'height' => 624,
            'styleUUID' => '111dc692-d470-4eec-b791-3475abac4c46',
            'enhancePrompt' => false
        ],
    ]);

    $data = json_decode($response->getBody(), true);
    $generationId = $data['sdGenerationJob']['generationId'] ?? null;

    if (!$generationId) {
        echo "No generation ID found.\n";
        return;
    }

    echo "Generation ID: $generationId\n";

    $maxRetries = 10;
    $retryDelay = 3;
    $status = '';
    $result = [];

    for ($i = 0; $i < $maxRetries; $i++) {
        sleep($retryDelay);

        $checkResponse = $client->get("generations/$generationId");
        $result = json_decode($checkResponse->getBody(), true);
        $status = $result['generations_by_pk']['status'] ?? 'UNKNOWN';

        echo "Check $i: Status = $status\n";

        if ($status === 'COMPLETE') {
            break;
        }
    }

    if ($status !== 'COMPLETE') {
        echo "Image generation did not complete in time.\n";
        return;
    }

    $imageUrls = [];
    if (!empty($result['generations_by_pk']['generated_images'])) {
        foreach ($result['generations_by_pk']['generated_images'] as $image) {
            if (isset($image['url'])) {
                $imageUrls[] = $image['url'];
            }
        }
    }

    print_r($imageUrls);
}

$apiKey = $_ENV['LEONARDO_API_KEY'];
$prompt = 'A government cybersecurity operations room seen at eye level with soft, directional lighting. Military and civilian personnel collaborate before displays showing threat detection systems and protected network visualizations. An analyst in uniform gestures toward a dashboard. A senior officer studies tablet intel, while a comms expert types with focus. Badge readers, document folders, and reinforced walls convey control. The room features government seals, security zones, and clearance indicators. Palette: navy blues, reds, authoritative grays. Natural depth of field.';
generateLeonardoImage($apiKey, $prompt);
