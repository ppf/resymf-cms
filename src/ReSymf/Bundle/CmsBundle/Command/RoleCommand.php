<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 11/10/13
 * Time: 10:17 PM
 */

namespace ReSymf\Bundle\CmsBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ReSymf\Bundle\CmsBundle\Entity\Role;

class RoleCommand extends ContainerAwareCommand
{
	protected function configure()
	{
		$this
				->setName('security:createrole')
				->setDescription('Create Role')
				->addArgument('name', InputArgument::REQUIRED, 'Name')
				->addArgument('roleName', InputArgument::REQUIRED, 'Role')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$name   = $input->getArgument('name');
		$roleName      = $input->getArgument('roleName');

		$role = new Role();

		$role->setName($name);
		$role->setRole($roleName);

		$em = $this->getContainer()->get('doctrine')->getManager();
		$em->persist($role);
		$em->flush();

		$output->writeln(sprintf('Created role <comment>%s</comment>', $roleName));

	}
}