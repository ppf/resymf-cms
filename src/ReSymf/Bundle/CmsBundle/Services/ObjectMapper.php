<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 11/12/13
 * Time: 10:14 PM
 */

namespace ReSymf\Bundle\CmsBundle\Services;

use Doctrine\ORM\EntityNotFoundException;


/**
 * Class ObjectMapper
 * @package ReSymf\Bundle\CmsBundle\Services
 *
 * @author Piotr Francuz <francuz256@gmail.com>
 */
class ObjectMapper
{

    private $adminConfigurator;

    public function __construct(AdminConfigurator $adminConfigurator)
    {
        $this->adminConfigurator = $adminConfigurator;
    }

    public function getMappedObject($objectName)
    {
        $adminConfig = $this->adminConfigurator->getAdminConfig();

        if (is_array($adminConfig[$objectName]) && isset($adminConfig[$objectName]['class'])) {
            return $adminConfig[$objectName]['class'];
        } else {
            throw new EntityNotFoundException('class not found in admin config file');
        }

//        return false;
    }
} 