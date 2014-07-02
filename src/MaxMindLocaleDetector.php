<?php

namespace Heystack\Maxmind;

use Heystack\Core\Identifier\Identifier;
use Heystack\Ecommerce\Locale\Interfaces\ZoneServiceInterface;
use Heystack\Ecommerce\Locale\Traits\HasLocaleServiceTrait;
use Heystack\Ecommerce\Locale\Traits\HasZoneServiceTrait;
use Heystack\Ecommerce\Locale\Interfaces\LocaleDetectionInterface;
use Heystack\Ecommerce\Locale\Interfaces\LocaleServiceInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * @package Heystack\Maxmind
 */
class MaxMindLocaleDetector implements LocaleDetectionInterface
{
    use HasLocaleServiceTrait;
    use HasZoneServiceTrait;

    /**
     * @var string
     */
    protected $key;

    /**
     * Timeout in seconds
     * @var int
     */
    protected $timeout = 2;

    /**
     * @var array
     */
    protected $excludedUserAgents;

    /**
     * @param \Heystack\Ecommerce\Locale\Interfaces\LocaleServiceInterface $localeServce
     * @param string $key
     * @param int $timeout
     * @param array $excludedUserAgents
     * @throws \InvalidArgumentException
     */
    public function __construct(
        LocaleServiceInterface $localeServce,
        $key,
        $timeout = 2,
        array $excludedUserAgents = []
    )
    {
        if (!is_int($timeout)) {
            throw new InvalidArgumentException("timeout must be an int");
        }
        if (!is_array($excludedUserAgents)) {
            throw new InvalidArgumentException("excludedUserAgents must be an array");
        }

        $this->localeService = $localeServce;
        $this->key = (string) $key;
        $this->timeout = (int) $timeout;
        $this->excludedUserAgents = $excludedUserAgents;
    }

    /**
     * @param \SS_HTTPRequest $request
     * @return \Heystack\Ecommerce\Locale\Interfaces\CountryInterface|null
     */
    public function getCountryForRequest(\SS_HTTPRequest $request)
    {
        $location = false;

        if ($this->isAllowedUserAgent($request->getHeader('User-Agent'))) {
            $fp = fopen(
                sprintf('http://geoip.maxmind.com/a?l=%s&i=%s', $this->key, $request->getIP()),
                'r',
                null,
                stream_context_create(['http' => ['timeout' => $this->timeout]])
            );
            if (is_resource($fp)) {
                $location = stream_get_contents($fp);
                fclose($fp);
            }
        }

        return $location ? $this->localeService->getCountry(new Identifier($location)) : null;
    }

    /**
     * @param \SS_HTTPRequest $request
     * @throws \RuntimeException
     * @return \Heystack\Ecommerce\Locale\Interfaces\ZoneInterface|null
     */
    public function getZoneForRequest(\SS_HTTPRequest $request)
    {
        if (!$this->zoneService instanceof ZoneServiceInterface) {
            throw new RuntimeException("Can't use getZoneForRequest without Zone service configured");
        }

        $country = $this->getCountryForRequest($request);

        return $country ? $this->zoneService->getZoneForCountry($country->getIdentifier()) : null;
    }

    /**
     * @param string $userAgent
     * @return bool
     */
    protected function isAllowedUserAgent($userAgent)
    {
        foreach ($this->excludedUserAgents as $excludedAgentString) {
            if (strpos($userAgent, $excludedAgentString) !== false) {
                return false;
            }
        }

        return true;
    }
}