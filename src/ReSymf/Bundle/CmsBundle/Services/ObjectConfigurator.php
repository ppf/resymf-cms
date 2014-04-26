<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 21.04.14
 * Time: 16:26
 */

namespace ReSymf\Bundle\CmsBundle\Services;


use Doctrine\ORM\EntityManager;
use ReSymf\Bundle\CmsBundle\Annotation\AnnotationReader;
use Symfony\Component\Security\Core\SecurityContext;

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
    public function __construct(AdminConfigurator $adminConfigurator, $reader, SecurityContext $security, EntityManager $entityManager)
    {
        $this->adminConfigurator = $adminConfigurator;
        $this->reader = $reader; // set annotations reader
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function  generateMultiSelectOptions($classNameSpace, $object)
    {
        $multiSelect = array();
        $fields = array('q.id');

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
                if ($annotation->getType() == 'relation') {
                    $fieldName = $reflectionProperty->getName();
                    $relationType = $annotation->getRelationType();
                    $class = $annotation->getClass();
                    $displayField = $annotation->getDisplayField();
                    if($displayField) {
                        $fields[] = 'q.'.$displayField;
                    } else {
                        $fields[] = 'q.name';
                    }

                    // get all options to select
                    $allMultiSelectObjects = $this->entityManager->getRepository($class)->createQueryBuilder('q')->select($fields)->getQuery()->getResult();

                    //get selected options
                    $methodName = 'get' . $fieldName;
                    $selectedOptions = $object->$methodName();
                    print_r($selectedOptions);
                    die();
                    $multiSelect[$fieldName]['all'] = $allMultiSelectObjects;
                    $multiSelect[$fieldName]['selected'] = $selectedOptions;

                    switch($relationType){
                        case 'manyToMany':
                            break;
                        case 'oneToMany':
                            break;
                        case 'oneToOne':
                            break;
                    }
                }
            }
        }
        return $multiSelect;
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
            return $slug . uniqid();
        } else {
            return $slug;
        }
    }

    private function getEntitiesWithTheSameBaseSlug($adminConfigKey)
    {
        $entities = array();
        $adminConfig = $this->adminConfigurator->getAdminConfig();

        $baseEntity = $adminConfig[$adminConfigKey];
        $baseSlug = $baseEntity['slug'];

        foreach ($adminConfig as $key => $value) {

            if (isset($value['slug']) && $value['slug'] == $baseSlug) {
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

    public function checkUniqueValuesFromAnnotations($classNameSpace, $object, $adminConfigKey)
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
                        case 'uniqueSlug' :
                            $fieldName = $reflectionProperty->getName();
                            $getMethodName = 'get' . $fieldName;
                            $slug = $object->$getMethodName();
                            $newValue = $this->generateUniqueSlug($adminConfigKey, $slug);
                            $setMethodName = 'set' . $fieldName;
                            $object->$setMethodName($newValue);
                            break;
                        default :
                            // do nothing
                            break;
                    }
                }
            }
        }
        return $object;
    }
} 