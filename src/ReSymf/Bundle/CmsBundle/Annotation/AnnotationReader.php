<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace ReSymf\Bundle\CmsBundle\Annotation;


/**
 * Class AnnotationReader
 * @package ReSymf\Bundle\CmsBundle\Annotation
 *
 * @author Piotr Francuz <francuz256@gmail.com>
 */
class AnnotationReader
{

    private $reader;

    /**
     * @param $reader
     */
    public function __construct($reader)
    {
        $this->reader = $reader; // set annotations reader
    }

    public function readTableAnnotation($classNameSpace)
    {

        if (!isset($classNameSpace)) {
            return false;
        }
        $configTableObject = new \stdClass;

        $reflectionClass = new \ReflectionClass($classNameSpace);
        $classAnnotations = $this->reader->getClassAnnotation($reflectionClass, 'ReSymf\Bundle\CmsBundle\Annotation\Table');
        $configTableObject->sorting = $classAnnotations->getSorting();
        $configTableObject->paging = $classAnnotations->getPaging();
        $configTableObject->pageSize = $classAnnotations->getPageSize();
        $configTableObject->filtering = $classAnnotations->getFiltering();

        $properties = $reflectionClass->getProperties();
        $configTableObject->fields = array();

        foreach ($properties as $reflectionProperty) {

            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, 'ReSymf\Bundle\CmsBundle\Annotation\Table');
            if (null !== $annotation) {
                if($annotation->getDisplay()) {

                    $hideOnDevice = $annotation->getHideOnDevice();
//                    $devicesArray = explode(',', $hideOnDevice);
//                    $configTableObject->hideOnDevice[$reflectionProperty->getName()] = $devicesArray;
                    $configTableObject->fields[] = array('name' =>$reflectionProperty->getName(), 'hideOnDevice' => $hideOnDevice);
                }
            } else {
                $configTableObject->fields[] = array('name' =>$reflectionProperty->getName());
            }
        }
//        echo '<pre>';
//        print_r($configTableObject);
//        echo '<pre>';
//        die();
        return $configTableObject;
    }

}
