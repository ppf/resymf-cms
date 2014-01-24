<?php

namespace ReSymf\Bundle\CmsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * Class DefaultController
 * @package ReSymf\Bundle\CmsBundle\Controller
 *
 * @author Piotr Francuz <francuz256@gmail.com>
 */
class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('ReSymfCmsBundle::index.html.twig');
    }
}
