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
 * @Form(editLabel="Edit project", showLabel="Show Project", createLabel="Create project", showLabel="Show Project")
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
     * @Table(hideOnDevice="", label="Name")
     * @Form(fieldLabel="Name",type="text",required=true)
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Start date")
     * @Form(fieldLabel="Start Date",type="date",required=true)
     *
     * @ORM\Column(name="start_date", type="datetime", length=255)
     */
    private $startDate;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="End date")
     * @Form(fieldLabel="End Date",type="date",required=true)
     *
     * @ORM\Column(name="end_date", type="datetime", length=255)
     */
    private $endDate;


    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Hour price[zl]")
     * @Form(fieldLabel="Hour price[zl]",type="text",required=true)
     *
     * @ORM\Column(name="hour_price", type="string", length=255)
     */
    private $hourPrice;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Paid hours")
     * @Form(fieldLabel="Paid hours",type="text",readOnly=true, required=true)
     *
     */
    private $totalHours;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Total cost[zl]")
     * @Form(fieldLabel="Total cost[zl]",readOnly=true, type="text",required=true)
     *
     */
    private $totalCost;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Worked hours")
     * @Form(fieldLabel="Worked hours",readOnly=true, type="text",required=true)
     *
     */
    private $realTotalHours;

    /**
     * @var string
     *
     * @Table(format="html", hideOnDevice="all", length=300, label="Description")
     * @Form(type="editor",required=true, fieldLabel = "Description")
     *
     * @ORM\Column(name="description", type="text")
     */
    private $description;

    /**
     * @var string
     *
     * @Table(format="html", hideOnDevice="all", length=300, label="Data Access")
     * @Form(type="editor",required=true, fieldLabel = "Data Access")
     *
     * @ORM\Column(name="data_access", type="text")
     */
    private $dataAccess;

    /**
     * @var Task
     *
     * @Form(type="relation", relationType="oneToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Sprint", fieldLabel="Sprints")
     * @Table(display=false)
     *
     * @ORM\OneToMany(targetEntity="Sprint", mappedBy="project")
     */
    private $sprints;

    /**
     * @var Contacts
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="manyToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Contact", displayField="name", fieldLabel="Contacts")
     *
     * @ORM\OneToMany(targetEntity="Contact", mappedBy="project")
     */
    private $contacts;

    /**
     * @var Files
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="oneToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Document", fieldLabel="Documents")
     *
     * @ORM\OneToMany(targetEntity="Document", mappedBy="project")
     */
    private $documents;


    /**
     * @var Terms
     *
     * @Table(display=false)
     * @Form(type="relation", relationType="manyToMany", class="ReSymf\Bundle\ProjectManagerBundle\Entity\Term", fieldLabel="Terms")
     *
     * @ORM\OneToMany(targetEntity="Term", mappedBy="project")
     */
    private $terms;

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
    public function getDataAccess()
    {
        return $this->dataAccess;
    }

    /**
     * @param string $dataAccess
     */
    public function setDataAccess($dataAccess)
    {
        $this->dataAccess = $dataAccess;
    }

    /**
     * @return string
     */
    public function getTotalCost()
    {
        return ($this->getTotalHours() * $this->getHourPrice());
    }

    /**
     * @param string $total_cost
     */
    public function setTotalCost($total_cost)
    {
        $this->totalCost = $total_cost;
    }

    /**
     * @return string
     */
    public function getStartDate()
    {
        if ($this->startDate) {
            return $this->startDate->format('Y-m-d H:i:s');
        } else {
            $date = new \DateTime('now');
            return $date->format('Y-m-d H:i:s');
        }

    }

    /**
     * @param string $realTotalHours
     */
    public function setStartDate($startDate)
    {
        $this->startDate = $startDate;
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
     * @return string
     */
    public function getEndDate()
    {
        if ($this->endDate) {
            return $this->endDate->format('Y-m-d H:i:s');
        } else {
            $date = new \DateTime('now');
            return $date->format('Y-m-d H:i:s');
        }
    }

    /**
     * @param string $realTotalHours
     */
    public function setEndDate($endDate)
    {
        $this->endDate = $endDate;
    }


    /**
     * @return string
     */
    public function getRealTotalHours()
    {
        $sum = 0;
        foreach($this->sprints as $sprint){
            $sum += $sprint->getRealTotalHours();
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
        foreach($this->sprints as $sprint){
            $sum += $sprint->getTotalHours();
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

    public function addSprint($sprint)
    {
        $this->sprints->add($sprint);
    }

    public function addDocument($document)
    {
        $this->documents->add($document);
    }

    public function addTerm($term)
    {
        $this->terms->add($term);
    }

    public function addContact($contact)
    {
        $this->contacts->add($contact);
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
