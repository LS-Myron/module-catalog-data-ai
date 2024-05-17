<?php
declare(strict_types=1);

namespace MageOS\CatalogDataAI\Controller\Adminhtml\Catalog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\CatalogDataAI\Model\Config;
use MageOS\CatalogDataAI\Model\Product\Enricher;
use OpenAI\Factory;
use OpenAI\Client;
class AiContent extends Action
{
    protected const PREFIX_PROMPT = " with extra params '%s'";
    protected Client $client;
    public function __construct(
        Context $context,
        private readonly JsonFactory $jsonFactory,
        protected Config $config,
        protected ProductRepositoryInterface $product,
        protected StoreManagerInterface $storeManager,
        private readonly Factory $clientFactory,
        private readonly Enricher $enricher
    ) {
        parent::__construct($context);
        $this->client = $this->clientFactory
            ->withApiKey($this->config->getApiKey())
            ->make();
    }

    /**
     * @throws NoSuchEntityException
     */
    public function execute(): Json
    {
        $attributeCode = $this->getRequest()->getParam('attribute_code');
        $value         = strip_tags($this->getRequest()->getParam('value'));
        $productId     = $this->getRequest()->getParam('product_id');
        $storeId       = (int) $this->getRequest()->getParam('store') ?? 0;
        $product       = $this->product->getById($productId, false, $storeId);

        $responseResult = null;

        if ($this->config->isEnabled() && $this->config->getApiKey() || !$this->config->isAsync()) {
            if ($value) {
                $promptPrefix = sprintf(self::PREFIX_PROMPT, $value);
                $this->config->setPrefixPrompt($promptPrefix);
            }

            $responseResult = $this->enricher->prepareResponse($product, $attributeCode, $storeId);
        }

        return $this->jsonFactory->create()->setData([
            'response' => $responseResult->choices[0]
        ]);
    }
}
