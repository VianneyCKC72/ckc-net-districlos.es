<?php

require_once dirname(__FILE__).'/../../config/config.inc.php';
require_once dirname(__FILE__).'/../../init.php';
require_once dirname(__FILE__).'/classes/CkcSyncQueue.php';

// Appel de la méthode statique de la classe
CkcSyncQueue::processQueue();