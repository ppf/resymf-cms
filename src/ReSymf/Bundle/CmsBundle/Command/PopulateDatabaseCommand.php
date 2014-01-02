<?php
namespace ReSymf\Bundle\CmsBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use ReSymf\Bundle\CmsBundle\Entity\Page;

class PopulateDatabaseCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('resymf:populate')
            ->setDescription('Populate database');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
// get em
        $em = $this->getContainer()->get('doctrine')->getManager();

        $createDate = new \DateTime();
        $content = 'francuz256@gmail.com';
        $authorId = 1;
        $slug = 'page';

        for($i = 0; $i<200; $i++) {
            $page = new Page();
            $page->setAuthorId($authorId);
            $page->setContent($content);
            $page->setCreateDate($createDate);
            $page->setSlug($slug);
            $page->setName('my name is');
            $em->persist($page);
        }

        $em->flush();
        $output->writeln(sprintf('Created 200 pages'));
    }
}