<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 11/4/13
 * Time: 10:50 AM
 */

namespace ReSymf\Bundle\CmsBundle\Services;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;


/**
 * Class AdminConfigurator
 * @package ReSymf\Bundle\CmsBundle\Services
 *
 * @author Piotr Francuz <francuz256@gmail.com>
 */
class AdminConfigurator
{

    private $adminConfig;
    private $siteConfig;

    public function __construct()
    {
        $yaml = new Parser();
        try {
            $value = $yaml->parse(file_get_contents(__DIR__ . '/../Resources/config/admin/admin.yml'));
        } catch (ParseException $e) {
            throw new ParseException("Unable to parse the YAML string: %s", $e->getMessage());
        }

        if (!$value['admin'] && !is_array($value['admin'])) {
            throw new ParseException('unable to find admin configuration');
        } else {
            $this->adminConfig = $this->setAdminDefaultValues($value['admin']);
        }

        if (!$value['site_config'] && !is_array($value['site_config'])) {
            throw new ParseException('unable to find site_config configuration');
        } else {
            $this->siteConfig = $value['site_config'];
        }
    }

    public function readConfig()
    {

    }

    /**
     * set default values to admin configuration
     *
     * @param $adminConfig
     * @return mixed
     * @throws \Symfony\Component\Config\Definition\Exception\Exception
     */
    public function setAdminDefaultValues($adminConfig)
    {
        foreach ($adminConfig as $key => $value) {
            if (!isset($adminConfig[$key]['type'])) {
                $adminConfig[$key]['type'] = 'crud';
            }
            if ($adminConfig[$key]['type'] == 'crud') {

                if (!isset($adminConfig[$key]['class'])) {
                    throw new Exception('No class set in ' . $key . ' area in admin.yml file');
                }

                if (!isset($adminConfig[$key]['remote'])) {
                    $adminConfig[$key]['remote'] = false;
                }

                if (!isset($adminConfig[$key]['object_prefix'])) {
                    $adminConfig[$key]['object_prefix'] = 'object';
                }
            }

            if ($adminConfig[$key]['type'] == 'custom_page' && !isset($adminConfig[$key]['template'])) {
                throw new Exception('No template set in ' . $key . ' area in admin.yml file');
            }

            if (!isset($adminConfig[$key]['icon'])) {
                $adminConfig[$key]['icon'] = 'file';
            }

            if (!isset($adminConfig[$key]['label'])) {
                $adminConfig[$key]['label'] = 'No Label';
            }

            if(!isset($adminConfig[$key]['role']) || empty($adminConfig[$key]['role'])) {
                $adminConfig[$key]['role'] = 'ROLE_ADMIN';
            }
        }
        return $adminConfig;
    }

    public function getAdminConfig()
    {
        return $this->adminConfig;
    }

    public function getSiteConfig()
    {
        return $this->siteConfig;
    }

    public function checkItemIfExistInMenu()
    {
        // TODO: check if item exist return mapping full class namespace
    }

} 