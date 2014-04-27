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
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class AnnotationReader
{

    private $reader;


    /**
     * @param $reader
     * @param $objectConfigurator
     */
    public function __construct($reader)
    {
        $this->reader = $reader; // set annotations reader
    }

    /**
     * read @Table annotation from Entity
     *
     * @param $classNameSpace
     * @return bool|\stdClass
     */
    public function readTableAnnotation($classNameSpace)
    {
        if (!isset($classNameSpace)) {
            return false;
        }
        $tableConfig = new \stdClass;

        $reflectionClass = new \ReflectionClass($classNameSpace);
        $classAnnotations = $this->reader->getClassAnnotation($reflectionClass, 'ReSymf\Bundle\CmsBundle\Annotation\Table');

//        print_r($classAnnotations);
//        die();

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
                    $format = $annotation->getFormat();
                    $dateFormat = $annotation->getDateFormat();
                    $tableConfig->fields[] = array('name' =>$reflectionProperty->getName(), 'hideOnDevice' => $hideOnDevice, 'label' => $label, 'format' => $format, 'dateFormat' => $dateFormat);
                }
            } else {
                $tableConfig->fields[] = array('name' =>$reflectionProperty->getName());
            }
        }

        return $tableConfig;
    }

    /**
     * read @Form annotation from entity
     *
     * @param $classNameSpace
     * @return bool|\stdClass
     */
    public function readFormAnnotation($classNameSpace)
    {
        if (!isset($classNameSpace)) {
            return false;
        }
        $formConfig = new \stdClass;

        $reflectionClass = new \ReflectionClass($classNameSpace);
        $classAnnotations = $this->reader->getClassAnnotation($reflectionClass, 'ReSymf\Bundle\CmsBundle\Annotation\Form');

        $formConfig->editLabel = $classAnnotations->getEditLabel();
        $formConfig->createLabel = $classAnnotations->getCreateLabel();

        $properties = $reflectionClass->getProperties();
        $formConfig->fields = array();
        foreach ($properties as $reflectionProperty) {
            $annotation = $this->reader->getPropertyAnnotation($reflectionProperty, 'ReSymf\Bundle\CmsBundle\Annotation\Form');
            if (null !== $annotation) {
                if($annotation->getDisplay()) {
                    $type = $annotation->getType();
                    $relationType = $annotation->getRelationType();
                    $fieldLabel = $annotation->getFieldLabel();
                    $formConfig->fields[] = array('name' =>$reflectionProperty->getName(), 'type' => $type, 'fieldLabel' => $fieldLabel, 'relationType' => $relationType);
                }
            } else {
                $formConfig->fields[] = array('name' =>$reflectionProperty->getName());
            }
        }

        return $formConfig;
    }
}
