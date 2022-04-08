<?php declare(strict_types = 1);

namespace App;

use Nette;
use Nette\Application\Routers\RouteList;

class RouterFactory
{

	use Nette\StaticClass;

	public static function createRouter(): Nette\Routing\Router
	{
		$router = new RouteList();

		$router->withModule('Admin')
			->addRoute('admin/<presenter>/<action>[/<id>]', 'Homepage:default');

		$router->withModule('Front')

            ->addRoute('[<lang=cs (cs)>/]', 'Homepage:default')
            ->addRoute('[<lang=cs (cs)>/]template', 'Homepage:template')
            ->addRoute('[<lang=cs (cs)>/]aktuality', 'New:default')
            ->addRoute('[<lang=cs (cs)>/]aktualita/<slug>', 'New:show')
            ->addRoute('[<lang=cs (cs)>/]<presenter>/<action>', 'Error:404');

		return $router;
	}

}
