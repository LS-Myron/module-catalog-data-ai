<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Model\Product;

use MageOS\CatalogDataAI\Model\Config;
use Magento\Catalog\Model\Product;
use OpenAI\Factory;
use OpenAI\Client;
use OpenAI\Responses\Chat\CreateResponse;
use OpenAI\Responses\Chat\CreateResponseChoice;

class Enricher
{
    private Client $client;

    public function __construct(
        private readonly Factory $clientFactory,
        private readonly Config $config
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
    public function parsePrompt($prompt, $product): string
    {
        $this->config->setOutputLanguage('nl_NL');

        $prompt = $this->addOutputLanguage($prompt);

        return preg_replace_callback('/\{\{(.+?)\}\}/', function ($matches) use ($product) {
            return $product->getData($matches[1]);
        }, $prompt);
    }

    public function addOutputLanguage($prompt): string
    {
        $outputLanguage = $this->config->getOutputLanguage();
        return $prompt . sprintf(' text to "%s"', $outputLanguage);
    }

    public function getOpenAiResponse($prompt, $product): CreateResponse
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
                        'content' => $this->parsePrompt($prompt, $product)
                    ]
                ]
            ]
        );
    }

    public function prepareResponse($product, $attributeCode): ?CreateResponse
    {
        $prompt = $this->config->getProductAttributePrompt($attributeCode);
        if ($prompt === null) {
            return null;
        }
        return $this->getOpenAiResponse($prompt, $product);
    }

    public function enrichAttribute($product, $attributeCode): void
    {
        if(!$product->getData('mageos_catalogai_overwrite') && $product->getData($attributeCode)){
            return;
        }

        $responseResult = $this->prepareResponse($product, $attributeCode);
        $responseResultContent = $responseResult->choices[0]?->message->content;
        if ($responseResultContent !== null) {
            $product->setData($attributeCode, $responseResultContent);
        }
    }

    public function execute(Product $product): void
    {
        foreach ($this->getAttributes() as $attributeCode) {
            $this->enrichAttribute($product, $attributeCode);
        }
        //@TODO: throw exception?
    }
}
