<?php
/**
 * Created by PhpStorm.
 * User: Piotr
 * Date: 26.02.14
 * Time: 19:23
 */

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

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
 * @Form(editLabel="Edit Term", createLabel="Create Term", showLabel="Show Term")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Term
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
     * @var \DateTime
     *
     * @Table(label="Date", format="date", dateFormat="Y-m-d")
     * @Form(type="date",required=true, dateFormat="DD-MM-YYYY")
     *
     * @ORM\Column(name="create_date", type="datetime", nullable=true)
     */
    private $date;

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
     * @var Project
     *
     * @Form(type="relation", relationType="manyToOne", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Project", fieldLabel="Project")
     * @Table(display=false)
     *
     * @ORM\ManyToOne(targetEntity="Project", inversedBy="sprints")
     */
    private $project;


    /**
     * @return \DateTime
     */
    public function getDate()
    {
        if ($this->date) {
            return $this->date->format('Y-m-d');
        } else {
            return new \DateTime('now');
        }
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
    }

    /**
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param Project $project
     */
    public function setProject($project)
    {
        $this->project = $project;
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

} 