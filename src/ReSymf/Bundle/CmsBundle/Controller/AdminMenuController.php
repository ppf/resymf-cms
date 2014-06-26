<?php

namespace ReSymf\Bundle\CmsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;


/**
 * Class AdminMenuController
 * @package ReSymf\Bundle\CmsBundle\Controller
 *
 * @author Piotr Francuz <piotr.francuz@bizneslan.pl>
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
        $request = $this->container->get('request');
        $routeName = $request->get('_route');
        $em = $this->getDoctrine()->getManager();

        $objectType = 'ReSymf\Bundle\CmsBundle\Entity\User';

        $user = $this->get('security.context')->getToken()->getUser();

        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        //TODO: set settings class in config
//        $objectMapper = $this->get('resymfcms.object.mapper');
//        $objectType = $objectMapper->getSettingsClass($type);

        $annotationReader = $this->get('resymfcms.annotation.reader');

        $formConfig = $annotationReader->readFormAnnotation($objectType);

        if ($request->isMethod('POST')) {
            foreach ($formConfig->fields as $field) {
                $methodName = 'set' . $field['name'];
                $user->$methodName($request->get($field['name']));
            }
            $em->persist($user);
            $em->flush();
        }

        return $this->render(
            'ReSymfCmsBundle:adminmenu:create.html.twig',
            array(
                'menu' => $adminConfigurator->getAdminConfig(),
                'site_config' => $adminConfigurator->getSiteConfig(),
                'form_config' => $formConfig, 'route' => $routeName,
                'edit_object' => $user,
                'multi_select' => array()
            )
        );

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
        if (!$type) {
            return $this->redirect($this->generateUrl('resymf_admin_dashboard'), 301);
        }

        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        $objectMapper = $this->get('resymfcms.object.mapper');

        $objectType = $objectMapper->getMappedObject($type);
        $annotationReader = $this->get('resymfcms.annotation.reader');

        $tableConfig = $annotationReader->readTableAnnotation($objectType);

        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository($objectType)
            ->createQueryBuilder('q')
            ->setMaxResults(220)
            ->getQuery()
            ->getResult();

//        print_r('aaa');
//        die();
        return $this->render('ReSymfCmsBundle:adminmenu:list.html.twig', array(
                'menu' => $adminConfigurator->getAdminConfig(),
                'site_config' => $adminConfigurator->getSiteConfig(),
                'entities' => $entities,
                'table_config' => $tableConfig,
                'object_type' => $type)
        );
    }


    /**
     * Creates a new object base on url and request parameters.
     *
     * @param $type
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function createAction($type)
    {
        if (!$type) {
            return $this->redirect($this->generateUrl('resymf_admin_dashboard'), 301);
        }
        $em = $this->getDoctrine()->getManager();

        $request = $this->container->get('request');
        $routeName = $request->get('_route');

        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        $objectConfigurator = $this->get('resymfcms.configurator.object');

        $objectMapper = $this->get('resymfcms.object.mapper');

        $objectType = $objectMapper->getMappedObject($type);
        $annotationReader = $this->get('resymfcms.annotation.reader');

        $formConfig = $annotationReader->readFormAnnotation($objectType);

        if ($request->isMethod('POST')) {
            $object = new $objectType();

//            echo '<pre>';
//            print_r($formConfig->fields);
//            die();
            foreach ($formConfig->fields as $field) {
                $fieldType = $field['type'];
                $fieldRelationType = $field['relationType'];
                $methodName = 'set' . $field['name'];

//                if()
                switch ($fieldType) {
                    case 'relation':
                        $class = $field['class'];
                        $targetEntityField = $field['targetEntityField'];
                        $relationObjects = $em->getRepository($class)
                            ->createQueryBuilder('q')
                            ->where('q.id IN(:id)')
                            ->setParameter('id', $request->get($field['name']))
//                            ->setMaxResults()
                            ->getQuery()
                            ->getResult();
//                        print_r($relationObject);

                        foreach ($relationObjects as $relationObject) {
                            if ($relationObject) {

                                $addMethodName = 'set' . $type;
                                $addMethodName2 = 'set' . $field['name'];

                                if ($fieldRelationType == 'oneToMany') {
                                    $addMethodName2 = 'add' . $field['name'];

                                } if($fieldRelationType = 'manyToMany' || $fieldRelationType = 'multiselect') {
                                    $addMethodName2 = 'add' . $targetEntityField;
                                } else {  ///toOne
                                    $relationObject->$addMethodName($object);
                                }
//
                                $object->$addMethodName2($relationObject);

                            }
                        }

                        break;
                    case 'date':
                        $object->$methodName(new \DateTime($request->get($field['name'])));
                        break;
                    case 'file':
//                        echo $field['name'];
//                        print_r($request->get($field['name']));
//                        die();
                        $object->$methodName(json_encode($request->get($field['name'])));
                    default:
                        $object->$methodName($request->get($field['name']));
                }

            }

            $objectConfigurator->setInitialValuesFromAnnotations($objectType, $object, $type);
            $em->persist($object);
            $em->flush();
            return $this->redirect($this->generateUrl('object_edit', array('type' => $type, 'id' => $object->getId())), 301);
        }

        if (!isset($object)) {
            $object = false;
        }
        $multiSelectValues = $objectConfigurator->generateMultiSelectOptions($objectType, $object);

        return $this->render('ReSymfCmsBundle:adminmenu:create.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig(), 'form_config' => $formConfig, 'route' => $routeName, 'multi_select' => $multiSelectValues));
    }


    /**
     * @param $type
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function editAction($type, $id)
    {
        if (!$id) {
            return $this->redirect($this->generateUrl('resymf_admin_dashboard'), 301);
        }
        $request = $this->container->get('request');
        $routeName = $request->get('_route');

        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        $objectMapper = $this->get('resymfcms.object.mapper');

        $objectConfigurator = $this->get('resymfcms.configurator.object');

        $objectType = $objectMapper->getMappedObject($type);
        $annotationReader = $this->get('resymfcms.annotation.reader');

        $formConfig = $annotationReader->readFormAnnotation($objectType);
        $formConfig->objectKey = $type;

        $em = $this->getDoctrine()->getManager();

        $editObject = $em->getRepository($objectType)
            ->createQueryBuilder('q')
            ->where('q.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();


        if (!$editObject) {
            // TODO: może redirect do tworzenia ?
            throw new \Exception('Object not found');
        }


        if ($request->isMethod('POST') && $editObject) {

//            echo '<pre>';
//            print_r($formConfig->fields);
//            die();
            foreach ($formConfig->fields as $field) {
                $fieldType = $field['type'];
                $fieldRelationType = $field['relationType'];
                $methodName = 'set' . $field['name'];
                $targetEntityField = $field['targetEntityField'];
                $autoInput = $field['autoInput'];
//                print_r($field);
//                die();
//                if()
                if(!$autoInput) {
                    switch ($fieldType) {
                        case 'relation':
                            $class = $field['class'];
                            $parameters = $request->get($field['name']);

                            $relationObjects = $em->getRepository($class)
                                ->createQueryBuilder('q')
                                ->where('q.id IN(:id)')
                                ->setParameter('id', $parameters)
                                ->getQuery()
                                ->getResult();

                            $addMethodName2 = 'set' . $field['name'];
                            $editObject->$addMethodName2($relationObjects);
                            foreach ($relationObjects as $relationObject) {

                                if ($relationObject) {

                                    $addMethodName = 'set' . $type;
                                    $addMethodName2 = 'set' . $field['name'];

                                    if ($fieldRelationType == 'oneToMany') {
                                        $addMethodName2 = 'add' . $field['name'];

                                    }
                                    if ($fieldRelationType = 'manyToMany' || $fieldRelationType = 'multiselect') {
                                        $addMethodName2 = 'add' . $targetEntityField;
                                    } else { ///toOne
                                        $relationObject->$addMethodName($editObject);
                                    }

                                }
                            }


                            break;
                        case 'date':
                            $editObject->$methodName(new \DateTime($request->get($field['name'])));
                            break;
                        case 'file':
                            $editObject->$methodName($request->get($field['name']));

                        default:
                            $editObject->$methodName($request->get($field['name']));
                    }
                }
            }

            $objectConfigurator->checkUniqueValuesFromAnnotations($editObject, $type);

            $em = $this->getDoctrine()->getManager();
            $em->persist($editObject);
            $em->flush();
//            die();
        }
//        echo '<pre>';
//        print_r($formConfig);
//        die();

        $multiSelectValues = $objectConfigurator->generateMultiSelectOptions($objectType, $editObject);
//        print_r($multiSelectValues);
//        die();

        return $this->render(
            'ReSymfCmsBundle:adminmenu:create.html.twig',
            array(
                'menu' => $adminConfigurator->getAdminConfig(),
                'site_config' => $adminConfigurator->getSiteConfig(),
                'form_config' => $formConfig, 'route' => $routeName,
                'edit_object' => $editObject,
                'multi_select' => $multiSelectValues
            )
        );
    }

    /**
     * @param $type
     * @param $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * @throws \Exception
     */
    public function showAction($type, $id)
    {
        if (!$id) {
            return $this->redirect($this->generateUrl('resymf_admin_dashboard'), 301);
        }
        $request = $this->container->get('request');
        $routeName = $request->get('_route');

        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        $objectMapper = $this->get('resymfcms.object.mapper');

        $objectConfigurator = $this->get('resymfcms.configurator.object');

        $objectType = $objectMapper->getMappedObject($type);
        $annotationReader = $this->get('resymfcms.annotation.reader');

        $formConfig = $annotationReader->readFormAnnotation($objectType);
        $formConfig->objectType = $objectType;
        $formConfig->objectKey = $type;

        $em = $this->getDoctrine()->getManager();

        $editObject = $em->getRepository($objectType)
            ->createQueryBuilder('q')
            ->where('q.id = :id')
            ->setParameter('id', $id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();


        if (!$editObject) {
            // TODO: może redirect do tworzenia ?
            throw new \Exception('Object not found');
        }

        $multiSelectValues = $objectConfigurator->generateMultiSelectOptions($objectType, $editObject);
//        print_r($multiSelectValues);
//        die();
//        $multiSelectValues

        return $this->render(
            'ReSymfCmsBundle:adminmenu:show.html.twig',
            array(
                'menu' => $adminConfigurator->getAdminConfig(),
                'site_config' => $adminConfigurator->getSiteConfig(),
                'form_config' => $formConfig, 'route' => $routeName,
                'edit_object' => $editObject,
                'multi_select' => $multiSelectValues
            )
        );
    }

    public function deleteAction($type, $id)
    {
        $request = $this->container->get('request');
        $url = $request->headers->get('referer');
//        print_r($url);
//        die();
        if (!$id) {
            return $this->redirect($this->generateUrl('resymf_admin_dashboard'), 301);
        }
        $objectMapper = $this->get('resymfcms.object.mapper');
        $objectType = $objectMapper->getMappedObject($type);
        $em = $this->getDoctrine()->getManager();

        // TODO: if no object display error
        $editObject = $em->getRepository($objectType)->createQueryBuilder('q')->where('q.id = :id')->setParameter('id', $id)->setMaxResults(1)->getQuery()->getResult();
        if (!isset($editObject[0])) {
            return $this->redirect($this->generateUrl('object_list', array('type' => $type)), 301);
        }
        $em->remove($editObject[0]);
        $em->flush();

        //return $this->redirect($this->generateUrl('object_list', array('type' => $type)), 301);
        return $this->redirect($url, 301);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function settingsAction()
    {
        $request = $this->container->get('request');
        $routeName = $request->get('_route');
        $em = $this->getDoctrine()->getManager();

        $objectType = 'ReSymf\Bundle\CmsBundle\Entity\Settings';

        // ALways first object
        $settingsObject = $em->getRepository($objectType)
            ->createQueryBuilder('q')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
//        print_r($settingsObject);
        //      die();
        if (!$settingsObject) {
            $settingsObject = new $objectType();
        }

        $adminConfigurator = $this->get('resymfcms.configurator.admin');
        //TODO: set settings class in config
//        $objectMapper = $this->get('resymfcms.object.mapper');
//        $objectType = $objectMapper->getSettingsClass($type);

        $annotationReader = $this->get('resymfcms.annotation.reader');

        $formConfig = $annotationReader->readFormAnnotation($objectType);

        if ($request->isMethod('POST')) {
            foreach ($formConfig->fields as $field) {
                $methodName = 'set' . $field['name'];
                $settingsObject->$methodName($request->get($field['name']));
            }
            $em->persist($settingsObject);
            $em->flush();
        }

        return $this->render(
            'ReSymfCmsBundle:adminmenu:create.html.twig',
            array(
                'menu' => $adminConfigurator->getAdminConfig(),
                'site_config' => $adminConfigurator->getSiteConfig(),
                'form_config' => $formConfig, 'route' => $routeName,
                'edit_object' => $settingsObject,
                'multi_select' => array()
            )
        );
    }
}
