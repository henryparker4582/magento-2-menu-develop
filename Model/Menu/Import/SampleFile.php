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
        $nodeTypes = array_keys($this->nodeTypeProvider->getLabels());
        $nodeData = [NodeInterface::TYPE => 'available types: <' . implode(' | ', $nodeTypes) . '>'];

        $data = $this->getFieldsData(
            $this->nodeResource->getFields(),
            self::NODE_EXCLUDED_FIELDS,
            $nodeData
        );

        return $this->serializer->serialize([$data]);
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
