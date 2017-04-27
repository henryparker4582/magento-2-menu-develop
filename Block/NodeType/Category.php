<?php

namespace Snowdog\Menu\Block\NodeType;

use Magento\Framework\View\Element\Template;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Profiler;
use Magento\Framework\Registry;
use Snowdog\Menu\Api\NodeTypeInterface;

class Category extends Template implements NodeTypeInterface
{
    protected $nodes;
    protected $categoryUrls;
    /**
     * @var ResourceConnection
     */
    private $connection;

    /**
     * @var Profiler
     */
    private $profiler;

    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * Determines whether a "View All" link item,
     * of the current parent node, could be added to menu.
     *
     * @var bool
     */
    private $viewAllLink = true;

    protected $_template = 'menu/node_type/category.phtml';

    public function __construct(
        Template\Context $context,
        ResourceConnection $connection,
        Profiler $profiler,
        Registry $coreRegistry,
        $data = []
    ) {
        $this->connection = $connection;
        $this->profiler = $profiler;
        $this->coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * @return \Magento\Catalog\Model\Category|null
     */
    public function getCurrentCategory()
    {
        return $this->coreRegistry->registry('current_category');
    }

    /**
     * @return array
     */
    public function getNodeCacheKeyInfo()
    {
        $info = [
            'module_' . $this->getRequest()->getModuleName(),
            'controller_' . $this->getRequest()->getControllerName(),
            'route_' . $this->getRequest()->getRouteName(),
            'action_' . $this->getRequest()->getActionName()
        ];

        $category = $this->getCurrentCategory();
        if ($category) {
            $info[] = 'category_' . $category->getId();
        }

        return $info;
    }

    public function getJsonConfig()
    {
        $this->profiler->start(__METHOD__);
        $connection = $this->connection->getConnection('read');
        $select = $connection->select()->from(
            ['a' => $this->connection->getTableName('eav_attribute')],
            ['attribute_id']
        )->join(
            ['t' => $this->connection->getTableName('eav_entity_type')],
            't.entity_type_id = a.entity_type_id',
            []
        )->where('t.entity_type_code = ?', \Magento\Catalog\Model\Category::ENTITY)->where(
            'a.attribute_code = ?',
            'name'
        );
        $nameAttributeId = $connection->fetchOne($select);
        $select = $connection->select()->from(
            ['e' => $this->connection->getTableName('catalog_category_entity')],
            ['entity_id' => 'e.entity_id', 'parent_id' => 'e.parent_id']
        )->join(
            ['v' => $this->connection->getTableName('catalog_category_entity_varchar')],
            'v.entity_id = e.entity_id AND v.store_id = 0 AND v.attribute_id = ' . $nameAttributeId,
            ['name' => 'v.value']
        )->where('e.level > 0')->order('e.level ASC')->order('e.position ASC');
        $data = $connection->fetchAll($select);

        $labels = [];

        foreach ($data as $row) {
            if (isset($labels[$row['parent_id']])) {
                $label = $labels[$row['parent_id']];
            } else {
                $label = [];
            }
            $label[] = $row['name'];
            $labels[$row['entity_id']] = $label;
        }

        $options = [];
        foreach ($labels as $id => $label) {
            $label = implode(' > ', $label);
            $options[$label] = $id;
        }

        $data = [
            'snowMenuAutoCompleteField' => [
                'type'    => 'category',
                'options' => $options,
                'message' => __('Category not found'),
            ],
        ];
        $this->profiler->stop(__METHOD__);
        return json_encode($data);
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
        $table = $this->connection->getTableName('url_rewrite');
        $select = $this->connection->getConnection('read')
                                   ->select()
                                   ->from($table, ['entity_id', 'request_path'])
                                   ->where('entity_type = ?', 'category')
                                   ->where('redirect_type = ?', 0)
                                   ->where('store_id = ?', $this->_storeManager->getStore()->getId())
                                   ->where('entity_id IN (' . implode(',', $categoryIds) . ')');
        $this->categoryUrls = $this->connection->getConnection('read')->fetchPairs($select);
        $this->profiler->stop(__METHOD__);
    }

    /**
     * @param int $nodeId
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isCurrentCategory(int $nodeId)
    {
        if (!isset($this->nodes[$nodeId])) {
            throw new \InvalidArgumentException('Invalid node identifier specified');
        }

        $node = $this->nodes[$nodeId];
        $categoryId = (int) $node->getContent();
        $currentCategory = $this->getCurrentCategory();

        return $currentCategory
            ? $currentCategory->getId() == $categoryId
            : false;
    }

    /**
     * @param int $nodeId
     * @param int|null $storeId
     * @return string|false
     * @throws \InvalidArgumentException
     */
    public function getCategoryUrl(int $nodeId, $storeId = null)
    {
        if (!isset($this->nodes[$nodeId])) {
            throw new \InvalidArgumentException('Invalid node identifier specified');
        }

        $node = $this->nodes[$nodeId];
        $categoryId = (int) $node->getContent();

        if (isset($this->categoryUrls[$categoryId])) {
            $baseUrl = $this->_storeManager->getStore($storeId)->getBaseUrl();
            $categoryUrlPath = $this->categoryUrls[$categoryId];

            return $baseUrl . $categoryUrlPath;
        }

        return false;
    }

    public function getHtml(int $nodeId, int $level, $storeId = null)
    {
        $classes = $level == 0 ? 'level-top' : '';
        $node = $this->nodes[$nodeId];
        $url = $this->getCategoryUrl($nodeId, $storeId);
        $title = $node->getTitle();
        return <<<HTML
<a href="$url" class="$classes" role="menuitem"><span>$title</span></a>
HTML;
    }

    public function getAddButtonLabel()
    {
        return __("Add Category node");
    }

    /**
     * @return bool
     */
    public function isViewAllLinkAllowed()
    {
        return $this->viewAllLink;
    }
}
