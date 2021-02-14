<?php

namespace Spro\AplazoPayment\Observer;

use Magento\Framework\Event\ObserverInterface;

class Cancel implements ObserverInterface
{
    private $checkoutSession;
    private $checkoutSessionn;

    public function __construct(
        \Magento\Checkout\Model\Session\Proxy $checkoutSession,
        \Magento\Checkout\Model\Session $session
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->checkoutSessionn= $session;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $lastRealOrder = $this->checkoutSession->getLastRealOrder();
        if ($lastRealOrder->getPayment()) {

            if ($lastRealOrder->getData('state') === 'new' && $lastRealOrder->getData('status') === 'pending' && $lastRealOrder->getPayment()->getMethod()=='aplazo_payment') {
                $comment='Your payment has been declined. Please try again.';
                $lastRealOrder->registerCancellation($comment)->save();
                $this->checkoutSession->restoreQuote();
            }
        }
        return true;
    }
}