<?php

// Is this really a search controller? Probably

use Phalcon\Mvc\Controller;

class IndexController extends Controller
{

	public function indexAction()
	{
		$this->view->title = "CHEESE!";
	}

}
