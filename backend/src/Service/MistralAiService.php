<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class MistralAiService
{
    private string $mistralApiKey;
    private string $mistralApiUrl;
    private string $mistralModel;

    public function __construct(
        #[Autowire('%env(MISTRALAI_API_KEY)%')] string $mistralApiKey,
        #[Autowire('%env(MISTRALAI_API_URL)%')] string $mistralApiUrl,
        #[Autowire('%env(MISTRALAI_MODEL)%')] string $mistralModel,
        private LoggerInterface $logger
    ) {
        $this->mistralApiKey = $mistralApiKey;
        $this->mistralApiUrl = $mistralApiUrl;
        $this->mistralModel = $mistralModel;
    }

    public function chatCompletion(string $prompt): array
    {
        $data = [
            'model' => $this->mistralModel,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ]
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->mistralApiUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'Authorization: Bearer ' . $this->mistralApiKey
            ],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'LibTrack/1.0'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($error) {
            $this->logger->error('Mistral AI cURL error: ' . $error);
            throw new ServiceUnavailableHttpException('Failed to connect to Mistral AI service');
        }

        $responseData = json_decode($response, true);

        if ($httpCode !== 200) {
            $this->logger->error('Mistral AI API error', [
                'http_code' => $httpCode,
                'response' => $responseData
            ]);

            $errorMessage = $responseData['error']['message'] ?? 'Unknown error occurred';
            throw new BadRequestHttpException('Mistral AI API error: ' . $errorMessage);
        }

        return $responseData;
    }

    public function generateReleaseDescription(array $releaseData): string
    {
        $artistNames = array_map(fn($artist) => $artist['name'] ?? '', $releaseData['artists'] ?? []);
        $genreNames = array_map(fn($genre) => $genre['name'] ?? '', $releaseData['genres'] ?? []);

        $prompt = sprintf(
            'Generate an engaging description for this music release:\n\n' .
                'Title: %s\n' .
                'Artist(s): %s\n' .
                'Release Year: %s\n' .
                'Format: %s\n' .
                'Genres: %s\n\n' .
                'Focus on the musical style and significance of the release.' .
                'Can you write it with no Markdown and with no intro or outro comment (no "Certainly" or ending remarks), please. Thank you!',
            $releaseData['title'] ?? 'Unknown',
            implode(', ', $artistNames) ?: 'Unknown Artist',
            $releaseData['release_date'] ?? 'Unknown',
            $releaseData['format']['name'] ?? 'Unknown',
            implode(', ', $genreNames) ?: 'Unknown Genre'
        );

        $response = $this->chatCompletion($prompt);

        return $response['choices'][0]['message']['content'] ?? '';
    }
}
