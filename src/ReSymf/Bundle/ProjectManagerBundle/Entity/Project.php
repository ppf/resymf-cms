<?php
/**
 * Created by PhpStorm.
 * User: Piotr
 * Date: 12.02.14
 * Time: 17:17
 */

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Project
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
class Project
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
     * @Table(hideOnDevice="tablet,phone", label="Cena za godzinę")
     * @Form(fieldLabel="Cena za godzinę",type="text",required=true)
     *
     * @ORM\Column(name="hour_price", type="string", length=255)
     */
    private $hourPrice;

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
     * @Table(format="html", length=300, label="Opis")
     * @Form(type="editor",required=true, fieldLabel = "Opis")
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var Task
     *
     * @Form(type="relation", relationType="many", class="ReSymfCms\Bundle\ProjectManagerBundle\Sprint", fieldLabel="Sprints")
     * @Table(display=false)
     *
     * @ORM\OneToMany(targetEntity="Sprint", mappedBy="project")
     */
    private $sprints;

    /**
     * @var Contacts
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="many", class="ReSymfCms\Bundle\ProjectManagerBundle\Contact", fieldLabel="Contacts")
     *
     * @ORM\ManyToMany(targetEntity="Contact")
     */
    private $contacts;

    /**
     * @var Files
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="many", class="ReSymfCms\Bundle\ProjectManagerBundle\Document", fieldLabel="Documents")
     *
     * @ORM\ManyToMany(targetEntity="Document")
     */
    private $documents;

    /**
     *
     */
    function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->sprints = new ArrayCollection();
        $this->documents = new ArrayCollection();
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
    public function getHourPrice()
    {
        return $this->hourPrice;
    }

    /**
     * @param string $hourPrice
     */
    public function setHourPrice($hourPrice)
    {
        $this->hourPrice = $hourPrice;
    }

    /**
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Files
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Files $documents
     */
    public function setDocuments($documents)
    {
        $this->documents = $documents;
    }

    /**
     * @return \ReSymf\Bundle\ProjectManagerBundle\Entity\Contacts
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param \ReSymf\Bundle\ProjectManagerBundle\Entity\Contacts $contacts
     */
    public function setContacts($contacts)
    {
        $this->contacts = $contacts;
    }

    /**
     * @return mixed
     */
    public function getSprints()
    {
        return $this->sprints;
    }

    /**
     * @param mixed $tasks
     */
    public function setSprints($tasks)
    {
        $this->sprints = $tasks;
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