<?php

declare(strict_types=1);

namespace Snowdog\Menu\Model\ImportExport\Processor\Import;

use Snowdog\Menu\Api\Data\MenuInterface;
use Snowdog\Menu\Api\Data\MenuInterfaceFactory;
use Snowdog\Menu\Api\MenuRepositoryInterface;

class Menu
{
    /**
     * @var MenuInterfaceFactory
     */
    private $menuFactory;

    /**
     * @var MenuRepositoryInterface
     */
    private $menuRepository;

    /**
     * @var Menu\DataProcessor
     */
    private $dataProcessor;

    /**
     * @var Menu\Validator
     */
    private $validator;

    public function __construct(
        MenuInterfaceFactory $menuFactory,
        MenuRepositoryInterface $menuRepository,
        Menu\DataProcessor $dataProcessor,
        Menu\Validator $validator
    ) {
        $this->menuFactory = $menuFactory;
        $this->menuRepository = $menuRepository;
        $this->dataProcessor = $dataProcessor;
        $this->validator = $validator;
    }

    public function createMenu(array $data, array $stores): MenuInterface
    {
        $menu = $this->menuFactory->create();

        $menu->setData($this->dataProcessor->getMenuData($data));
        $this->menuRepository->save($menu);
        $menu->saveStores($this->dataProcessor->getStoreIds($stores));

        return $menu;
    }

    public function validateImportData(array $data): void
    {
        $this->validator->validate($data);
    }
}
