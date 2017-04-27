<?php

namespace Snowdog\Menu\Block\NodeType;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Profiler;
use Magento\Store\Model\StoreManagerInterface;
use Snowdog\Menu\Api\NodeTypeInterface;

class CustomUrl extends Template implements NodeTypeInterface
{
    protected $nodes;
    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * Determines whether a "View All" link item,
     * of the current parent node, could be added to menu.
     *
     * @var bool
     */
    private $viewAllLink = true;

    protected $_template = 'menu/node_type/custom_url.phtml';

    public function __construct(
        Template\Context $context,
        Profiler $profiler,
        $data = []
    ) {
        $this->profiler = $profiler;
        parent::__construct($context, $data);
    }


    public function fetchData(array $nodes)
    {
        $this->profiler->start(__METHOD__);
        $localNodes = [];
        $categoryIds = [];
        foreach ($nodes as $node) {
            $localNodes[$node->getId()] = $node;
            $categoryIds[] = (int)$node->getContent();
        }
        $this->nodes = $localNodes;
        $this->profiler->stop(__METHOD__);
    }

    public function getHtml(int $nodeId, int $level)
    {
        $classes = $level == 0 ? 'level-top' : '';
        $node = $this->nodes[$nodeId];
        $url = $this->_storeManager->getStore()->getBaseUrl() . $node->getContent();
        $title = $node->getTitle();
        return <<<HTML
<a href="$url" class="$classes" role="menuitem"><span>$title</span></a>
HTML;
    }

    public function getAddButtonLabel()
    {
        return __("Add Custom Url node");
    }

    /**
     * @return bool
     */
    public function isViewAllLinkAllowed()
    {
        return $this->viewAllLink;
    }
}
