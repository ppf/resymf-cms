<?php
/**
 * Created by PhpStorm.
 * User: Piotr
 * Date: 12.02.14
 * Time: 16:53
 */

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Contact
 * @package ReSymf\Bundle\ProjectManagerBundle\Entity
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Contact", createLabel="Create Contact", showLabel="Show Contact")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Contact
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
     * @Form(type="text",required=true, fieldLabel="Last Name")
     * @Table(hideOnDevice="tablet,phone", label="Last")
     *
     * @ORM\Column(name="last_name", type="string")
     */
    private $lastName;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Phone")
     * @Form(fieldLabel="Phone",type="text",required=true)
     *
     * @ORM\Column(name="phone", type="string", length=255)
     */
    private $phone;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Email")
     * @Form(fieldLabel="Email",type="text",required=true)
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

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
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $content
     */
    public function setDescription($content)
    {
        $this->description = $content;
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
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail($email)
    {
        $this->email = $email;
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
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $last_name
     */
    public function setLastName($last_name)
    {
        $this->lastName = $last_name;
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
