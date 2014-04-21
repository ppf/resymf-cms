<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 21.04.14
 * Time: 16:26
 */

namespace ReSymf\Bundle\CmsBundle\Services;


class ObjectConfigurator
{

    private $adminConfigurator;
    private $reader;
    private $security;
    private $entityManager;


    /**
     * @param AdminConfigurator $adminConfigurator
     * @param $reader
     * @param $security
     * @param $entityManager
     */
    public function __construct(AdminConfigurator $adminConfigurator, $reader, $security, $entityManager)
    {
        $this->adminConfigurator = $adminConfigurator;
        $this->reader = $reader; // set annotations reader
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function setInitialValuesFromAnnotations($classNameSpace, $object, $adminConfigKey)
    {
        if (!isset($classNameSpace)) {
            return false;
        }
        $formConfig = new \stdClass;

        $reflectionClass = new \ReflectionClass($classNameSpace);

        $properties = $reflectionClass->getProperties();
        $formConfig->fields = array();
        foreach ($properties as $reflectionProperty) {
            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, 'ReSymf\Bundle\CmsBundle\Annotation\Form');
            if (null !== $annotation) {
                if ($annotation->getAutoInput()) {
                    $autoInputType = $annotation->getAutoInput();
                    $newValue = '';
                    switch ($autoInputType) {
                        case 'currentUserId':
                            $user = $this->security->getToken()->getUser();
                            $newValue = $user;
                            break;
                        case 'currentDateTime' :
                            $currentDate = new \DateTime('now');
                            $newValue = $currentDate;
                            break;
                        case 'uniqueSlug' :
                            $fieldName = $reflectionProperty->getName();
                            $methodName = 'get' . $fieldName;
                            $slug = $object->$methodName();
                            $newValue = $this->generateUniqueSlug($adminConfigKey, $slug);
                            break;
                        default :
                            $newValue = 'Wrong auto input type';
                            break;
                    }
                    $fieldName = $reflectionProperty->getName();
                    $methodName = 'set' . $fieldName;
                    $object->$methodName($newValue);
                }
            }
        }
        return $object;
    }

    public function generateUniqueSlug($adminConfigKey, $slug)
    {

        $entities = $this->getEntitiesWithTheSameBaseSlug($adminConfigKey);

        $notUnique = false;
        foreach ($entities as $entity) {
            $object = $this->getObjectFromSlug($entity, $slug);
            if ($object) {
                $notUnique = true;
                break;
            }
        }
        if ($notUnique) {
            return $slug.uniqid();
        } else {
            return $slug;
        }
    }

    private function getEntitiesWithTheSameBaseSlug($adminConfigKey)
    {
        $entities = array();
        $adminConfig = $this->adminConfigurator->getAdminConfig();

        $baseEntity = $adminConfig['$adminConfigKey'];
        $baseSlug = $baseEntity['slug'];

        foreach ($adminConfig as $key => $value) {
            if (!isset($adminConfig[$key]['slug']) && $adminConfig[$key]['slug'] ==  $baseSlug) {
                $entities[] = $adminConfig[$key]['class'];
            }
        }

        return $entities;
    }

    public function getObjectFromSlug($className, $slug)
    {
        $pageObject = $this->entityManager->getRepository($className)->createQueryBuilder('p')
            ->select('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        return $pageObject;
    }
} 