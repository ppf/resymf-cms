<?php

namespace ReSymf\Bundle\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DefaultController
 * @package ReSymf\Bundle\CmsBundle\Controller
 *
 * @author Piotr Francuz <francuz256@gmail.com>
 */
class ErrorController extends Controller
{

    public function notFoundAction()
    {
        return $this->render('ReSymfCmsBundle:error:notfound.html.twig');
    }

    public function accessDeniedAction()
    {
        return $this->render('ReSymfCmsBundle:error:accessdenied.html.twig');
    }
}
