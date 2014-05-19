<?php
/**
 * Created by PhpStorm.
 * User: Piotr
 * Date: 12.02.14
 * Time: 17:42
 */

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Task
 * @package ReSymf\Bundle\ProjectManagerBundle\Entity
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Task", createLabel="Create project")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Task
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
     * @var Project
     *
     * @Form(type="relation", relationType="manyToOne", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Sprint")
     * @Table(display=false)
     *
     * @ORM\ManyToOne(targetEntity="Sprint", inversedBy="tasks")
     */
    private $sprint;

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
     * @Table(hideOnDevice="tablet,phone", label="Suma godzin")
     * @Form(fieldLabel="Suma godzin",type="text",required=true)
     *
     * @ORM\Column(name="total_hours", type="string", length=255)
     */
    private $totalHours;

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
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Priorytet")
     * @Form(fieldLabel="Priorytet",type="text",required=true)
     *
     * @ORM\Column(name="priority", type="string", length=255)
     */
    private $priority;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Status")
     * @Form(fieldLabel="Status",type="text",required=true)
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

    /**
     * @var Issue
     *
     * @Form(type="relation", relationType="oneToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Issue")
     * @Table(display=false)
     *
     * @ORM\OneToMany(targetEntity="Issue", mappedBy="task")
     */
    private $issues;

    function __construct()
    {
        $this->documents = new ArrayCollection();
        $this->issues = new ArrayCollection();
    }

    /**
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Issue
     */
    public function getIssues()
    {
        return $this->issues;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Issue $issues
     */
    public function setIssues($issues)
    {
        $this->issues = $issues;
    }

    /**
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
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
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Project
     */
    public function getSprint()
    {
        return $this->sprint;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Project $project
     */
    public function setSprint($project)
    {
        $this->sprint = $project;
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
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;
    }

} 
