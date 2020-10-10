<?php

namespace Combinatoria\DopplerRelay\Block\Adminhtml;

/**
 * @api
 * @since 100.0.2
 */
class Paragraph extends \Magento\Backend\Block\AbstractBlock implements
    \Magento\Framework\Data\Form\Element\Renderer\RendererInterface
{
    /**
     * Render element html
     *
     * @param \Magento\Framework\Data\Form\Element\AbstractElement $element
     * @return string
     */
    public function render(\Magento\Framework\Data\Form\Element\AbstractElement $element)
    {
        return sprintf(
            '<tr class="system-fieldset-sub-head" id="row_%s"><td colspan="5"><p id="%s">%s</p></td></tr>',
            $element->getHtmlId(),
            $element->getHtmlId(),
            $element->getLabel()
        );
    }
}
