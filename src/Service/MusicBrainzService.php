<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class MusicBrainzService
{
  private HttpClientInterface $httpClient;
  private string $userAgent;

  public function __construct(
    HttpClientInterface $httpClient,
    #[Autowire('%env(MUSICBRAINZ_APP_NAME)%')] string $appName,
    #[Autowire('%env(MUSICBRAINZ_CONTACT_EMAIL)%')] string $contactEmail
  ) {
    $this->httpClient = $httpClient;
    $this->userAgent = "$appName/1.0 ($contactEmail)";
  }

  public function getReleaseByBarcode(string $barcode): array
  {
    $response = $this->httpClient->request('GET', 'https://musicbrainz.org/ws/2/release/', [
      'query' => [
        'query' => 'barcode:' . $barcode,
        'fmt' => 'json',
      ],
      'headers' => [
        'User-Agent' => $this->userAgent
      ]
    ]);

    return $response->toArray();
  }

  public function getReleaseById(string $mbid): array
  {
    $response = $this->httpClient->request('GET', 'https://musicbrainz.org/ws/2/release/' . $mbid, [
      'query' => [
        'fmt' => 'json',
        'inc' => 'artists+labels+recordings',
      ],
      'headers' => [
        'User-Agent' => $this->userAgent
      ]
    ]);

    return $response->toArray();
  }
}
