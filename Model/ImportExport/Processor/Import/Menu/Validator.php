<?php

namespace Snowdog\Menu\Model\ImportExport\Processor\Import\Menu;

use Snowdog\Menu\Api\Data\MenuInterface;
use Snowdog\Menu\Model\ImportExport\Processor\ExtendedFields;
use Snowdog\Menu\Model\ImportExport\Processor\Store;
use Snowdog\Menu\Model\ImportExport\Processor\Import\Validator\AggregateError;

class Validator
{
    const REQUIRED_FIELDS = [
        MenuInterface::TITLE,
        MenuInterface::IDENTIFIER,
        MenuInterface::CSS_CLASS,
        MenuInterface::IS_ACTIVE,
        ExtendedFields::STORES
    ];

    /**
     * @var Store
     */
    private $store;

    /**
     * @var AggregateError
     */
    private $aggregateError;

    public function __construct(Store $store, AggregateError $aggregateError)
    {
        $this->store = $store;
        $this->aggregateError = $aggregateError;
    }

    public function validate(array $data)
    {
        $this->validateRequiredFields($data);

        if (isset($data[ExtendedFields::STORES])) {
            $this->validateStores($data[ExtendedFields::STORES]);
        }
    }

    private function validateRequiredFields(array $data)
    {
        $missingFields = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                $missingFields[] = $field;
            }
        }

        if ($missingFields) {
            $this->aggregateError->addError(
                __('The following menu required import fields are missing: "%1".', implode('", "', $missingFields))
            );
        }
    }

    private function validateStores(array $stores)
    {
        $invalidStores = [];

        foreach ($stores as $store) {
            if (!$this->store->get($store)) {
                $invalidStores[] = $store;
            }
        }

        if ($invalidStores) {
            $this->aggregateError->addError(
                __('The following store codes/IDs are invalid: "%1".', implode('", "', $invalidStores))
            );
        }
    }
}
