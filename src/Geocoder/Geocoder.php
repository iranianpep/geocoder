<?php

namespace Geocoder;

class Geocoder
{
    const API_URL = 'https://maps.google.com/maps/api/geocode';
    const VALID_OUTPUT_FORMAT = ['json', 'xml'];

    private $apiKey;
    private $rawResponse;
    private $status;
    private $errorMessage;
    private $results;

    /**
     * Geocoder constructor.
     *
     * @param string $apiKey
     */
    public function __construct(string $apiKey = '')
    {
        $this->setApiKey($apiKey);
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     */
    public function setApiKey(string $apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param        $address
     * @param string $region
     * @param string $outputFormat
     *
     * @throws \Exception
     *
     * @return $this
     */
    public function geocode(string $address, string $region = '', string $outputFormat = 'json')
    {
        if ($this->validateOutputFormat($outputFormat) !== true) {
            throw new \Exception("'{$outputFormat}' is not a valid format");
        }

        $rawResponse = file_get_contents($this->generateRequestUrl($address, $region, $outputFormat));

        $this->processRawResponse($rawResponse);

        return $rawResponse;
    }

    /**
     * @param $rawResponse
     */
    private function processRawResponse($rawResponse)
    {
        $this->setRawResponse($rawResponse);

        $responseArray = json_decode($rawResponse, true);
        $this->setStatus($responseArray['status']);

        if (isset($responseArray['error_message'])) {
            $this->setErrorMessage($responseArray['error_message']);
        }

        $this->setResults($responseArray['results']);
    }

    /**
     * @param $address
     *
     * @return array
     */
    public function getLatLng($address)
    {
        $this->geocode($address);

        if ($this->getStatus() !== 'OK') {
            return;
        }

        $latLng = [];
        foreach ($this->getResults() as $result) {
            $latLng[] = [
                'lat' => $result['geometry']['location']['lat'],
                'lng' => $result['geometry']['location']['lng'],
            ];
        }

        return $latLng;
    }

    /**
     * @param $format
     *
     * @return bool
     */
    private function validateOutputFormat(string $format): bool
    {
        if (in_array($format, self::VALID_OUTPUT_FORMAT)) {
            return true;
        }

        return false;
    }

    /**
     * @param        $address
     * @param string $region
     * @param string $outputFormat
     *
     * @return string
     */
    private function generateRequestUrl(string $address, string $region = '', string $outputFormat = 'json'): string
    {
        $baseUrl = self::API_URL.'/'.$outputFormat.'?address='.urlencode($address);

        if (!empty($region)) {
            $baseUrl .= "&region={$region}";
        }

        if (!empty($this->getApiKey())) {
            $baseUrl .= '&key='.$this->getApiKey();
        }

        return $baseUrl;
    }

    /**
     * @return string
     */
    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * @param string $rawResponse
     */
    public function setRawResponse($rawResponse)
    {
        $this->rawResponse = $rawResponse;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus(string $status)
    {
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }

    /**
     * @param string $errorMessage
     */
    public function setErrorMessage(string $errorMessage)
    {
        $this->errorMessage = $errorMessage;
    }

    /**
     * @return mixed
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * @param mixed $results
     */
    public function setResults($results)
    {
        $this->results = $results;
    }

    /**
     * Check if the address exists
     * Normally 'ZERO_RESULTS' is returned if address does not exist.
     *
     * @param $address
     * @param string $region
     *
     * @return bool
     */
    public function isAddressValid(string $address, string $region = ''): bool
    {
        $this->geocode($address, $region);

        if ($this->getStatus() === 'OK') {
            return true;
        }

        return false;
    }
}
