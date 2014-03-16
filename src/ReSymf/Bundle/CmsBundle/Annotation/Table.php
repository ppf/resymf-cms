<?php

namespace ReSymf\Bundle\CmsBundle\Annotation;

/**
 * Class Table
 * @package ReSymf\Bundle\CmsBundle\Annotation
 *
 * @Annotation
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Table
{

    private $display = true;
    private $label;
    private $hideOnDevice = false;
    private $sorting = true;
    private $paging = true;
    private $pageSize = 10;
    private $filtering = true;
    private $format = 'text';
    private $dateFormat = 'Y-m-d';
    private $length = 50;
    private $relation = false;
    private $class;

    /**
     * save all @Table annotations from Entity to this Table object
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
    public function getRelation()
    {
        return $this->relation;
    }

    /**
     * @param mixed $relationType
     */
    public function setRelation($relationType)
    {
        $this->relation = $relationType;
    }

    public function getDisplay()
    {
        return $this->display;
    }

    public function getSorting()
    {
        return $this->sorting;
    }

    public function getPaging()
    {
        return $this->paging;
    }

    public function getPageSize()
    {
        return $this->pageSize;
    }

    public function getFiltering()
    {
        return $this->filtering;
    }

    public function getHideOnDevice()
    {
        return $this->hideOnDevice;
    }

    /**
     * @return mixed
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @return string
     */
    public function getDateFormat()
    {
        return $this->dateFormat;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }


}
