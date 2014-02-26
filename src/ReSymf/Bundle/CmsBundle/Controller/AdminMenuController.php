<?php

namespace ReSymf\Bundle\CmsBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


/**
 * Class AdminMenuController
 * @package ReSymf\Bundle\CmsBundle\Controller
 *
 * @author Piotr Francuz <francuz256@gmail.com>
 */
class AdminMenuController extends Controller
{

    /**
     * Profile show/edit url
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function profileAction()
    {
        $adminConfigurator = $this->get('resymfcms.configurator.admin');

        return $this->render('ReSymfCmsBundle:adminmenu:profile.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig()));
    }

    /**
     * Display dashboard information
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dashboardAction()
    {
        $adminConfigurator = $this->get('resymfcms.configurator.admin');

        return $this->render('ReSymfCmsBundle:adminmenu:dashboard.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig()));
    }

    /**
     * Lists all entities.
     *
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listAction($type)
    {
        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        $objectMapper = $this->get('resymfcms.object.mapper');

        $objectType = $objectMapper->getMappedObject($type);
        $annotationReader = $this->get('resymfcms.annotation.reader');

        $tableConfig = $annotationReader->readTableAnnotation($objectType);

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository($objectType)->createQueryBuilder('q')->setMaxResults(220)->getQuery()->getResult();

        return $this->render('ReSymfCmsBundle:adminmenu:list.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig(), 'entities' => $entities, 'table_config' => $tableConfig));
    }


    /**
     * Creates a new object base on url and request parameters.
     *
     * @param $type
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction($type, Request $request)
    {
        $request = $this->container->get('request');
        $routeName = $request->get('_route');

        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        $objectMapper = $this->get('resymfcms.object.mapper');

        $objectType = $objectMapper->getMappedObject($type);
        $annotationReader = $this->get('resymfcms.annotation.reader');

        $formConfig = $annotationReader->readFormAnnotation($objectType);

        if ($request->isMethod('POST')) {
            $object = new $objectType();
            foreach($formConfig->fields as $field) {
                $methodName = 'set'.$field['name'];
                $object->$methodName($request->get($field['name']));
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($object);
            $em->flush();
        }

        return $this->render('ReSymfCmsBundle:adminmenu:create.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig(), 'form_config'=>$formConfig, 'route' => $routeName));
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingsAction(Request $request)
    {
        $request = $this->container->get('request');
        $routeName = $request->get('_route');

        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        //TODO: set settings class in config
//        $objectMapper = $this->get('resymfcms.object.mapper');
//        $objectType = $objectMapper->getSettingsClass($type);

        $objectType =  'ReSymf\Bundle\CmsBundle\Entity\Settings';
        $annotationReader = $this->get('resymfcms.annotation.reader');

        $formConfig = $annotationReader->readFormAnnotation($objectType);

        if ($request->isMethod('POST')) {
            $object = new $objectType();
            foreach($formConfig->fields as $field) {
                $methodName = 'set'.$field['name'];
                $object->$methodName($request->get($field['name']));
            }
            $em = $this->getDoctrine()->getManager();
            $em->persist($object);
            $em->flush();
        }

        return $this->render('ReSymfCmsBundle:adminmenu:create.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig(), 'form_config'=>$formConfig, 'route' => $routeName));
    }

}