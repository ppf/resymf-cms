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
 * @Form(editLabel="Edit project", createLabel="Create project")
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
     * @var Task
     *
     * @Form(type="relation", relationType="one", class="ReSymfCms\Bundle\ProjectManagerBundle\Task")
     * @Table(format="text", relation=true, class="ReSymfCms\Bundle\ProjectManagerBundle\Task", fieldLabel = "Zadanie")
     *
     * @ORM\ManyToOne(targetEntity="Task", inversedBy="issues")
     */
    private $task;

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
     * @Form(type="relation", relationType="many", class="ReSymfCms\Bundle\ProjectManagerBundle\Document")
     *
     * @ORM\ManyToMany(targetEntity="Document")
     */
    private $documents;

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