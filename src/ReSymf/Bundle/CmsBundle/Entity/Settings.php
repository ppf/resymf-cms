<?php

namespace ReSymf\Bundle\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Settings
 *
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="ReSymf\Bundle\CmsBundle\Entity\SettingsRepository")
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Settings", createLabel="Create Settings")
 *
 */
class Settings
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
     * @Form(fieldLabel="App Name",type="text",required=true)
     *
     * @ORM\Column(name="app_name", type="string", length=255)
     */
    private $appName;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Seo Description")
     * @Form(fieldLabel="Seo Description",type="text",required=true)
     *
     * @ORM\Column(name="seo_description", type="string", length=255)
     */
    private $seoDescription;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Seo Keywords")
     * @Form(fieldLabel="Seo Keywords",type="text",required=true)
     *
     * @ORM\Column(name="seo_keywords", type="string", length=255)
     */
    private $seoKeywords;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Seo Separator")
     * @Form(fieldLabel="Seo Separator",type="text",required=true)
     *
     * @ORM\Column(name="seo_separator", type="string", length=255)
     */
    private $seoSeparator;

    /**
     * @var string
     *
     * @Table(hideOnDevice="tablet,phone", label="Google Analytics Key")
     * @Form(fieldLabel="Google Analytics Key",type="text",required=true)
     *
     * @ORM\Column(name="ga_key", type="string", length=255)
     */
    private $gaKey;


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
     * Set appName
     *
     * @param string $appName
     * @return Settings
     */
    public function setAppName($appName)
    {
        $this->appName = $appName;
    
        return $this;
    }

    /**
     * Get appName
     *
     * @return string 
     */
    public function getAppName()
    {
        return $this->appName;
    }

    /**
     * Set seoDescription
     *
     * @param string $seoDescription
     * @return Settings
     */
    public function setSeoDescription($seoDescription)
    {
        $this->seoDescription = $seoDescription;
    
        return $this;
    }

    /**
     * Get seoDescription
     *
     * @return string 
     */
    public function getSeoDescription()
    {
        return $this->seoDescription;
    }

    /**
     * Set seoKeywords
     *
     * @param string $seoKeywords
     * @return Settings
     */
    public function setSeoKeywords($seoKeywords)
    {
        $this->seoKeywords = $seoKeywords;
    
        return $this;
    }

    /**
     * Get seoKeywords
     *
     * @return string 
     */
    public function getSeoKeywords()
    {
        return $this->seoKeywords;
    }

    /**
     * Set seoSeparator
     *
     * @param string $seoSeparator
     * @return Settings
     */
    public function setSeoSeparator($seoSeparator)
    {
        $this->seoSeparator = $seoSeparator;
    
        return $this;
    }

    /**
     * Get seoSeparator
     *
     * @return string 
     */
    public function getSeoSeparator()
    {
        return $this->seoSeparator;
    }

    /**
     * Set gaKey
     *
     * @param string $gaKey
     * @return Settings
     */
    public function setGaKey($gaKey)
    {
        $this->gaKey = $gaKey;
    
        return $this;
    }

    /**
     * Get gaKey
     *
     * @return string 
     */
    public function getGaKey()
    {
        return $this->gaKey;
    }
}
