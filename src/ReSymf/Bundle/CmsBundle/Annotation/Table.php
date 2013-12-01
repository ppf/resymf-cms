<?php

namespace ReSymf\Bundle\CmsBundle\Annotation;

/**
 * Class Table
 * @package ReSymf\Bundle\CmsBundle\Annotation
 *
 * @Annotation
 *
 * @author Piotr Francuz <francuz256@gmail.com>
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

    // read all @Table annotations from Entity
    public function __construct($options)
    {

        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $key));
            }

            $this->$key = $value;
        }
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

}
