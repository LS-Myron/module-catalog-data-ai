<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Ui\DataProvider\Product\Form\Modifier;

use Magento\Backend\Model\UrlInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Stdlib\ArrayManager;
use MageOS\CatalogDataAI\Model\Product\Enricher;
use MageOS\CatalogDataAI\Model\Config;

class GenerateAiContentButton extends AbstractModifier
{
    public const TITLE_AI_BUTTON = 'Generate Ai Content';
    public const TITLE_RESET_BUTTON = 'Reset Ai Content';
    public const URL_CONTROLLER = 'catalog_data_ai/catalog/aicontent';
    public const PATH_SUFFIX_AI_BUTTON = '/ai_button/arguments/data/config';
    public const PATH_SUFFIX_RESET_BUTTON = '/ai_reset_button/arguments/data/config';

    public function __construct(
        protected Enricher $enricher,
        protected UrlInterface $urlBuilder,
        protected RequestInterface $request,
        protected ArrayManager $arrayManager,
        protected Config $config,
    ) {
    }

    public function modifyData(array $data): array
    {
        return $data;
    }

    public function modifyMeta(array $meta): array
    {
        if ($this->config->IsAsync() || !$this->config->enableGenerateContentButtons()) {
            return $meta;
        }

        foreach ($this->getAttributes() as $attributeCode) {
            $path = $this->getParentPath($attributeCode, $meta);
            if ($path === null) {
                continue;
            }

//            TODO: make sure the buttons are in 1 admin__field-group-additional instead of 2 separate elements
            $meta = $this->populateMeta($path . self::PATH_SUFFIX_AI_BUTTON, $meta, $this->generateAiContentButton($attributeCode));
            $meta = $this->populateMeta($path . self::PATH_SUFFIX_RESET_BUTTON, $meta, $this->generateResetButton($attributeCode));
        }

        return $meta;
    }

    public function populateMeta($path, $meta, $content): array
    {
        $meta = $this->arrayManager->populate($path, $meta);
        return $this->arrayManager->set($path,
            $meta,
            $content
        );
    }

    public function getParentPath(string $attributeCode, array $meta): ?string
    {
        $origPath = $this->arrayManager->findPath($attributeCode, $meta);
        if (!$origPath) {
            return null;
        }

        $pathArray = explode('/', $origPath);
        array_pop($pathArray);

        return implode('/', $pathArray);
    }

    public function getAttributes(): array
    {
        return $this->enricher->getAttributes();
    }

    public function generateAiContentButton(string $attributeCode): array
    {
        $baseUrl = $this->urlBuilder->getBaseUrl();
        $adminUrlPrefix = $this->urlBuilder->getAreaFrontName();
        $fullUrl = $baseUrl . $adminUrlPrefix . '/' . self::URL_CONTROLLER;
        return [
            'url' => $fullUrl,
            'product_id' => $this->request->getParam('id'),
            'store' => $this->request->getParam('store'),
            'targetName' => $attributeCode,
            'title' => __(self::TITLE_AI_BUTTON),
            'componentType' => "button",
            'component' => 'MageOS_CatalogDataAI/js/components/generate-ai-component',
            'template' => 'ui/form/components/button/container',
            'displayAsLink' => false,
            'additionalForGroup' => true,
            'provider' => false,
            'source' => self::DEFAULT_GENERAL_PANEL,
            'additionalClasses' => 'admin__field-small primary'
        ];
    }

    public function generateResetButton(string $attributeCode): array
    {
        return [
            'product_id' => $this->request->getParam('id'),
            'targetName' => $attributeCode,
            'title' => __(self::TITLE_RESET_BUTTON),
            'componentType' => "button",
            'component' => 'MageOS_CatalogDataAI/js/components/reset-content-component',
            'template' => 'ui/form/components/button/container',
            'displayAsLink' => false,
            'additionalForGroup' => true,
            'provider' => false,
            'source' => self::DEFAULT_GENERAL_PANEL,
            'additionalClasses' => 'admin__field-small'
        ];
    }
}
