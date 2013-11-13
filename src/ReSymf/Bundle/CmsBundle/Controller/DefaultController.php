<?php

namespace ReSymf\Bundle\CmsBundle\Controller;

use ReSymf\Bundle\CmsBundle\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Class DefaultController
 * @package ReSymf\Bundle\CmsBundle\Controller
 *
 * @author Piotr Francuz <francuz256@gmail.com>
 */
class DefaultController extends Controller
{
	/**
	 * @return array
	 */
	public function indexAction()
	{
		return $this->render('ReSymfCmsBundle::index.html.twig');
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function addPostAction()
	{
		$post = new Post();

		$form = $this->createFormBuilder($post)
				->add('name', 'text')
				->add('content', 'textarea')
				->add('save', 'submit')
				->getForm();

		return $this->render('ReSymfCmsBundle:Default:form.html.twig', array(
			'form' => $form->createView(),
		));
	}
}
