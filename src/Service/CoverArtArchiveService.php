<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class CoverArtArchiveService
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

  public function getCoverArtByMbid(string $mbid): string
  {
    $response = $this->httpClient->request(
      'GET',
      'https://coverartarchive.org/release/' . $mbid,
      [
        'headers' => [
          'User-Agent' => $this->userAgent
        ]
      ]
    );

    $statusCode = $response->getStatusCode();

    if ($statusCode === 200) {
      $covers = $response->toArray();

      foreach ($covers['images'] as $cover) {
        if ($cover['front']) {
          return $cover['image'];
        }
      }
    }

    return '';
  }
}
