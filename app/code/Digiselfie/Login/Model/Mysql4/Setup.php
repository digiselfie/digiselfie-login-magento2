<?php
namespace Digiselfie\Login\Model\Mysql4;


/**
 * Digiselfie Login resource setup model
 *
 * @category    Digiselfie
 * @package     Digiselfie_Login
 * @author      Digiselfie Inc.
 */

class Setup extends \Magento\Eav\Setup\EavSetup
{
    protected $_customerAttributes = array();

    public function setSocialCustomerAttributes($customerAttributes)
    {
        $this->_customerAttributes = $customerAttributes;
        return $this;
    }

   /**
     * Add our custom customer attributes
     *
     * @return \Magento\Eav\Setup\EavSetup
     */
    public function installSocialCustomerAttributes()
    {
        foreach ($this->_customerAttributes as $code => $attr) {
            $this->addAttribute('customer', $code, $attr);
        }
        return $this;
    }

    /**
     * Remove custom customer attributes
     *
     * @return \Magento\Eav\Setup\EavSetup
     */
    public function removeSocialCustomerAttributes()
    {
        foreach ($this->_customerAttributes as $code => $attr) {
            $this->removeAttribute('customer', $code);
        }
        return $this;
    }
}
