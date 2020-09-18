<?php

declare(strict_types=1);

namespace Snowdog\Menu\Ui\DataProvider\Menu\Form;

use Magento\Ui\DataProvider\AbstractDataProvider;
use Snowdog\Menu\Model\ResourceModel\Menu\CollectionFactory;
use Snowdog\Menu\Model\Menu;

class MenuDataProvider extends AbstractDataProvider
{
    /** @var array */
    private $loadedData = [];

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $collectionFactory->create();
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $meta,
            $data
        );
    }

    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        /** @var Menu $menu */
        foreach ($items as $menu) {
            $menu->addData(['stores' => $menu->getStores()]);
            $this->loadedData[$menu->getId()] = $menu->getData();
        }

        return $this->loadedData;
    }
}
