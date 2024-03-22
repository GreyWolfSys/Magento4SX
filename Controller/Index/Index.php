<?php
namespace Altitude\SX\Controller\Index;

class Index extends \Magento\Framework\App\Action\Action
{
    protected $pageFactory;
    private $productRepository;
    private $sx;

    /**
     * @var PageFactory
     */
    protected $_resultPageFactory;

    /**
     * @var JsonFactory
     */
    protected $_resultJsonFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Altitude\SX\Model\SX $sx
    )
    {
        $this->productRepository = $productRepository;
        $this->_resultPageFactory = $resultPageFactory;
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->sx = $sx;

        return parent::__construct($context);
    }

    public function execute()
    {
        if ($this->sx->botDetector() || !$this->getRequest()->isXmlHttpRequest()) {
            $this->_redirect('/');
            return;
        }

        $result = $this->_resultJsonFactory->create();
        $resultPage = $this->_resultPageFactory->create();
        $regioncode = $this->getRequest()->getParam('regioncode');

        $block = $resultPage->getLayout()
                ->createBlock('Altitude\SX\Block\Main')
                ->setTemplate('Altitude_SX::qtyajaxcheckout.phtml')
                ->setData('regioncode',$regioncode)
                ->toHtml();

        $result->setData(['output' => $block]);
        return $result;
    }
}
