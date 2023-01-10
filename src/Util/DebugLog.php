<?php

declare(strict_types=1);

namespace slox_product_sync\Util;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Monolog\Logger;


class DebugLog
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var Client
     */
    private $restClient;

    /**
     * @var Logger
     */
    private $logger;


    public function __construct(SystemConfigService $systemConfigService, Logger $logger)
    {
        $this->systemConfigService = $systemConfigService;
        $this->restClient = new Client();
        $this->logger = $logger;
    }

    /**
     * @return Bool
     */
    public function WriteLocalLog(string $message, $context)
    {   
        if (is_null($context))
            $context = [];
        elseif (is_string($context))
            $context = [strval($context)];
        elseif (!is_array($context))
            $context = get_object_vars($context);
        $this->logger->log(Logger::INFO, $message, $context);

        return false;
    }

    /**
     * @return Bool
     */
    public function sendLog(string $message, $context = null)
    {
        $this->WriteLocalLog($message, $context);

        if (((bool) $this->systemConfigService->get('slox_product_sync.config.DebugPost')) && $this->systemConfigService->get('slox_product_sync.config.DebugPostURL')) {
            $url = strval($this->systemConfigService->get('slox_product_sync.config.DebugPostURL'));

            $request = new Request(
                'POST',
                $url,
                ['Content-Type' => 'application/json'],
                json_encode(['_DebuglogOrigin' => $message, 'data' => json_encode($context)])
            );
            $response = $this->restClient->send($request);
        }

        return false;
    }

    /**
     * @return Bool
     */
    public function sendLog_forwardRequestCopy(string $message, $request)
    {

        $this->WriteLocalLog($message, $request);
        if (((bool) $this->systemConfigService->get('slox_product_sync.config.DebugPost')) && $this->systemConfigService->get('slox_product_sync.config.DebugPostURL')) {
            $queryBag = $request->query;
            $requestBag = $request->request;

            $requestGuzz = new Request(
                'POST',
                $this->systemConfigService->get('slox_product_sync.config.DebugPostURL'),
                ['Content-Type' => 'application/json'],
                json_encode(array_replace(['_DebuglogOrigin' => $message], ['getperm' => $queryBag->all(), 'postperm' => $requestBag->all()], ['Body' => $request->getContent(), 'server' => $request->server->all()]))
            );
            $response = $this->restClient->send($requestGuzz);
        }

        return false;
    }
}
