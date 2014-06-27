<?php

namespace ReSymf\Bundle\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;

/**
 * Category
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Category", createLabel="Create Theme", showLabel="Show Theme")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Theme
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
    private $id;
    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Name")
     * @Form(fieldLabel="Name",type="text",required=true)
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var Task
     *
     * @Form(display=false)
     * @Table(display=false)
     *
     * @ORM\OneToMany(targetEntity="User", mappedBy="theme")
     */
    private $users;

    function __construct()
    {
        $this->users = new ArrayCollection();
    }

    /**
     * @return Task
     */
    public function getUsers()
    {
        return $this->users;
    }

    /**
     * @param Task $users
     */
    public function setUsers($users)
    {
        $this->users = $users;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
