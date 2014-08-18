<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 24.11.13
 * Time: 01:07
 */

namespace ReSymf\Bundle\CmsBundle\Annotation;

/**
 * Class Form
 * @package ReSymf\Bundle\CmsBundle\Annotation
 *
 * @Annotation
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Form
{
    private $type = 'text';
    private $required = true;
    private $length;
    private $display = true;
    private $readOnly = false;
    private $editLabel = 'Edit Object';
    private $createLabel = 'Create Object';
    private $showLabel = 'Show Object';
    private $withoutLink = false;
    private $fieldLabel;
    private $dateFormat ="DD-MM-YYYY hh:mm:ss";
    private $relationType;
    private $autoInput = false;
    private $class;
    private $displayField;
    private $multiSelectInEditForm = false;
    private $targetEntityField;
    /**
     * save all @Form annotations from Entity to this Form object
     *
     * @param $options
     * @throws \InvalidArgumentException
     */
    public function __construct($options)
    {
        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $key));
            }
            $this->$key = $value;
        }
    }

    /**
     * @return mixed
     */
    public function getTargetEntityField()
    {
        return $this->targetEntityField;
    }

    /**
     * @param mixed $targetEntityField
     */
    public function setTargetEntityField($targetEntityField)
    {
        $this->targetEntityField = $targetEntityField;
    }

    /**
     * @return string
     */
    public function getShowLabel()
    {
        return $this->showLabel;
    }

    /**
     * @param string $showLabel
     */
    public function setShowLabel($showLabel)
    {
        $this->showLabel = $showLabel;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * @param string $dateFormat
     */
    public function setDateFormat($dateFormat)
    {
        $this->dateFormat = $dateFormat;
    }

    /**
     * @return mixed
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param mixed $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }
 
    /**
     * @return mixed
     */
    public function getWithoutLink()
    {
        return $this->withoutLink;
    }

    /**
     * @param mixed $class
     */
    public function setWithoutLink($class)
    {
        $this->withoutLink = $class;
    }

    // can set value as:
    // 1. 'currentTime' - set current time as value when created
    // 2. 'currentUser' - set current user id as value when created

    /**
     * @return mixed
     */
    public function getRelationType()
    {
        return $this->relationType;
    }

    /**
     * @param mixed $relationType
     */
    public function setRelationType($relationType)
    {
        $this->relationType = $relationType;
    }

    /**
     * @return mixed
     */
    public function getDisplay()
    {
        return $this->display;
    }

    /**
     * @return mixed
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return mixed
     */
    public function getReadOnly()
    {
        return $this->readOnly;
    }

    /**
     * @return mixed
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return mixed
     */
    public function getEditLabel()
    {
        return $this->editLabel;
    }

    /**
     * @return mixed
     */
    public function getCreateLabel()
    {
        return $this->createLabel;
    }

    /**
     * @return mixed
     */
    public function getFieldLabel()
    {
        return $this->fieldLabel;
    }

    /**
     * @return boolean
     */
    public function getAutoInput()
    {
        return $this->autoInput;
    }

    /**
     * @return mixed
     */
    public function getDisplayField()
    {
        return $this->displayField;
    }

    /**
     * @param mixed $displayField
     */
    public function setDisplayField($displayField)
    {
        $this->displayField = $displayField;
    }

    /**
     * @return boolean
     */
    public function getMultiSelectInEditForm()
    {
        return $this->multiSelectInEditForm;
    }

    /**
     * @param boolean $multiSelectInEditForm
     */
    public function setMultiSelectInEditForm($multiSelectInEditForm)
    {
        $this->multiSelectInEditForm = $multiSelectInEditForm;
    }

} 
