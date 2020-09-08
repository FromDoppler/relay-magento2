<?php

namespace Combinatoria\DopplerRelay\Mail;

use Closure;
use Exception;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Phrase;
use Combinatoria\DopplerRelay\Helper\Data;
use Zend\Mail\Message;
use Zend_Exception;

/**
 * Class Transport
 * @package Combinatoria\DopplerRelay\Mail
 */
class Transport
{
    /**
     * @var int Store Id
     */
    protected $_storeId;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * Transport constructor.
     * @param Data $helper
     */
    public function __construct(
        Data $helper
    ) {
        $this->helper = $helper;
    }

    /**
     * @param TransportInterface $subject
     * @param Closure $proceed
     * @throws MailException
     * @throws Zend_Exception
     */
    public function aroundSendMessage(
        TransportInterface $subject,
        Closure $proceed
    ) {
        $message = $subject->getMessage();
        if ($this->helper->isEnabled() && $message) {
            $transport = $this->helper->getTransport($this->_storeId);
            try {
                $transport->send($message);
            } catch (Exception $e) {
                throw new MailException(new Phrase($e->getMessage()), $e);
            }
        } else {
            $proceed();
        }
    }
}
