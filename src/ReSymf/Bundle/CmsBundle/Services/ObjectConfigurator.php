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
    private $resymfReader;
    private $security;
    private $entityManager;


    /**
     * @param AdminConfigurator $adminConfigurator
     * @param $annotationReader
     * @param $resymfAnnotationReader
     * @param SecurityContext $security
     * @param EntityManager $entityManager
     */
    public function __construct(AdminConfigurator $adminConfigurator, $annotationReader, $resymfAnnotationReader, SecurityContext $security, EntityManager $entityManager)
    {
        $this->adminConfigurator = $adminConfigurator;
        $this->reader = $annotationReader; // set annotations reader
        $this->resymfReader = $resymfAnnotationReader; // set annotations reader
        $this->security = $security;
        $this->entityManager = $entityManager;
    }

    public function  generateMultiSelectOptions($classNameSpace, $object, $single = false)
    {
        $multiSelect = array();
        $displayMethodName = 'getName';


        if (!isset($classNameSpace)) {
            return false;
        }
        $formConfig = new \stdClass;

        $reflectionClass = new \ReflectionClass($classNameSpace);

        $properties = $reflectionClass->getProperties();
        $formConfig->fields = array();
        foreach ($properties as $reflectionProperty) {
            $fields = array('q.id');

            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, 'ReSymf\Bundle\CmsBundle\Annotation\Form');
            if (null !== $annotation) {
                if ($annotation->getType() == 'relation') {

                    $selectedIds = array();
                    // get name of current field
                    $fieldName = $reflectionProperty->getName();

                    // get relation type
                    $relationType = $annotation->getRelationType();

//                    if (
//                        ($single && ($relationType == 'oneToOne' || $relationType == 'manyToOne')) ||
//                        (!$single)
//                    ) {

                    $class = $annotation->getClass();
                    $displayField = $annotation->getDisplayField();
                    if ($displayField) {
                        $fields[] = 'q.' . $displayField;
                        $displayMethodName = 'get' . $displayField;
                    } else {
                        $fields[] = 'q.name';
                    }

                    if ($object) {
                        $methodName = 'get' . $fieldName;
                        $selectedOptionsObjects = $object->$methodName();
                    } else {
                        $selectedOptionsObjects = array();
                    }

                    if ($relationType == 'oneToOne' || $relationType == 'manyToOne') {

                        $selectedOptions = array();
                        if ($selectedOptionsObjects) {
                            $tempOption['name'] = $selectedOptionsObjects->$displayMethodName();
                            $tempOption['id'] = $selectedOptionsObjects->getId();
                            $selectedOptions[$fieldName] = $tempOption;

                            $selectedIds[] = $selectedOptionsObjects->getId();

                        }
                        $multiSelect[$fieldName]['selected'] = $selectedOptions;
                    } else {

                        if (count($selectedOptionsObjects) > 0) {

                            $selectedOptions = array();

                            foreach ($selectedOptionsObjects as $option) {
                                $tempOption = array();
                                $tempOption['name'] = $option->$displayMethodName();
                                $tempOption['id'] = $option->getId();
                                $selectedOptions[$fieldName] = $tempOption;
                            }

                            $selectedIds = $this->array_value_recursive('id', $selectedOptions[$fieldName]);

                            // get all options to select

                            $multiSelect[$fieldName]['selected'] = $selectedOptions;

                        } else {
                            $multiSelect[$fieldName]['selected'] = array();
                        }
                    }

                    if ($selectedIds) {
                        $allMultiSelectObjects = $this->entityManager
                            ->getRepository($class)
                            ->createQueryBuilder('q')
                            ->select($fields)
                            ->where('q.id NOT IN (' . implode(',', $selectedIds) . ')')
                            ->getQuery()
                            ->getResult();

                    } else {
                        $allMultiSelectObjects = $this->entityManager
                            ->getRepository($class)
                            ->createQueryBuilder('q')
                            ->select($fields)
                            ->getQuery()
                            ->getResult();
                    }

                    $multiSelect[$fieldName]['all'] = $allMultiSelectObjects;

                    if ($relationType == 'oneToOne' || $relationType == 'manyToOne') continue;
//                    }

                    // for toMany relations
                    if ($selectedOptionsObjects) {
                        $multiSelect[$fieldName]['entities'] = $selectedOptionsObjects->toArray();
                    } else {
                        $multiSelect[$fieldName]['entities'] = array();
                    }
                    $tableConfig = $this->resymfReader->readTableAnnotation($class);
                    $multiSelect[$fieldName]['table_config'] = $tableConfig;
                    $multiSelect[$fieldName]['object_type'] = $this->getAdminConfigKeyByClassNAme($class);

                }
            }
        }
        return $multiSelect;
    }

    /**
     * Get all values from specific key in a multidimensional array
     *
     * @param $key string
     * @param $arr array
     * @return null|string|array
     */
    public function array_value_recursive($key, array $arr)
    {
        $val = array();
        array_walk_recursive($arr, function ($v, $k) use ($key, &$val) {
            if ($k == $key) array_push($val, $v);
        });
        return $val;
    }

    private function getAdminConfigKeyByClassNAme($className)
    {
        $adminConfig = $this->adminConfigurator->getAdminConfig();

        foreach ($adminConfig as $key => $value) {

            if (isset($value['class']) && $value['class'] == $className) {
                return $key;
            }
        }

        return false;
    }

    public function setInitialValuesFromAnnotations($classNameSpace, $object)
    {
        $adminConfigKey = $this->getAdminConfigKeyByClassNAme($classNameSpace);

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

    public function checkUniqueValuesFromAnnotations($object, $adminConfigKey)
    {
        $adminConfig = $this->adminConfigurator->getAdminConfig();

        $baseEntity = $adminConfig[$adminConfigKey];
        $classNameSpace = $baseEntity['class'];

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
