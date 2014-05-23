<?php
/**
 * Created by PhpStorm.
 * User: Piotr
 * Date: 26.02.14
 * Time: 20:18
 */

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Issue
 * @package ReSymf\Bundle\ProjectManagerBundle\Entity
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit issue", createLabel="Create issue", showLabel="Show Issue")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Issue
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
     * @Table(hideOnDevice="", label="Name")
     * @Form(fieldLabel="Name",type="text",required=true)
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var Task
     *
     * @Form(type="relation", relationType="manyToOne", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Task", fieldLabel="Task")
     * @Table(display="false")
     *
     * @ORM\ManyToOne(targetEntity="Task", inversedBy="issues")
     */
    private $task;

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
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Status")
     * @Form(fieldLabel="Status",type="text",required=true)
     *
     * @ORM\Column(name="status", type="string", length=255)
     */
    private $status;

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
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Task
     */
    public function getTask()
    {
        return $this->task;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Task $task
     */
    public function setTask($task)
    {
        $this->task = $task;
    }
} 
