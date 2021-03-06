<?php
/**
 * Created by PhpStorm.
 * User: Piotr
 * Date: 26.02.14
 * Time: 15:20
 */

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Sprint
 * @package ReSymf\Bundle\ProjectManagerBundle\Entity
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Sprint", showLabel="Show Sprint", createLabel="Create Sprint", showLabel="Show Sprint")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Sprint
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
     * @Table(label="Name")
     * @Form(fieldLabel="Page Name",type="text",required=true)
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @Table(format="html", hideOnDevice="tablet,phone", length=300, label="Description")
     * @Form(type="editor",required=true, fieldLabel = "Description")
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var Task
     *
     * @Form(type="relation", relationType="oneToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Task", fieldLabel="Tasks")
     * @Table(display=false)
     *
     * @ORM\OneToMany(targetEntity="Task", mappedBy="sprint")
     */
    private $tasks;

    /**
     * @var Project
     *
     * @Form(type="relation", relationType="manyToOne", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Project", fieldLabel="Project")
     * @Table(display=false)
     *
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="sprints")
     */
    private $project;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Priced hours")
     * @Form(fieldLabel="Priced hours",readOnly=true, type="text",required=true)
     *
     */
    private $totalHours;

    /**
     * @var Documents
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="manyToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Document", fieldLabel="Documents")
     *
     * @ORM\ManyToMany(targetEntity="Document")
     */
    private $documents;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Worked hours")
     * @Form(fieldLabel="Worked hours",readOnly=true, type="text",required=true)
     *
     */
    private $realTotalHours;


    function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getRealTotalHours()
    {
        $sum = 0;
        foreach($this->tasks as $task){
            $sum += $task->getRealTotalHours();
        };
        return $sum;
    }

    /**
     * @param string $realTotalHours
     */
    public function setRealTotalHours($realTotalHours)
    {
        $this->realTotalHours = $realTotalHours;
    }

    /**
     * @return string
     */
    public function getTotalHours()
    {
        $sum = 0;
        foreach($this->tasks as $task){
            $sum += $task->getTotalHours();
        };
        return $sum;
    }

    /**
     * @param string $totalHours
     */
    public function setTotalHours($totalHours)
    {
        $this->totalHours = $totalHours;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Documents
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Documents $documents
     */
    public function setDocuments($documents)
    {
        $this->documents = $documents;
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


    /**
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
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
} 
