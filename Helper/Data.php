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

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\ConfigResource\ConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ResourceConnection;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\App\ProductMetadataInterface;

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
     * @param $ver
     * @param string $operator
     *
     * @return mixed
     */
    public function versionCompare($ver, $operator = '>=')
    {
        $productMetadata = $this->objectManager->get(ProductMetadataInterface::class);
        $version = $productMetadata->getVersion(); //will return the magento version
        return version_compare($version, $ver, $operator);
    }

    /**
     * @return mixed
     */
    public function getSmtpConfig()
    {
        $data['host'] = $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_HOST);
        $data['port'] = $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_PORT);
        $data['username'] = $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_USERNAME);
        $data['password'] = $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_PASSWORD);
        $data['authentication'] = $this::CONFIG_DOPPLER_RELAY_AUTH;

        return $data;
    }

    public function isEnabled($storeId){
        return $this->getConfigValue($this::CONFIG_DOPPLER_RELAY_ENABLED);
    }
}