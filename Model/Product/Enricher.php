<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Model\Product;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\CatalogDataAI\Model\Config;
use OpenAI\Factory;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse;

class Enricher
{
    private Client $client;

    public function __construct(
        private readonly Factory $clientFactory,
        private readonly Config $config,
        protected StoreManagerInterface $storeManager,
    ) {
        $this->client = $this->clientFactory->withApiKey($this->config->getApiKey())
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
            $x = 'debug';

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
        if(!$product->getData('mageos_catalogai_overwrite') && $product->getData($attributeCode)){
            return;
        }

        $responseResult = $this->prepareResponse($product, $attributeCode, $storeId);
        $responseResultContent = $responseResult;
        if (isset($responseResultContent->choices)) {
            $product
                ->setData($attributeCode, $responseResultContent->choices[0]->message->content)
                ->setStoreId($storeId);
        }
    }

    public function execute(ProductInterface $product, int $storeId = 0): void
    {
        foreach ($this->getAttributes() as $attributeCode) {
            $this->enrichAttribute($product, $attributeCode, $storeId);
        }
    }
}
