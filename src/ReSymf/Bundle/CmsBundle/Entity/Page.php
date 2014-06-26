<?php

namespace ReSymf\Bundle\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\Expr\Base;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Page
 * @package ReSymf\Bundle\CmsBundle\Entity
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Page", createLabel="Create Page", showLabel="Show Page")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Page extends BasePage
{
    /**
     * @var integer
     *
     * @Form(display=false)
     * @Table(display=false)
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var Terms
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="multiselect", class="ReSymf\Bundle\CmsBundle\Entity\Category", fieldLabel="Categories", targetEntityField="category")
     *
     * @ORM\ManyToMany(targetEntity="Category")
     */
    private $categories;

    function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    /**
     * @return Terms
     */
    public function getCategories()
    {
        return $this->categories;
    }

    /**
     * @param Terms $categories
     */
    public function setCategories($categories)
    {
        $this->categories = $categories;
    }

    public function addCategory($category)
    {
        if (!$this->categories->contains($category)) {
            $this->categories->add($category);
        }

    }

}
