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
        $displayMethodName = 'getName';

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

                    // get name of current field
                    $fieldName = $reflectionProperty->getName();

                    // get relation type
                    $relationType = $annotation->getRelationType();
                    $class = $annotation->getClass();
                    $displayField = $annotation->getDisplayField();
                    if ($displayField) {
                        $fields[] = 'q.' . $displayField;
                        $displayMethodName = 'get' . $displayField;
                    } else {
                        $fields[] = 'q.name';
                    }

                    $methodName = 'get' . $fieldName;
                    $selectedOptionsObjects = $object->$methodName();

                    if (count($selectedOptionsObjects) > 0) {
//print_r()
                        $selectedOptions = array();

                        foreach ($selectedOptionsObjects as $option) {
                            $tempOption = array();
                            $tempOption['name'] = $option->$displayMethodName();
                            $tempOption['id'] = $option->getId();
                            $selectedOptions[$fieldName] = $tempOption;
                        }

                        $selectedIds = $this->array_value_recursive('id', $selectedOptions[$fieldName]);

                        // get all options to select
                        $allMultiSelectObjects = $this->entityManager
                            ->getRepository($class)
                            ->createQueryBuilder('q')
                            ->select($fields)
                            ->where('q.id NOT IN (' . implode(',', $selectedIds) . ')')
                            ->getQuery()
                            ->getResult();
                        $multiSelect[$fieldName]['selected'] = $selectedOptions;
                    } else {
                        $multiSelect[$fieldName]['selected'] = array();

                        $allMultiSelectObjects = $this->entityManager
                            ->getRepository($class)
                            ->createQueryBuilder('q')
                            ->select($fields)
                            ->getQuery()
                            ->getResult();
                    }
//                    print_r($allMultiSelectObjects);
//                    die();
//

                    $multiSelect[$fieldName]['all'] = $allMultiSelectObjects;


                    switch ($relationType) {
                        case 'manyToMany':
                            // can check many
                            break;
                        case 'oneToMany':
                            // can check many
                            break;
                        case 'oneToOne':
                            // can check one
                            break;
                    }
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