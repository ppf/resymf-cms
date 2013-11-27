<?php

namespace ReSymf\Bundle\CmsBundle\Annotation;

/**
 * @Annotation
 */
class Table
{

    private $display = true;
    private $hideOnDevice = false;
    private $sorting = true;
    private $paging = true;
    private $pageSize = 10;
    private $filtering = false;

    // read all @Table annotations from Entity
    public function __construct($options) {

        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $key));
            }

            $this->$key = $value;
        }
    }

    public function getDisplay() {
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
}
