<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 11/10/13
 * Time: 3:22 PM
 */

namespace ReSymf\Bundle\CmsBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Security\Core\User\AdvancedUserInterface;


/**
 * Class User
 * @package ReSymf\Bundle\CmsBundle\Entity
 *
 * @ORM\Table(name="resymf_users")
 * @ORM\Entity(repositoryClass="ReSymf\Bundle\CmsBundle\Entity\UserRepository")
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Profile", createLabel="Create Profile", showLabel="Show Profile")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class User implements AdvancedUserInterface, \Serializable
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     *
     * @Table(display=false)
     * @Form(display=false)
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=25, unique=true)
     *
     * @Table(hideOnDevice="tablet,phone", label="username")
     * @Form(fieldLabel="Username",type="text",required=true)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=32)
     *
     * @Table(display=false)
     * @Form(display=false)
     */
    private $salt;

    /**
     * @ORM\Column(type="string", length=64)
     *
     * @Table(display=false)
     * @Form(display=false)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=60, unique=true)
     *
     * @Table(hideOnDevice="tablet,phone", label="email")
     * @Form(fieldLabel="email",type="text",required=true)
     */
    private $email;

    /**
     * @ORM\Column(name="is_active", type="boolean")
     *
     * @Table(hideOnDevice="tablet,phone", label="active")
     * @Form(fieldLabel="Active",type="text",required=true)
     */
    private $isActive;

    /**
     * @ORM\ManyToMany(targetEntity="Role", inversedBy="users")
     *
     * @Table(display=false)
     * @Form(display=false)
     *
     */
    private $roles;


    public function __construct()
    {
        $this->isActive = true;
        $this->salt = md5(uniqid(null, true));
        $this->roles = new ArrayCollection();
    }

    /**
     * @inheritDoc
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set username
     *
     * @param string $username
     * @return User
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getSalt()
    {
        return $this->salt;
    }

    /**
     * Set salt
     *
     * @param string $salt
     * @return User
     */
    public function setSalt($salt)
    {
        $this->salt = $salt;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set password
     *
     * @param string $password
     * @return User
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getRoles()
    {
        return $this->roles->toArray();
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials()
    {
    }

    /**
     * @see \Serializable::serialize()
     */
    public function serialize()
    {
        return serialize(array(
            $this->id,
        ));
    }

    /**
     * @see \Serializable::unserialize()
     */
    public function unserialize($serialized)
    {
        list (
            $this->id,
            ) = unserialize($serialized);
    }

    public function isAccountNonExpired()
    {
        return true;
    }

    public function isAccountNonLocked()
    {
        return true;
    }

    public function isCredentialsNonExpired()
    {
        return true;
    }

    public function isEnabled()
    {
        return $this->isActive;
    }

    /**
     * @return mixed
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return User
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get isActive
     *
     * @return boolean
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * Set isActive
     *
     * @param boolean $isActive
     * @return User
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

    /**
     * Add roles
     *
     * @param \ReSymf\Bundle\CmsBundle\Entity\Role $roles
     * @return User
     */
    public function addRole(\ReSymf\Bundle\CmsBundle\Entity\Role $roles)
    {
        $this->roles[] = $roles;

        return $this;
    }

    /**
     * Remove roles
     *
     * @param \ReSymf\Bundle\CmsBundle\Entity\Role $roles
     */
    public function removeRole(\ReSymf\Bundle\CmsBundle\Entity\Role $roles)
    {
        $this->roles->removeElement($roles);
    }
}
