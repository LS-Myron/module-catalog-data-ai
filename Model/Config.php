<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
USE Magento\Catalog\Model\Product;

class Config
{
    public const string XML_PATH_ENRICH_ENABLED = 'catalog_ai/settings/active';
    public const string XML_PATH_ATTRIBUTE_GENERATE_CONTENT_BUTTONS = 'catalog_ai/settings/generate_content_buttons';
    public const string XML_PATH_USE_ASYNC = 'catalog_ai/settings/async';
    public const string XML_PATH_OPENAI_API_KEY = 'catalog_ai/open_ai/openai_key';
    public const string XML_PATH_OPENAI_API_MODEL = 'catalog_ai/open_ai/openai_model';
    public const string XML_PATH_OPENAI_API_MAX_TOKENS = 'catalog_ai/open_ai/openai_max_tokens';
    public const string XML_PATH_OPENAI_API_ADVANCED_SYSTEM_PROMPT = 'catalog_ai/advanced/system_prompt';
    public const string XML_PATH_OPENAI_API_ADVANCED_TEMPERATURE = 'catalog_ai/advanced/temperature';
    public const string XML_PATH_OPENAI_API_ADVANCED_FREQUENCY_PENALTY = 'catalog_ai/advanced/frequency_penalty';
    public const string XML_PATH_OPENAI_API_ADVANCED_PRESENCE_PENALTY = 'catalog_ai/advanced/presence_penalty';
    protected string $prefixPrompt = '';

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig
    ) {}

    public function isEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENRICH_ENABLED
        );
    }

    public function IsAsync(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_USE_ASYNC
        );
    }

    public function getApiKey(): string
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_API_KEY
        );
    }

    public function getApiModel(): mixed
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_API_MODEL
        );
    }

    public function getApiMaxTokens(): int
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_API_MAX_TOKENS
        );
    }

    public function getProductPrompt(string $attributeCode): ?string
    {
        $path = 'catalog_ai/product/' . $attributeCode;
        $prompt = $this->scopeConfig->getValue(
            $path
        );
        $prefix = $this->getPrefixPrompt();

        return $prefix ? $prefix . $prompt : $prompt;
    }

    public function getProductPromptToken(String $attributeCode)
    {
        $path = 'catalog_ai/product/' . $attributeCode;
        return $this->scopeConfig->getValue(
            $path
        );
    }

    public function canEnrich(Product $product): bool
    {
        return $this->isEnabled() && $this->getApiKey() && $product->isObjectNew();
    }

    public function getSystemPrompt(): mixed
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_API_ADVANCED_SYSTEM_PROMPT
        );
    }

    public function getTemperature(): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_API_ADVANCED_TEMPERATURE
        );
    }

    public function getFrequencyPenalty(): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_API_ADVANCED_FREQUENCY_PENALTY
        );
    }

    public function getPresencePenalty(): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_OPENAI_API_ADVANCED_PRESENCE_PENALTY
        );
    }

    public function enableGenerateContentButtons(): float
    {
        return (float)$this->scopeConfig->getValue(
            self::XML_PATH_ATTRIBUTE_GENERATE_CONTENT_BUTTONS
        );
    }

    public function setPrefixPrompt(string $prefixPrompt): void
    {
        $this->prefixPrompt = $prefixPrompt;
    }

    public function getPrefixPrompt(): string
    {
        return $this->prefixPrompt;
    }
}
