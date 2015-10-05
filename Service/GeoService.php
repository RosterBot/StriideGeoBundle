<?php
namespace Striide\GeoBundle\Service;

use Striide\GeoBundle\Entity\GeoIP;
use Striide\GeoBundle\Entity\GeoIpHarvesting;

class GeoService
{
    /**
     *
     */
    private $logger = null;
    /**
     *
     */
    private $em = null;
    /**
     *
     */
    private $rest_client = null;
    private $googleApiKey;

    /**
     * @param $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param EntityManager $e
     */
    public function setEntityManager($e)
    {
        $this->em = $e;
    }

    /**
     * @param $client
     */
    public function setRestClient($client)
    {
        $this->rest_client = $client;
    }

    /**
     * @param string $ip
     * @return null
     */
    public function harvestLocationByIp($ip)
    {
        $this->logger->info(__METHOD__, array($ip));

        $geoip = $this->em->getRepository('StriideGeoBundle:GeoIP')->findOneByIp($ip);
        if (!empty($geoip)) {
            return $geoip;
        }

        try {
            $payload = $this->rest_client->get(sprintf("https://freegeoip.net/json/%s", $ip));
        } catch (\Exception $e) {
            return null;
        }

        if (!empty($payload)) {
            $geoip = new GeoIP();
            $geoip->setIp($ip);
            $geoip->setJson($payload);

            $this->em->persist($geoip);
            $this->em->flush();
            $this->em->clear();

            return $geoip;
        }

        return null;
    }

    /**
     * @return GeoIP
     */
    public function getLocationByIp($ip)
    {
        $this->logger->info(sprintf("Looking up address by ip... (%s)", $ip));

        $geoip = $this->em->getRepository('StriideGeoBundle:GeoIP')->findOneByIp($ip);
        if (!empty($geoip)) {
            return $geoip;
        } else {
            // queue harvesting....
            $this->queueHarvest($ip);
        }
        return null;
    }

    /**
     * queue the record to harvest
     */
    public function queueHarvest($ip)
    {
        $this->logger->info(__METHOD__, array($ip));

        // only harvest if we need to
        $geoip = $this->em->getRepository('StriideGeoBundle:GeoIP')->findOneByIp($ip);
        if (!empty($geoip)) {
            return;
        }

        $geoip = $this->em->getRepository('StriideGeoBundle:GeoIpHarvesting')->findOneBy(array('ip' => $ip));
        if (!empty($geoip)) {
            return;
        }

        $harvest = new GeoIpHarvesting();
        $harvest->setIp($ip);

        $this->em->persist($harvest);
        $this->em->flush();
        $this->em->clear();
    }

    /**
     * @param $address
     * @return mixed|null
     */
    public function getLocationByAddress($address)
    {
        $this->logger->info(sprintf("Looking up address... (%s)", $address));

        try {
            $payload = $this->rest_client->get(sprintf("https://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=%s&key=%s", urlencode($address), $this->googleApiKey));
            $results = json_decode($payload, true);
            return $results;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getPlaceByAddress($address)
    {
        $this->logger->info(sprintf("Looking up place... (%s)", $address));

        try {
            $payload = $this->rest_client->get(sprintf("https://maps.googleapis.com/maps/api/place/textsearch/json?query=%s&key=%s", urlencode($address), $this->googleApiKey));
            $results = json_decode($payload, true);
            return $results;
        } catch (\Exception $e) {
            return null;
        }

    }

    public function getTimezoneFromLatLng($lat, $lng)
    {
        $this->logger->info(sprintf("Looking up lat lng... (%s, %s)", $lat, $lng));

        try {
            $payload = $this->rest_client->get(sprintf("https://maps.googleapis.com/maps/api/timezone/json?location=%s,%s&key=%s", $lat, $lng, $this->googleApiKey));
            $results = json_decode($payload, true);
            return $results;
        } catch (\Exception $e) {
            return null;
        }

    }

    /**
     *
     */
    public function getCountriesArray()
    {
        $repository = $this->em->getRepository('StriideGeoBundle:Countries');
        $countries = $repository->getArray();
        $array = array();
        foreach ($countries as $country) {
            $name = $country->getName();

            if (empty($name)) {
                continue;
            }
            $array[$name] = $name;
        }
        return $array;
    }

    /**
     *
     */
    public function getRegionsArrayByCountry($country)
    {

        if ($country == "Canada") {
            return $this->getProvincesArray();
        }

        if ($country == "United States") {
            return $this->getStatesArray();
        }
        return array();
    }

    /**
     *
     */
    public function getProvincesArray()
    {
        $repository = $this->em->getRepository('StriideGeoBundle:StatesCa');
        $provinces = $repository->getArray();
        $array = array();
        foreach ($provinces as $province) {
            $name = $province->getName();
            $code = $province->getShortCode();

            if (empty($code)) {
                continue;
            }

            if (empty($name)) {
                continue;
            }
            $array[$code] = $name;
        }
        return $array;
    }

    /**
     *
     */
    public function getStatesArray()
    {
        $repository = $this->em->getRepository('StriideGeoBundle:StatesUs');
        $states = $repository->getArray();
        $array = array();
        foreach ($states as $state) {
            $name = $state->getName();
            $code = $state->getShortCode();

            if (empty($name)) {
                continue;
            }

            if (empty($code)) {
                continue;
            }
            $array[$code] = $name;
        }
        return $array;
    }

    /**
     * @param mixed $googleApiKey
     */
    public function setGoogleApiKey($googleApiKey)
    {
        $this->googleApiKey = $googleApiKey;
    }
}
