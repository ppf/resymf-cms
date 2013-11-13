<?php

namespace ReSymf\Bundle\CmsBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ReSymf\Bundle\CmsBundle\Entity\User;
use ReSymf\Bundle\CmsBundle\Entity\Role;

class AdminCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
				->setName('security:createadmin')
				->setDescription('Create admin user')
				->addArgument('username', InputArgument::REQUIRED, 'Admin Name')
				->addArgument('email', InputArgument::REQUIRED, 'Admin Email')
				->addArgument('password', InputArgument::REQUIRED, 'password')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		// get em
		$em = $this->getContainer()->get('doctrine')->getManager();

		$username   = $input->getArgument('username');
		$email      = $input->getArgument('email');
		$password   = $input->getArgument('password');
		$user  = new User();

		$user->setUsername($username);
		$user->setEmail($email);

		$factory = $this->getContainer()->get('security.encoder_factory');
		$encoder = $factory->getEncoder($user);
		$pwd = $encoder->encodePassword($password, $user->getSalt());
		$user->setPassword($pwd);

		$repository = $this->getContainer()->get('doctrine')->getRepository('ReSymfCmsBundle:Role');
		$role = $repository->findOneBy(array('role'=>'ROLE_ADMIN'));

		if(!$role) {
			$role = new Role();
			$role->setName('admin');
			$role->setRole('ROLE_ADMIN');
		}
		$role->addUser($user);
		$user->addRole($role);
		$em->persist($user);
		$em->persist($role);

		$em->flush();
		$output->writeln(sprintf('Created user <comment>%s</comment>', $username));
	}
}