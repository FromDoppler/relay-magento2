<?php
namespace Combinatoria\DopplerRelay\Mail\Rse;

use Combinatoria\DopplerRelay\Helper\Data;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp;
use Zend\Mail\Transport\SmtpOptions;
use Zend_Exception;
use Zend_Mail_Transport_Smtp;

/**
 * Class Mail
 * @package Combinatoria\DopplerRelay\Application\Rse
 */
class Mail
{
    /**
     * @var Data
     */
    protected $smtpHelper;

    /**
     * @var array Is module enable by store
     */
    protected $_moduleEnable = [];

    /**
     * @var array is developer mode
     */
    protected $_developerMode = [];

    /**
     * @var string message body email
     */
    protected $_message;

    /**
     * @var array option by storeid
     */
    protected $_smtpOptions = [];

    /**
     * @var array
     */
    protected $_returnPath = [];

    /**
     * @var Zend_Mail_Transport_Smtp
     */
    protected $_transport;

    /**
     * @var array
     */
    protected $_fromByStore = [];

    /**
     * Mail constructor.
     *
     * @param Data $helper
     * @param null $options
     */
    public function __construct(Data $helper)
    {
        $this->smtpHelper = $helper;
    }

    /**
     * @param $storeId
     * @param array $options
     *
     * @return $this
     */
    public function setSmtpOptions($storeId, $options = [])
    {
        if (isset($options['return_path'])) {
            $this->_returnPath[$storeId] = $options['return_path'];
            unset($options['return_path']);
        }

        if (isset($options['force_sent']) && $options['force_sent']) {
            $this->_moduleEnable[$storeId] = true;
            unset($options['force_sent']);
        }

        if (count($options)) {
            $this->_smtpOptions[$storeId] = $options;
        }

        return $this;
    }

    /**
     * @param $storeId
     *
     * @return Zend_Mail_Transport_Smtp | Smtp
     * @throws Zend_Exception
     */
    public function getTransport($storeId)
    {
        if ($this->_transport === null) {
            if (!isset($this->_smtpOptions[$storeId])) {
                $configData = $this->smtpHelper->getSmtpConfig();
                $options = [
                    'host' => isset($configData['host']) ? $configData['host'] : '',
                    'port' => isset($configData['port']) ? $configData['port'] : ''
                ];

                if (isset($configData['authentication']) && $configData['authentication'] !== "") {
                    $options += [
                        'auth'     => $configData['authentication'],
                        'username' => isset($configData['username']) ? $configData['username'] : '',
                        'password' => $configData['password']
                    ];
                }

                $this->_smtpOptions[$storeId] = $options;
            }

            if (!isset($this->_smtpOptions[$storeId]['host']) || !$this->_smtpOptions[$storeId]['host']) {
                throw new Zend_Exception(__('A host is necessary for smtp transport, but none was given'));
            }

            if ($this->smtpHelper->versionCompare('2.2.8')) {
                $options = $this->_smtpOptions[$storeId];
                if (isset($options['auth'])) {
                    $options['connection_class'] = $options['auth'];
                    $options['connection_config'] = [
                        'username' => $options['username'],
                        'password' => $options['password']
                    ];
                    unset($options['auth'], $options['username'], $options['password']);
                }
                if (isset($options['ssl'])) {
                    $options['connection_config']['ssl'] = $options['ssl'];
                    unset($options['ssl']);
                }
                unset($options['type']);

                $options = new SmtpOptions($options);

                $this->_transport = new Smtp($options);
            } else {
                $this->_transport = new Zend_Mail_Transport_Smtp(
                    $this->_smtpOptions[$storeId]['host'],
                    $this->_smtpOptions[$storeId]
                );
            }
        }

        return $this->_transport;
    }

    /**
     * @param $message
     * @param $storeId
     *
     * @return mixed
     */
    public function processMessage($message, $storeId)
    {
        if (!empty($this->_fromByStore) &&
            ((is_array($message->getHeaders()) && !array_key_exists("From", $message->getHeaders())) ||
                ($message instanceof Message && !$message->getFrom()->count()))
        ) {
            $message->setFrom($this->_fromByStore['email'], $this->_fromByStore['name']);
        }

        return $message;
    }

    /**
     * @param $email
     * @param $name
     *
     * @return $this
     */
    public function setFromByStore($email, $name)
    {
        $this->_fromByStore = [
            'email' => $email,
            'name'  => $name
        ];

        return $this;
    }

    /**
     * @param $storeId
     *
     * @return bool
     */
    public function isModuleEnable($storeId)
    {
        if (!isset($this->_moduleEnable[$storeId])) {
            $this->_moduleEnable[$storeId] = $this->smtpHelper->isEnabled($storeId);
        }

        return $this->_moduleEnable[$storeId];
    }
}
