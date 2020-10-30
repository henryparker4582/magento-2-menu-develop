<?php

namespace Snowdog\Menu\Model\Menu\Import;

use Magento\Framework\Serialize\SerializerInterface;
use Snowdog\Menu\Api\Data\MenuInterface;
use Snowdog\Menu\Api\Data\NodeInterface;
use Snowdog\Menu\Model\Menu\ExportProcessor;
use Snowdog\Menu\Model\NodeTypeProvider;
use Snowdog\Menu\Model\ResourceModel\Menu as MenuResource;
use Snowdog\Menu\Model\ResourceModel\Menu\Node as NodeResource;

class SampleFile
{
    const MENU_EXCLUDED_FIELDS = [
        MenuInterface::MENU_ID,
        MenuInterface::CREATION_TIME,
        MenuInterface::UPDATE_TIME
    ];

    const NODE_EXCLUDED_FIELDS = [
        NodeInterface::MENU_ID,
        NodeInterface::CREATION_TIME,
        NodeInterface::UPDATE_TIME
    ];

    const NODE_ID_DEFAULT_VALUE = '<an integer value that is only required for nodes that have children>';

    const NODE_DEFAULT_DATA = [
        NodeInterface::TYPE => '<available types: <{types}>',
        NodeInterface::NODE_ID => self::NODE_ID_DEFAULT_VALUE,
        NodeInterface::PARENT_ID => self::NODE_ID_DEFAULT_VALUE,
        NodeInterface::LEVEL => '<an integer value that must be greater than 0 for child nodes>'
    ];

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ExportProcessor
     */
    private $exportProcessor;

    /**
     * @var NodeTypeProvider
     */
    private $nodeTypeProvider;

    /**
     * @var MenuResource
     */
    private $menuResource;

    /**
     * @var NodeResource
     */
    private $nodeResource;

    public function __construct(
        SerializerInterface $serializer,
        ExportProcessor $exportProcessor,
        NodeTypeProvider $nodeTypeProvider,
        MenuResource $menuResource,
        NodeResource $nodeResource
    ) {
        $this->serializer = $serializer;
        $this->exportProcessor = $exportProcessor;
        $this->nodeTypeProvider = $nodeTypeProvider;
        $this->menuResource = $menuResource;
        $this->nodeResource = $nodeResource;
    }

    /**
     * @return array
     */
    public function getFileDownloadContent()
    {
        $data = $this->getSampleData();
        return $this->exportProcessor->generateCsvDownloadFile('sample', $data, array_keys($data));
    }

    /**
     * @return array
     */
    private function getSampleData()
    {
        $data = $this->getMenuData();

        $data[ExportProcessor::STORES_CSV_FIELD] = $this->getStoresData();
        $data[ExportProcessor::NODES_CSV_FIELD] = $this->getNodesData();

        return $data;
    }

    /**
     * @return array
     */
    private function getMenuData()
    {
        return $this->getFieldsData($this->menuResource->getFields(), self::MENU_EXCLUDED_FIELDS);
    }

    /**
     * @return string
     */
    private function getStoresData()
    {
        return '<comma separated integer store IDs>';
    }

    /**
     * @return string
     */
    private function getNodesData()
    {
        $defaultData = self::NODE_DEFAULT_DATA;
        $defaultData[NodeInterface::TYPE] = $this->getNodeTypeDefaultValue();

        $data = $this->getFieldsData(
            $this->nodeResource->getFields(),
            self::NODE_EXCLUDED_FIELDS,
            $defaultData
        );

        return $this->serializer->serialize([$data]);
    }

    /**
     * @return string
     */
    private function getNodeTypeDefaultValue()
    {
        $nodeTypes = array_keys($this->nodeTypeProvider->getLabels());

        return strtr(
            self::NODE_DEFAULT_DATA[NodeInterface::TYPE],
            ['{types}' => implode(' | ', $nodeTypes)]
        );
    }

    /**
     * @return array
     */
    private function getFieldsData(array $fields, array $excludedFields = [], array $defaultData = [])
    {
        $fieldsData = [];
        $excludedFields = array_flip($excludedFields);

        foreach ($fields as $field => $description) {
            if (isset($excludedFields[$field])) {
                continue;
            }

            if (array_key_exists($field, $defaultData)) {
                $fieldsData[$field] = $defaultData[$field];
                continue;
            }

            $fieldsData[$field] = '<' . $description['DATA_TYPE'] . '>';
        }

        return $fieldsData;
    }
}
