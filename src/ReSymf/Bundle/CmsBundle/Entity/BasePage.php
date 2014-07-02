<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 21.04.14
 * Time: 15:01
 */

namespace ReSymf\Bundle\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Entity\User;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

class BasePage
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
    protected $id;


    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="title")
     * @Form(fieldLabel="Page title",type="text",required=true)
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    protected $title;

    /**
     * @var string
     *
     * @Table(format="html", length=300, label="Content")
     * @Form(type="editor",required=true, fieldLabel = "Content")
     *
     * @ORM\Column(name="content", type="text")
     */
    protected $content;

    /**
     * @var string
     *
     * @Form(type="text",required=true, fieldLabel="Slug to page", autoInput="uniqueSlug")
     *
     * @ORM\Column(name="slug", type="string")
     */
    protected $slug;

    /**
     * @var \DateTime
     *
     * @Table(label="Create Date", format="date", dateFormat="Y-m-d H:i:s")
     * @Form(type="text", readOnly=true, required=true, autoInput="currentDateTime", fieldLabel="Create date", dateFormat="Y-m-d H:i:s")
     *
     * @ORM\Column(name="create_date", type="datetime", nullable=true)
     */
    protected $createDate;


    /**
     * @var integer
     *
     * @Table(hideOnDevice="tablet,phone", label="Author", format="text")
     * @Form(display="text", readOnly=true, autoInput="currentUserId", fieldLabel="Author")
     *
     * @ORM\ManyToOne(targetEntity="User")
     */
    protected $author;


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get createDate
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        if ($this->createDate) {
            return $this->createDate->format('Y-m-d H:i:s');
        } else {
            $date = new \DateTime('now');
            return $date->format('Y-m-d H:i:s');
        }
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return Post
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle($title)
    {
        $this->title = $title;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set content
     *
     * @param string $content
     * @return Post
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get authorId
     *
     * @return integer
     */
    public function getAuthor()
    {
        if($this->author){
            return $this->author->getUsername();
        }
    }

    /**
     * @param $author
     * @return $this
     */
    public function setAuthor($author)
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param string $slug
     */
    public function setSlug($slug)
    {
        if ($slug) {
            $this->slug = $slug;
        } else {
            $this->slug = $slug;
        }
    }
} 
