<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class DiscogsService
{
  private HttpClientInterface $httpClient;
  private string $userAgent;
  private string $endpoint;
  private string $discogs_key;
  private string $discogs_secret;

  public function __construct(
    HttpClientInterface $httpClient,
    #[Autowire('%env(DISCOGS_ENDPOINT)%')] string $endpoint,
    #[Autowire('%env(DISCOGS_KEY)%')] string $discogs_key,
    #[Autowire('%env(DISCOGS_SECRET)%')] string $discogs_secret,
    #[Autowire('%env(MUSICBRAINZ_APP_NAME)%')] string $appName,
    #[Autowire('%env(MUSICBRAINZ_CONTACT_EMAIL)%')] string $contactEmail
  ) {
    $this->httpClient = $httpClient;
    $this->userAgent = "$appName/1.0 ($contactEmail)";
    $this->endpoint = $endpoint;
    $this->discogs_key = $discogs_key;
    $this->discogs_secret = $discogs_secret;
  }

  public function getReleaseByBarcode(string $barcode): array
  {
    try {
      $response = $this->httpClient->request('GET', $this->endpoint . 'database/search', [
        'query' => [
          'barcode' => $barcode,
          'key' => $this->discogs_key,
          'secret' => $this->discogs_secret
        ],
        'headers' => [
          'User-Agent' => $this->userAgent
        ]
      ]);

      if ($response->getStatusCode() === 200) {
        $response = $response->toArray();
        $releases = [];

        foreach ($response['results'] as $rel) {
          $releaseData = $this->getReleaseById($rel['id']);

          if ($releaseData !== null) {
            $releases[] = $releaseData;
          }
        }
      } else {
        throw new \Exception('Unexpected response status: ' . $response->getStatusCode());
      }
    } catch (\Throwable $th) {
      throw new \Exception('Unexpected response status: ' . $th->getMessage());
    }

    return $releases;
  }

  public function getReleaseById(string $id)
  {
    try {
      $response = $this->httpClient->request('GET', $this->endpoint . 'releases/' . $id, [
        'headers' => [
          'User-Agent' => $this->userAgent
        ]
      ]);

      if ($response->getStatusCode() === 200) {
        $data = json_decode($response->getContent(), true);

        return $data;
      } else {
        return null;
      }
    } catch (\Throwable $th) {
      throw new \Exception('Error fetching release data: ' . $th->getMessage(), 0, $th);
    }
  }

  /**
   * Search a release on Discogs
   * 
   * Since version 0.2.2, it is possible to directly search with a Discogs release_id.
   *
   * @param string $by -> title,release_title,artist,genre,country,year,format,barcode
   * @param string $search
   * @return array
   */
  public function searchRelease(string $by, string $search, int $per_page = 5, int $page = 1): array
  {
    // First, let's try to search by release ID if the search term looks like an integer
    if (ctype_digit($search)) {
      try {
        $releaseData = $this->getReleaseById($search);

        if ($releaseData !== null) {
          return [
            "releases" => [$releaseData],
            "per_page" => 1,
            "page" => 1,
            "pages" => 1,
            "items" => 1,
          ];
        }
      } catch (\Throwable $th) {
        // If it fails, continue with regular search
      }
    }

    // Fall back to regular search if:
    // - search term is not numeric
    // - getReleaseById returned null
    // - getReleaseById threw an exception
    try {
      $response = $this->httpClient->request('GET', $this->endpoint . 'database/search', [
        'query' => [
          $by => $search,
          'per_page' => $per_page,
          'page' => $page,
          'key' => $this->discogs_key,
          'secret' => $this->discogs_secret
        ],
        'headers' => [
          'User-Agent' => $this->userAgent
        ]
      ]);

      if ($response->getStatusCode() === 200) {
        $response = $response->toArray();
        $releases = [];

        foreach ($response['results'] as $rel) {
          $releaseData = $this->getReleaseById($rel['id']);

          if ($releaseData !== null) {
            $releases[] = $releaseData;
          }
        }
      } else {
        throw new \Exception('Unexpected response status: ' . $response->getStatusCode());
      }
    } catch (\Throwable $th) {
      throw new \Exception('Unexpected response status: ' . $th->getMessage());
    }

    return [
      "releases" => $releases,
      "per_page" => $response['pagination']['per_page'],
      "page" => $response['pagination']['page'],
      "pages" => $response['pagination']['pages'],
      "items" => $response['pagination']['items'],
    ];
  }
}
