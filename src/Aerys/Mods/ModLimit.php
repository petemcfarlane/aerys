<?php

namespace Aerys\Mods;

use Aerys\Server,
    Aerys\Status,
    Aerys\Reason;

/**
 * Enforce request rate limits
 * 
 * Rate limits are defined as key-value pairs inside the config array's "limits" key. The key
 * represents the time period in seconds and the value sets the maximum number of requests
 * allowed during that time period before rate limiting is invoked. In the example config array
 * below, client requests are limited by the following criteria:
 * 
 * 1. 100 requests per minute (60)
 * 2. 2500 requests per hour (3600)
 * 
 * Rate-limited requests DO count against a client's rate. For example, if a client is allowed 
 * one request per minute and that client makes five requests in rapid succession, the rate limit
 * will remain in effect for five minutes until the client's average request rate/minute dips
 * below the maximum allowable threshold.
 * 
 * Example config array:
 * 
 * ```
 * $modLimitConfig = [
 *     'ipProxyHeader'   => NULL,
 *     'limits' => [
 *         60 => 100,
 *         3600 => 2500
 *     ]
 * ];
 * ```
 */
class ModLimit implements OnRequestMod {
    
    private $httpServer;
    
    private $ipProxyHeader;
    private $rateLimits = [];
    private $ratePeriods = [];
    private $rateAllowances = [];
    private $lastRateCheckedAt = [];
    private $maxRatePeriod;
    private $onRequestPriority = 10;
    
    function __construct(Server $httpServer, array $config) {
        $this->httpServer = $httpServer;
        
        if (empty($config['limits'])) {
            throw new \InvalidArgumentException(
                'No rate limits specified'
            );
        }
        
        if (!empty($config['ipProxyHeader'])) {
            $this->ipProxyHeader = 'HTTP_' . strtoupper($config['ipProxyHeader']);
        }
        
        ksort($config['limits']);
        $this->maxRatePeriod = max(array_keys($config['limits']));
        $this->rateLimits = $config['limits'];
        $this->ratePeriods[] = array_keys($config['limits']);
    }
    
    function getOnRequestPriority() {
        return $this->onRequestPriority;
    }
    
    /**
     * Tracks request rates per-client and assigns a 429 response if the client is rate-limited
     * 
     * @param Server $server
     * @param int $requestId
     * @return void
     */
    function onRequest($requestId) {
        $asgiEnv = $this->httpServer->getRequest($requestId);
        
        if ($this->ipProxyHeader) {
            $clientIp = isset($asgiEnv[$this->ipProxyHeader])
                ? $asgiEnv[$this->ipProxyHeader]
                : $asgiEnv['REMOTE_ADDR'];
        } else {
            $clientIp = $asgiEnv['REMOTE_ADDR'];
        }
        
        $now = time();
        
        if ($this->isRateLimited($clientIp, $now)) {
            $this->limit($requestId);
        }
        
        $this->clearExpiredRateLimitData($now);
    }
    
    private function isRateLimited($clientIp, $now) {
        if (isset($this->lastRateCheckedAt[$clientIp])) {
            $elapsedTime = $now - $this->lastRateCheckedAt[$clientIp];
            // move the clientIp check timestamp to the end of the ordered array for timed removal
            unset($this->lastRateCheckedAt[$clientIp]);
        } else {
            $elapsedTime = 0;
            $this->rateAllowances[$clientIp] = $this->rateLimits;
        }
        
        $this->lastRateCheckedAt[$clientIp] = $now;
        
        // NOTICE: We are operating on this value BY REFERENCE.
        $allowances =& $this->rateAllowances[$clientIp];
        
        foreach ($this->rateLimits as $period => $rate) {
            $allowances[$period] += $elapsedTime * ($rate / $period);
            
            // Throttle (you can't save up "rate credit" beyond the maximum)
            if ($allowances[$period] > $rate) {
                $allowances[$period] = $rate;
            }
            
            // All requests (even rate-limited requests) count against your average rate
            $allowances[$period] -= 1.0;
            
            if ($allowances[$period] < 0.0) {
                return TRUE;
            }
        }
        
        return FALSE;
    }
    
    private function limit($requestId) {
        $body = "<html><body><h1>429 Too Many Requests</h1></body></html>";
        $headers = [
            'Content-Type' => 'text\html',
            'Content-Length' => strlen($body)
        ];
        $asgiResponse = [Status::TOO_MANY_REQUESTS, Reason::HTTP_429, $headers, $body];
        
        $this->httpServer->setResponse($requestId, $asgiResponse);
    }
    
    private function clearExpiredRateLimitData($now) {
        $cutoffTime = $now - $this->maxRatePeriod;
        
        foreach ($this->lastRateCheckedAt as $clientIp => $lastCheckedAt) {
            if ($lastCheckedAt < $cutoffTime) {
                unset(
                    $this->lastRateCheckedAt[$clientIp],
                    $this->rateAllowances[$clientIp]
                );
            } else {
                break;
            }
        }
    }
    
}

