<?php

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;

/**
 * Category
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Category", createLabel="Create Category", showLabel="Show Category")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Status
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
     * @Form(type="relation", relationType="oneToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Task", fieldLabel="Tasks")
     * @Table(display=false)
     *
     * @ORM\OneToMany(targetEntity="Task", mappedBy="status")
     */
    private $tasks;

    
    function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

/**
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Task
     */
    public function getTasks()
    {
        return $this->tasks;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Task $tasks
     */
    public function setTasks($tasks)
    {
        $this->tasks = $tasks;
    }

    public function addTask($task)
    {
        $this->tasks->add($task);
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
