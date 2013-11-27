<?php
/**
 * Created by PhpStorm.
 * User: ppf
 * Date: 11/4/13
 * Time: 10:50 AM
 */

namespace ReSymf\Bundle\CmsBundle\Services;

use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

class MenuConfigurator
{

	private $menu;

	private $config;

	public function __construct()
	{
		$yaml = new Parser();
		try {
			$value = $yaml->parse(file_get_contents(__DIR__.'/../Resources/config/admin/admin.yml'));
		} catch (ParseException $e) {
			printf("Unable to parse the YAML string: %s", $e->getMessage());
		}

		$this->menu = $value['admin'];
		$this->config = $value['site_config'];
		// TODO: Implement __construct() method. Load menu to array
	}

	public function getMenuFromConfig()
	{
		return $this->menu;
	}

	public function getSiteConfig()
	{
		return $this->config;
	}

	public function checkItemIfExistInMenu()
	{
		// TODO: check if item exist return mapping full class namespace
	}

} 