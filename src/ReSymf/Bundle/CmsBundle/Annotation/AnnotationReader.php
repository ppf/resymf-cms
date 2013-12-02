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
        $tableConfig = new \stdClass;

        $reflectionClass = new \ReflectionClass($classNameSpace);
        $classAnnotations = $this->reader->getClassAnnotation($reflectionClass, 'ReSymf\Bundle\CmsBundle\Annotation\Table');
        $tableConfig->sorting = $classAnnotations->getSorting();
        $tableConfig->paging = $classAnnotations->getPaging();
        $tableConfig->pageSize = $classAnnotations->getPageSize();
        $tableConfig->filtering = $classAnnotations->getFiltering();

        $properties = $reflectionClass->getProperties();
        $tableConfig->fields = array();

        foreach ($properties as $reflectionProperty) {

            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, 'ReSymf\Bundle\CmsBundle\Annotation\Table');
            if (null !== $annotation) {
                if($annotation->getDisplay()) {
                    $hideOnDevice = $annotation->getHideOnDevice();
                    $label = $annotation->getLabel();
                    $tableConfig->fields[] = array('name' =>$reflectionProperty->getName(), 'hideOnDevice' => $hideOnDevice, 'label' => $label);
                }
            } else {
                $tableConfig->fields[] = array('name' =>$reflectionProperty->getName());
            }
        }

        return $tableConfig;
    }

    public function readFormAnnotation($classNameSpace)
    {
        if (!isset($classNameSpace)) {
            return false;
        }
        $formConfig = new \stdClass;

        $reflectionClass = new \ReflectionClass($classNameSpace);
        $classAnnotations = $this->reader->getClassAnnotation($reflectionClass, 'ReSymf\Bundle\CmsBundle\Annotation\Form');

        $formConfig->editLabel = $classAnnotations->getEditLabel();

        $properties = $reflectionClass->getProperties();
        $formConfig->fields = array();
        foreach ($properties as $reflectionProperty) {
            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, 'ReSymf\Bundle\CmsBundle\Annotation\Form');
            if (null !== $annotation) {
                if($annotation->getDisplay()) {

                }
            } else {

            }
        }

        //        echo '<pre>';
//        print_r($configTableObject);
//        echo '<pre>';
//        die();
        return $formConfig;
    }
}
