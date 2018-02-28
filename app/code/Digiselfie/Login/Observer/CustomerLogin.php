<?php

namespace Digiselfie\Login\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Digiselfie\Login\Helper\Data;

class CustomerLogin implements ObserverInterface
{

  /**
   * @var \Digiselfie\Login\Helper\Data
   */
  protected $digiselfieData;

  public function __construct(
    Data $digiselfieData
  ) {
    $this->digiselfieData = $digiselfieData;
  }

  public function execute(Observer $observer) {
    $customer = $observer->getEvent()->getCustomer();

    $digiselfieLoginId = $this->digiselfieData->getCookie('digiselfie_login_id') ?: null;

    if (!empty($digiselfieLoginId)) {
        $digiselfieCustomerId = $this->digiselfieData->getCookie('ds_customer_id') ?: null;

        if ($customer->getId() == $digiselfieCustomerId) {
            $customer->setDigiselfieLoginId($digiselfieLoginId)->save();
        }
    }
    $this->digiselfieData->deleteCookie('ds_customer_id');
    $this->digiselfieData->deleteCookie('digiselfie_login_id');
  }
}