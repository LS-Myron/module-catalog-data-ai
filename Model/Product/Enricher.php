<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\CatalogDataAI\Model\Config;
use OpenAI\Factory;
use OpenAI\Client;
use OpenAI\Responses\Meta\MetaInformation;
use OpenAI\Exceptions\ErrorException;
use OpenAI\Responses\Chat\CreateResponse;

class Enricher
{
    private Client $client;

    public function __construct(
        private Factory $clientFactory,
        private Config  $config
    )
    {
        $this->client = $this->clientFactory
            ->withApiKey($this->config->getApiKey())
            ->make();
    }

    public function getAttributes(): array
    {
        return [
            'short_description',
            'description',
            'meta_title',
            'meta_keyword',
            'meta_description',
        ];
    }

    /**
     * @todo move to parser class/pool
     */
    public function parsePrompt(ProductInterface $product, string $prompt, int $storeId): string
    {
        $prompt = $this->addOutputLanguage($prompt, $storeId);

        return preg_replace_callback('/\{\{(.+?)\}\}/', function ($matches) use ($product) {
            return $product->getData($matches[1]);
        }, $prompt);
    }

    public function addOutputLanguage(string $prompt, int $storeId = 0): string
    {
        if (!$this->config->getIsOutputTranslated()) {
            return $prompt;
        }

        $outputLanguage = $this->config->getOutputLanguage($storeId);

        return $prompt . sprintf(' text to "%s"', $outputLanguage);
    }

    public function getOpenAiResponse(ProductInterface $product, string $prompt, int $storeId): CreateResponse
    {
        return $this->client->chat()->create(
            [
                'model' => $this->config->getApiModel(),
                'temperature' => $this->config->getTemperature(),
                'frequency_penalty' => $this->config->getFrequencyPenalty(),
                'presence_penalty' => $this->config->getPresencePenalty(),
                'max_tokens' => $this->config->getApiMaxTokens(),
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $this->config->getSystemPrompt()
                    ],
                    [
                        'role' => 'user',
                        'content' => $this->parsePrompt($product, $prompt, $storeId)
                    ]
                ]
            ]
        );
    }

    public function prepareResponse(ProductInterface $product, string $attributeCode, int $storeId): ?CreateResponse
    {
        $prompt = $this->config->getProductAttributePrompt($attributeCode);
        if ($prompt === null) {
            return null;
        }
        return $this->getOpenAiResponse($product, $prompt, $storeId);
    }

    public function enrichAttribute(ProductInterface $product, string $attributeCode, int $storeId): void
    {
        $responseResult = $this->prepareResponse($product, $attributeCode, $storeId);

        if ((!$product->getData('mageos_catalogai_overwrite')
                && $product->getData($attributeCode))
            || $responseResult === null
        ) {
            return;
        }

        if (isset($responseResult->choices)) {
            $product
                ->setData($attributeCode, $responseResult->choices[0]->message->content)
                ->setStoreId($storeId);
        }

        if ($responseResult->meta()) {
            $this->backoff($responseResult->meta());
        }
    }

    public function backoff(MetaInformation $meta): void
    {
        if ($meta->requestLimit->remaining < 1) {
            sleep($this->strToSeconds($meta->requestLimit->reset));
        }
        // 1 token ~= 0.75 word
        // do not use config value
        if ($meta->tokenLimit->remaining < 1000) {
            sleep($this->strToSeconds($meta->tokenLimit->reset));
        }
    }

    private function strToSeconds(string $time): float|int
    {
        preg_match('/(?:([0-9]+)h)?(?:([0-9]+)m)?(?:([0-9]+)s)?/', $time, $matches);

        $hours = isset($matches[1]) ? intval($matches[1]) : 0;
        $minutes = isset($matches[2]) ? intval($matches[2]) : 0;
        $seconds = isset($matches[3]) ? intval($matches[3]) : 0;

        return $hours * 3600 + $minutes * 60 + $seconds;
    }

    public function execute(ProductInterface $product, int $storeId = 0): void
    {
        foreach ($this->getAttributes() as $attributeCode) {
            try {
                $this->enrichAttribute($product, $attributeCode, $storeId);
            } catch (ErrorException $e) {
                // try it one more time just in case we failed to catch the limit in backoff
                sleep(60);
                $this->enrichAttribute($product, $attributeCode, $storeId);
            }
        }

        //@TODO: throw exception?
    }
}
