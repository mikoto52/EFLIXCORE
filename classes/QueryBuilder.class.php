<?php
namespace Core {
	class QueryBuilder extends \Pixie\Connection {
		public function __construct() {
			 $container = $container ? : new Container();

	        $this->container = $container;

	        $this->setAdapter($adapter)->setAdapterConfig($adapterConfig)->connect();

	        // Create event dependency
	        $this->eventHandler = $this->container->build('\\Pixie\\EventHandler');

	        if ($alias) {
	            $this->createAlias($alias);
	        }
		}
	}
}