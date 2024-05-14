<?php
/**
 * This block serves as a skeleton class to change the scope of a block definition. 
 * The template attribute on the block will now default to this module rather than the 
 * core module on the original block definition.
 */
namespace Altitude\SX\Block\Checkout\Cart;

class Grid
{
    public function beforeToHtml(\Magento\Checkout\Block\Cart\Grid $subject)
    {
        //if ($template === 'Magento_Checkout::cart/form.phtml') {
            $subject->setTemplate('Altitude_SX::cart/form.phtml');
        //}
    }
}
