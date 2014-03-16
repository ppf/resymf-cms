<?php
/**
 * Created by PhpStorm.
 * User: Piotr
 * Date: 26.02.14
 * Time: 19:23
 */

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Class Task
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
     * @var \DateTime
     *
     * @Table(label="Date", format="date", dateFormat="Y-m-d H:i:s")
     * @Form(type="date",required=true, autoInput="currentTime")
     *
     * @ORM\Column(name="create_date", type="datetime", nullable=true)
     */
    private $date;

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
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     */
    public function setDate($date)
    {
        $this->date = $date;
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
} 