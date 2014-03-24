<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 23.03.14
 * Time: 23:14
 */

namespace ReSymf\Bundle\ProjectManagerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Company
 * @package ReSymf\Bundle\ProjectManagerBundle\Entity
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Company", createLabel="Create Company")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Company
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
     * @Form(fieldLabel="Company Name",type="text",required=true)
     *
     * @ORM\Column(name="name", type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Name")
     * @Form(fieldLabel="Zip code",type="text",required=true)
     *
     * @ORM\Column(name="zip", type="string", length=255)
     */
    private $zip;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Name")
     * @Form(fieldLabel="Street",type="text",required=true)
     *
     * @ORM\Column(name="street", type="string", length=255)
     */
    private $street;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Name")
     * @Form(fieldLabel="Nip",type="text",required=true)
     *
     * @ORM\Column(name="nip", type="string", length=255)
     */
    private $nip;

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
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function getNip()
    {
        return $this->nip;
    }

    /**
     * @param mixed $nip
     */
    public function setNip($nip)
    {
        $this->nip = $nip;
    }

    /**
     * @return mixed
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param mixed $street
     */
    public function setStreet($street)
    {
        $this->street = $street;
    }

    /**
     * @return mixed
     */
    public function getZip()
    {
        return $this->zip;
    }

    /**
     * @param mixed $zip
     */
    public function setZip($zip)
    {
        $this->zip = $zip;
    }

} 