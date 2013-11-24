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

//        if (!isset($entity)) {
//            return false;
//        }
        $configTableObject = new \stdClass;

        $reflectionClass = new \ReflectionClass($classNameSpace);
        $classAnnotations = $this->reader->getClassAnnotation($reflectionClass, 'ReSymf\Bundle\CmsBundle\Annotation\TableAnnotation');
        $configTableObject->display = $classAnnotations->getDisplay();
        $properties = $reflectionClass->getProperties();


        foreach ($properties as $reflectionProperty) {

            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, 'ReSymf\Bundle\CmsBundle\Annotation\TableAnnotation');
            if (null !== $annotation) {
                $hideOnDevice = $annotation->getHideOnDevice();
                if ($hideOnDevice) {
                    $devicesArray = explode(',', $hideOnDevice);
                    $configTableObject->$hideOnDevice = array('propertyName', $devicesArray);
                }
            }
        }

        return $configTableObject;
    }

}
