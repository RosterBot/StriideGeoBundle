<?php
namespace Striide\GeoBundle\Service;

class GoogleGeoService
{
  /**
   *
   */
  private $logger = null;

  /**
   *
   */
  public function __construct($doctrine,$logger)
  {
    $this->doctrine = $doctrine;
    $this->logger = $logger;
  }

  /**
   *
   */
  private $rest_client = null;

  private $googleApiKey;


  /**
   *
   */
  public function setRestClient($client)
  {
    $this->rest_client = $client;
  }

  /**
   *
   */
  public function getLocationByIpAddress($address)
  {
    $this->logger->info(sprintf("Looking up address... (%s)", $address));

    try
    {
      $payload = $this->rest_client->get(sprintf("https://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=%s&key=%s", $address, $this->googleApiKey));
      $results = json_decode($payload, true);
      return $results;
    }
    catch(\Exception $e)
    {
      return null;
    }
  }

  /**
   * @param mixed $googleApiKey
   */
  public function setGoogleApiKey($googleApiKey)
  {
    $this->googleApiKey = $googleApiKey;
  }
}
