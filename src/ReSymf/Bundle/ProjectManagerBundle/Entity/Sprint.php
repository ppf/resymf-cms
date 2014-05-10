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
 * @Form(editLabel="Edit Sprint", showLabel="Show Sprint", createLabel="Create Sprint")
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
     * @Table(hideOnDevice="tablet,phone", label="Name")
     * @Form(fieldLabel="Page Name",type="text",required=true)
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @Table(format="html", length=300, label="Opis")
     * @Form(type="editor",required=true, fieldLabel = "Opis")
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var Task
     *
     * @Form(type="relation", relationType="many", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Task")
     * @Table(display=false)
     *
     * @ORM\OneToMany(targetEntity="Task", mappedBy="sprint")
     */
    private $tasks;

    /**
     * @var Project
     *
     * @Form(type="relation", relationType="manyToOne", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Project")
     * @Table(display=false)
     *
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="sprints")
     */
    private $project;
//@Table(format="text", relation=true, class="ReSymf\Bundle\ProjectManagerBundle\Entity\Project")


    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Suma godzin")
     * @Form(fieldLabel="Suma godzin",type="text",required=true)
     *
     * @ORM\Column(name="hour_price", type="string", length=255)
     */
    private $totalHours;

    /**
     * @var Documents
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="manyToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Document")
     *
     * @ORM\ManyToMany(targetEntity="Document")
     */
    private $documents;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Rzeczywista suma godzin")
     * @Form(fieldLabel="Rzeczywista suma godzin",type="text",required=true)
     *
     * @ORM\Column(name="real_total_hours", type="string", length=255)
     */
    private $realTotalHours;

    /**
     * @var Terms
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="manyToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Term")
     *
     * @ORM\ManyToMany(targetEntity="Term")
     */
    private $terms;

    function __construct()
    {
        $this->tasks = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getRealTotalHours()
    {
        return $this->realTotalHours;
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
        return $this->totalHours;
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
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Terms
     */
    public function getTerms()
    {
        return $this->terms;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Terms $terms
     */
    public function setTerms($terms)
    {
        $this->terms = $terms;
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
} 