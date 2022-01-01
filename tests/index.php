<?php

require '../engine.php';

$engine = new RazorBlade(__DIR__, ['/', '/partials'], '/../cache');
$engine->view('home');
