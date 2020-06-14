<?php
namespace ProcessWire;

$general = wire('twack')->getNewComponent('General');
$general->addStyle(wire('config')->urls->templates . 'assets/css/newsroom.min.css', true, true);

$mainContent = wire('twack')->getComponent('mainContent');
$mainContent->addComponent('Newsroom');

echo $general->render();
