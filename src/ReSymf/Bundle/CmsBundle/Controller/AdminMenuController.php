<?php

namespace ReSymf\Bundle\CmsBundle\Controller;

use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;


use ReSymf\Bundle\CmsBundle\Form\PostType;

/**
 * Post controller.
 *
 */
class AdminMenuController extends Controller
{

    public function profileAction()
    {
        $adminConfigurator = $this->get('resymfcms.configurator.admin');

//		var_dump($adminConfigurator->getAdminConfig());
//		$entity = new Post();
//		$form   = $this->createCreateForm($entity);

        return $this->render('ReSymfCmsBundle:admin:profile.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig()));
    }

    public function dashboardAction()
    {
        $adminConfigurator = $this->get('resymfcms.configurator.admin');


        return $this->render('ReSymfCmsBundle:admin:dashboard.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig()));
    }

    /**
     * Lists all entities.
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

        return $this->render('ReSymfCmsBundle:admin:list.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig(), 'entities' => $entities, 'table_config' => $tableConfig));
    }

    /**
     * Creates a new entity.
     *
     */
    public function createAction($type, Request $request)
    {
//        $entity = new Post();
//        $form = $this->createCreateForm($entity);
//        $form->handleRequest($request);
//
//        if ($form->isValid()) {



//            $em = $this->getDoctrine()->getManager();
//            $em->persist($entity);
//            $em->flush();
//
//            return $this->redirect($this->generateUrl('object_show', array('id' => $entity->getId())));
//        }

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

        return $this->render('ReSymfCmsBundle:admin:form.html.twig', array('menu' => $adminConfigurator->getAdminConfig(), 'site_config' => $adminConfigurator->getSiteConfig(), 'form_config'=>$formConfig, 'route' => $routeName));
    }


    /**
     * Creates a form to create a entity.
     *
     * @param Object $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createCreateForm($entity)
    {
        $form = $this->createForm(new PostType(), $entity, array(
            'action' => $this->generateUrl('object_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new entity.
     *
     */
    public function newAction($type)
    {
        $entity = new Post();
        $form = $this->createCreateForm($entity);

        return $this->render('ReSymfCmsBundle:Post:new.html.twig', array(
            'entity' => $entity,
            'form' => $form->createView(),
        ));
    }

    /**
     * Finds and displays a entity.
     *
     */
    public function showAction($type, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ReSymfCmsBundle:Post')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Post entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ReSymfCmsBundle:Post:show.html.twig', array(
            'entity' => $entity,
            'delete_form' => $deleteForm->createView(),));
    }

    /**
     * Displays a form to edit an existing entity.
     *
     */
    public function editAction($type, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ReSymfCmsBundle:Post')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Post entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('ReSymfCmsBundle:Post:edit.html.twig', array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Creates a form to edit a entity.
     *
     * @param Object $entity
     *
     * @return \Symfony\Component\Form\Form
     */
    private function createEditForm($entity)
    {
        $form = $this->createForm(new PostType(), $entity, array(
            'action' => $this->generateUrl('object_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing entity.
     *
     */
    public function updateAction($type, Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('ReSymfCmsBundle:Post')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Post entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();

            return $this->redirect($this->generateUrl('object_edit', array('id' => $id)));
        }

        return $this->render('ReSymfCmsBundle:Post:edit.html.twig', array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Deletes a entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $entity = $em->getRepository('ReSymfCmsBundle:Post')->find($id);

            if (!$entity) {
                throw $this->createNotFoundException('Unable to find Post entity.');
            }

            $em->remove($entity);
            $em->flush();
        }

        return $this->redirect($this->generateUrl('object'));
    }

    /**
     * Creates a form to delete a entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('object_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm();
    }
}