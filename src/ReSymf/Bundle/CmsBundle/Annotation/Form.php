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
 * @author Piotr Francuz <francuz256@gmail.com>
 */
class Form
{
    private $type = 'text';
    private $required = true;
    private $length;
    private $display = true;
    private $readOnly;
    private $editLabel = 'Edit Object';
    private $createLabel = 'Create Object';
    private $fieldLabel;

    // read all @Form annotations from Entity
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



} 