<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 16.03.14
 * Time: 08:33
 */

namespace ReSymf\Bundle\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class CmsRoutingController
 * @package ReSymf\Bundle\CmsBundle\Controller
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
 *
 */
class CmsRoutingController extends Controller
{
    public function indexAction($slug)
    {
        $em = $this->getDoctrine()->getManager();
        $pageObject = $em->getRepository('ReSymf\Bundle\CmsBundle\Entity\Page')->createQueryBuilder('p')
            ->select('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        if($pageObject) {
            return $this->render('ReSymfCmsBundle::index.html.twig', array( 'pageObject' => $pageObject ));
        } else {
            return $this->notFoundAction();
        }
    }

    public function notFoundAction()
    {
        return $this->render('ReSymfCmsBundle:error:notfound.html.twig');
    }

    public function accessDeniedAction()
    {
        return $this->render('ReSymfCmsBundle:error:accessdenied.html.twig');
    }
} 