<?php

namespace WebPConvert\Convert\Converters;

use WebPConvert\Convert\Converters\AbstractConverter;
use WebPConvert\Convert\Converters\ConverterTraits\CloudConverterTrait;
use WebPConvert\Convert\Converters\ConverterTraits\CurlTrait;
use WebPConvert\Convert\Exceptions\ConversionFailedException;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperationalException;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\InvalidApiKeyException;
use WebPConvert\Convert\Exceptions\ConversionFailed\ConverterNotOperational\SystemRequirementsNotMetException;
use WebPConvert\Options\BooleanOption;
use WebPConvert\Options\SensitiveStringOption;

/**
 * Convert images to webp using ewww cloud service.
 *
 * @package    WebPConvert
 * @author     Bjørn Rosell <it@rosell.dk>
 * @since      Class available since Release 2.0.0
 */
class Ewww extends AbstractConverter
{
    use CloudConverterTrait;
    use CurlTrait;

    public static $nonFunctionalApiKeysDiscoveredDuringConversion;

    protected function getUnsupportedDefaultOptions()
    {
        return [
            'alpha-quality',
            'auto-filter',
            'encoding',
            'low-memory',
            'use-nice'
        ];
    }

    protected function createOptions()
    {
        parent::createOptions();

        $this->options2->addOptions(
            new SensitiveStringOption('api-key', ''),
            new BooleanOption('check-key-status-before-converting', true)
        );
    }

    /**
     * Get api key from options or environment variable
     *
     * @return string|false  api key or false if none is set
     */
    private function getKey()
    {
        if (!empty($this->options['api-key'])) {
            return $this->options['api-key'];
        }
        if (defined('WEBPCONVERT_EWWW_API_KEY')) {
            return constant('WEBPCONVERT_EWWW_API_KEY');
        }
        if (!empty(getenv('WEBPCONVERT_EWWW_API_KEY'))) {
            return getenv('WEBPCONVERT_EWWW_API_KEY');
        }
        return false;
    }


    /**
     * Check operationality of Ewww converter.
     *
     * @throws SystemRequirementsNotMetException  if system requirements are not met (curl)
     * @throws ConverterNotOperationalException   if key is missing or invalid, or quota has exceeded
     */
    public function checkOperationality()
    {

        $apiKey = $this->getKey();

        if ($apiKey === false) {
            if (isset($this->options['key'])) {
                throw new InvalidApiKeyException(
                    'The "key" option has been renamed to "api-key" in webp-convert 2.0. ' .
                    'You must change the configuration accordingly.'
                );
            }

            throw new InvalidApiKeyException('Missing API key.');
        }

        if (strlen($apiKey) < 20) {
            throw new InvalidApiKeyException(
                'Api key is invalid. Api keys are supposed to be 32 characters long - ' .
                'the provided api key is much shorter'
            );
        }

        // Check for curl requirements
        $this->checkOperationalityForCurlTrait();

        if ($this->options['check-key-status-before-converting']) {
            $keyStatus = self::getKeyStatus($apiKey);
            switch ($keyStatus) {
                case 'great':
                    break;
                case 'exceeded':
                    throw new ConverterNotOperationalException('Quota has exceeded');
                case 'invalid':
                    throw new InvalidApiKeyException('Api key is invalid');
            }
        }
    }

    protected function doActualConvert()
    {

        $options = $this->options;

        $ch = self::initCurl();

        $postData = [
            'api_key' => $this->getKey(),
            'webp' => '1',
            'file' => curl_file_create($this->source),
            'quality' => $this->getCalculatedQuality(),
            'metadata' => ($options['metadata'] == 'none' ? '0' : '1')
        ];

        curl_setopt_array(
            $ch,
            [
            CURLOPT_URL => "https://optimize.exactlywww.com/v2/",
            CURLOPT_HTTPHEADER => [
                'User-Agent: WebPConvert',
                'Accept: image/*'
            ],
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false
            ]
        );

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            throw new ConversionFailedException(curl_error($ch));
        }

        if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) != 'application/octet-stream') {
            curl_close($ch);

            $responseObj = json_decode($response);
            if (isset($responseObj->error)) {
                $this->logLn('We received the following error response: ' . $responseObj->error);
                $this->logLn('Complete response: ' . json_encode($responseObj));

                if (!isset(self::$nonFunctionalApiKeysDiscoveredDuringConversion)) {
                    self::$nonFunctionalApiKeysDiscoveredDuringConversion = [];
                }
                if (!in_array($options['api-key'], self::$nonFunctionalApiKeysDiscoveredDuringConversion)) {
                    self::$nonFunctionalApiKeysDiscoveredDuringConversion[] = $options['api-key'];
                }
                if ($responseObj->error == "invalid") {
                    throw new InvalidApiKeyException('The api key is invalid (or expired)');
                } else {
                    throw new InvalidApiKeyException('The quota is exceeded for the api-key');
                }
            }

            throw new ConversionFailedException(
                'ewww api did not return an image. It could be that the key is invalid. Response: '
                . $response
            );
        }

        // Not sure this can happen. So just in case
        if ($response == '') {
            throw new ConversionFailedException('ewww api did not return anything');
        }

        $success = file_put_contents($this->destination, $response);

        if (!$success) {
            throw new ConversionFailedException('Error saving file');
        }
    }

    /**
     *  Keep subscription alive by optimizing a jpeg
     *  (ewww closes accounts after 6 months of inactivity - and webp conversions seems not to be counted? )
     */
    public static function keepSubscriptionAlive($source, $key)
    {
        try {
            $ch = curl_init();
        } catch (\Exception $e) {
            return 'curl is not installed';
        }
        if ($ch === false) {
            return 'curl could not be initialized';
        }
        curl_setopt_array(
            $ch,
            [
            CURLOPT_URL => "https://optimize.exactlywww.com/v2/",
            CURLOPT_HTTPHEADER => [
                'User-Agent: WebPConvert',
                'Accept: image/*'
            ],
            CURLOPT_POSTFIELDS => [
                'api_key' => $key,
                'webp' => '0',
                'file' => curl_file_create($source),
                'domain' => $_SERVER['HTTP_HOST'],
                'quality' => 60,
                'metadata' => 0
            ],
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false
            ]
        );

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            return 'curl error' . curl_error($ch);
        }
        if (curl_getinfo($ch, CURLINFO_CONTENT_TYPE) != 'application/octet-stream') {
            curl_close($ch);

            $responseObj = json_decode($response);
            if (isset($responseObj->error)) {
                return 'The key is invalid';
            }

            return 'ewww api did not return an image. It could be that the key is invalid. Response: ' . $response;
        }

        // Not sure this can happen. So just in case
        if ($response == '') {
            return 'ewww api did not return anything';
        }

        return true;
    }

    public static function getKeyStatus($key)
    {
        $ch = self::initCurl();

        curl_setopt($ch, CURLOPT_URL, "https://optimize.exactlywww.com/verify/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'api_key' => $key
        ]);

        curl_setopt($ch, CURLOPT_USERAGENT, 'WebPConvert');

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            throw new \Exception(curl_error($ch));
        }
        curl_close($ch);

        if ($response == '') {
            return 'invalid';
        }
        $responseObj = json_decode($response);
        if (isset($responseObj->error)) {
            if ($responseObj->error == 'invalid') {
                return 'invalid';
            } else {
                throw new \Exception('Ewww returned unexpected error: ' . $response);
            }
        }
        if (!isset($responseObj->status)) {
            throw new \Exception('Ewww returned unexpected response to verify request: ' . $response);
        }
        switch ($responseObj->status) {
            case 'great':
            case 'exceeded':
                return $responseObj->status;
        }
        throw new \Exception('Ewww returned unexpected status to verify request: "' . $responseObj->status . '"');
    }

    public static function isWorkingKey($key)
    {
        return (self::getKeyStatus($key) == 'great');
    }

    public static function isValidKey($key)
    {
        return (self::getKeyStatus($key) != 'invalid');
    }

    public static function getQuota($key)
    {
        $ch = self::initCurl();

        curl_setopt($ch, CURLOPT_URL, "https://optimize.exactlywww.com/quota/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, [
            'api_key' => $key
        ]);
        curl_setopt($ch, CURLOPT_USERAGENT, 'WebPConvert');

        $response = curl_exec($ch);
        return $response;
    }
}
