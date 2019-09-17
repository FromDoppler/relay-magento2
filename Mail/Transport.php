<?php

namespace Combinatoria\DopplerRelay\Mail;

use Closure;
use Exception;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Mail\TransportInterface;
use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Combinatoria\DopplerRelay\Helper\Data;
use Combinatoria\DopplerRelay\Mail\Rse\Mail;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Zend\Mail\Message;
use Zend_Exception;

/**
 * Class Transport
 * @package Mageplaza\Smtp\Mail
 */
class Transport
{
    /**
     * @var int Store Id
     */
    protected $_storeId;

    /**
     * @var Mail
     */
    protected $resourceMail;

    /**
     * @var Registry $registry
     */
    protected $registry;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * Transport constructor.
     * @param Mail $resourceMail
     * @param Registry $registry
     * @param Data $helper
     * @param LoggerInterface $logger
     */
    public function __construct(
        Mail $resourceMail,
        Registry $registry,
        Data $helper,
        LoggerInterface $logger
    ) {
        $this->resourceMail = $resourceMail;
        $this->registry = $registry;
        $this->helper = $helper;
        $this->logger = $logger;
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
        $this->_storeId = $this->registry->registry('doppler_smtp_store_id');
        $message = $this->getMessage($subject);
        if ($this->resourceMail->isModuleEnable(0) && $message) {
            if ($this->helper->versionCompare('2.2.8')) {
                $message = Message::fromString($message->getRawMessage())->setEncoding('utf-8');
            }
            $message = $this->resourceMail->processMessage($message, $this->_storeId);
            $transport = $this->resourceMail->getTransport($this->_storeId);
            try {
                $transport->send($message);

                if ($this->helper->versionCompare('2.2.8')) {
                    $messageTmp = $this->getMessage($subject);
                    if ($messageTmp && is_object($messageTmp)) {
                        $body = $messageTmp->getBody();
                        if (is_object($body) && $body->isMultiPart()) {
                            $message->setBody($body->getPartContent("0"));
                        }
                    }
                }
            } catch (Exception $e) {
                throw new MailException(new Phrase($e->getMessage()), $e);
            }
        } else {
            $proceed();
        }
    }

    /**
     * @param $transport
     * @return mixed|null
     */
    protected function getMessage($transport)
    {
        if ($this->helper->versionCompare('2.2.0')) {
            return $transport->getMessage();
        }

        try {
            $reflectionClass = new ReflectionClass($transport);
            $message = $reflectionClass->getProperty('_message');
        } catch (Exception $e) {
            return null;
        }

        $message->setAccessible(true);

        return $message->getValue($transport);
    }
}
