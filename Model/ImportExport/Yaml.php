<?php

namespace Snowdog\Menu\Model\ImportExport;

use Magento\Framework\Exception\ValidatorException;
use Symfony\Component\Yaml\Yaml as YamlComponent;

class Yaml
{
    const INLINE_LEVEL = 10;
    const INDENTATION = 2;

    const EXTENSIONS = ['yaml', 'yml'];

    /**
     * @param string $data
     * @throws ValidatorException
     * @return array
     */
    public function parse($data)
    {
        try {
            return YamlComponent::parse($data);
        } catch (\Exception $exception) {
            throw new ValidatorException(__('Invalid YAML format: %1', $exception->getMessage()));
        }
    }

    /**
     * @return string
     */
    public function dump(array $data)
    {
        return YamlComponent::dump($data, self::INLINE_LEVEL, self::INDENTATION);
    }
}
