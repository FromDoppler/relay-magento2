<?php
/**
 * Doppler Relay Extension
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Combinatoria
 * @package     Combinatoria_DopplerRelay
 */
namespace Combinatoria\DopplerRelay\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;
use Zend\Mail\Message;
use Zend\Mail\Transport\Smtp;
use Zend\Mail\Transport\SmtpOptions;
use Zend_Exception;
use Zend_Mail_Transport_Smtp;

/**
 * Class Data
 * @package Combinatoria\DopplerRelay\Helper
 */
class Data extends AbstractHelper{
    /**
     * @var ObjectManagerInterface $objectManager
     */
    protected $objectManager;

    protected $_scopeConfig;
    private $_configInterface;

    private $storeManager;

    protected $_message;

    protected $_options = [];

    protected $_transport;

    const CONFIG_DOPPLER_RELAY_ENABLED   = 'doppler_relay_config/config/enabled';
    const CONFIG_DOPPLER_RELAY_USERNAME = 'doppler_relay_config/config/username';
    const CONFIG_DOPPLER_RELAY_PASSWORD = 'doppler_relay_config/config/password';
    const CONFIG_DOPPLER_RELAY_HOST     = 'doppler_relay_config/config/host';
    const CONFIG_DOPPLER_RELAY_PORT     = 'doppler_relay_config/config/port';
    const CONFIG_DOPPLER_RELAY_AUTH     = 'Login';
    /**
     * @param Context $context
     * @param ResourceConnection $resource
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        Context $context,
        ResourceConnection $resource,
        ScopeConfigInterface $scopeConfig,
        ConfigInterface $configInterface,
        ObjectManagerInterface $objectManager,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);

        $this->_scopeConfig = $scopeConfig;
        $this->_configInterface = $configInterface;
        $this->objectManager = $objectManager;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns config value
     *
     * @param string $path
     * @return mixed
     */
    public function getConfigValue($path)
    {
        return $this->_scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, Store::DEFAULT_STORE_ID);
    }

    /**
     * @return mixed
     */
    public function getConfig()
    {
        $data['host'] = $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_HOST);
        $data['port'] = $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_PORT);
        $data['username'] = $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_USERNAME);
        $data['password'] = $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_PASSWORD);
        $data['authentication'] = $this::CONFIG_DOPPLER_RELAY_AUTH;

        return $data;
    }

    public function isEnabled(){
        return $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_ENABLED);
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
            if (!isset($this->_options[$storeId])) {
                $configData = $this->getConfig();
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

                $this->_options[$storeId] = $options;
            }

            if (!isset($this->_options[$storeId]['host']) || !$this->_options[$storeId]['host']) {
                throw new Zend_Exception(__('Invalid host'));
            }

            if ($this->versionCompare('2.2.8')) {
                $options = $this->_options[$storeId];
                if (isset($options['auth'])) {
                    $options['connection_class']  = $options['auth'];
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
                    $this->_options[$storeId]['host'],
                    $this->_options[$storeId]
                );
            }
        }

        return $this->_transport;
    }

    public function versionCompare($ver, $operator = '>=')
    {
        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        $version = $productMetadata->getVersion(); //will return the magento version

        return version_compare($version, $ver, $operator);
    }
}