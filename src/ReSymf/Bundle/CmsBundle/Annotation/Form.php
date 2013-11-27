<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 24.11.13
 * Time: 01:07
 */

namespace ReSymf\Bundle\CmsBundle\Annotation;

/**
 * @Annotation
 */
class Form
{
    private $type;
    private $required;
    private $length;
    private $display;
    private $readOnly;

    // read all @Form annotations from Entity
    public function __construct($options) {

        foreach ($options as $key => $value) {
            if (!property_exists($this, $key)) {
                throw new \InvalidArgumentException(sprintf('Property "%s" does not exist', $key));
            }

            $this->$key = $value;
        }
    }
} 