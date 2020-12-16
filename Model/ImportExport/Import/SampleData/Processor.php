<?php

declare(strict_types=1);

namespace Snowdog\Menu\Model\ImportExport\Import\SampleData;

class Processor
{
    const BOOLEAN_TYPES = ['smallint', 'tinyint'];
    const BOOLEAN_FIELD_DEFAULT_VALUE = 'Valid values: <1 | 0>';

    const OPTIONAL_FIELD_LABEL = '[optional]';
    const TIMESTAMP_DEFAULT_VALUE = 'current_timestamp()';

    public function getFieldsData(array $fields, array $excludedFields = [], array $defaultData = []): array
    {
        $fieldsData = [];
        $excludedFields = array_flip($excludedFields);

        foreach ($fields as $field => $fieldDescription) {
            if (!isset($excludedFields[$field])) {
                $fieldsData[$field] = $this->getFieldData($field, $fieldDescription, $defaultData);
            }
        }

        return $fieldsData;
    }

    private function getFieldData(string $field, array $fieldDescription, array $defaultData = []): string
    {
        switch (true) {
            case array_key_exists($field, $defaultData):
                $data = (string) $defaultData[$field];
                break;
            case in_array($fieldDescription['DATA_TYPE'], self::BOOLEAN_TYPES):
                $data = self::BOOLEAN_FIELD_DEFAULT_VALUE;
                break;
            default:
                $data = '[type: ' . $fieldDescription['DATA_TYPE'] . ']';
        }

        return $data . $this->getFieldDetails($fieldDescription);
    }

    private function getFieldDetails(array $fieldDescription): string
    {
        $details = [];

        if ($fieldDescription['NULLABLE']) {
            $details[] = self::OPTIONAL_FIELD_LABEL;
        }

        if (isset($fieldDescription['DEFAULT'])
            && $fieldDescription['DEFAULT'] !== ''
            && $fieldDescription['DEFAULT'] !== self::TIMESTAMP_DEFAULT_VALUE
        ) {
            $details[] = '[default: ' . $fieldDescription['DEFAULT'] . ']';
        }

        return $details ? ' - ' . implode(' - ', $details) : '';
    }
}
