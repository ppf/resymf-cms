<?php

namespace ReSymf\Bundle\CmsBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Query\Expr\Base;
use ReSymf\Bundle\CmsBundle\Annotation\Table;
use ReSymf\Bundle\CmsBundle\Annotation\Form;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * Class Page
 * @package ReSymf\Bundle\CmsBundle\Entity
 *
 * @ORM\Table()
 * @ORM\Entity
 *
 * @Table(sorting=true, paging=true, pageSize=10, filtering=true)
 * @Form(editLabel="Edit Page", createLabel="Create Page")
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class Page extends BasePage
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
}
