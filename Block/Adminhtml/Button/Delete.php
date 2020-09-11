<?php

namespace Snowdog\Menu\Block\Adminhtml\Button;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

class Delete extends AbstractButton implements ButtonProviderInterface
{
    public function getButtonData(): array
    {
        return [
            'label' => __('Delete'),
            'on_click' => sprintf("location.href = '%s';", $this->getUrl('*/*/delete', ['id' => $this->getMenuId()])),
            'class' => 'delete',
            'sort_order' => 20
        ];
    }
}
