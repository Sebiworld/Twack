<?php
namespace ProcessWire;

$twack = wire('modules')->get('Twack');
$general = $twack->getNewComponent('General');
$general->addStyle(wire('config')->urls->templates . 'assets/css/newsroom.min.css', true, true);

$mainContent = $twack->getComponent('mainContent');
$mainContent->addComponent('Newsroom');

echo $general->render();
