<?php

require '../class-razorblade.php';

$engine = new RazorBlade(__DIR__, ['/', '/partials'], '/.cache');
$engine->view('home');
