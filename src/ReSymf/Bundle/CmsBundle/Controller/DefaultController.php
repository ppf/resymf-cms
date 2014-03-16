<?php

namespace ReSymf\Bundle\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * Class DefaultController
 * @package ReSymf\Bundle\CmsBundle\Controller
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 */
class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('ReSymfCmsBundle::index.html.twig');
    }
}
